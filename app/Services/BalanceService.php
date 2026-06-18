<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\BotUser;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Единая точка изменения балансов. Любое движение денег проходит здесь:
 * атомарно (блокировка строки) и с записью в transactions (аудит, история).
 */
class BalanceService
{
    private const COLUMN = [
        Transaction::WALLET_DEPOSIT => 'deposit_balance',
        Transaction::WALLET_REFERRAL => 'referral_balance',
    ];

    /**
     * Зачисление на баланс. amount > 0.
     */
    public function credit(
        BotUser $user,
        float $amount,
        string $type,
        string $wallet = Transaction::WALLET_DEPOSIT,
        ?string $description = null,
        ?Model $source = null,
    ): Transaction {
        return $this->apply($user, abs($amount), $type, $wallet, $description, $source);
    }

    /**
     * Списание с баланса. amount > 0. Бросает исключение при недостатке средств.
     */
    public function debit(
        BotUser $user,
        float $amount,
        string $type,
        string $wallet = Transaction::WALLET_DEPOSIT,
        ?string $description = null,
        ?Model $source = null,
    ): Transaction {
        return $this->apply($user, -abs($amount), $type, $wallet, $description, $source);
    }

    /**
     * Достаточно ли средств на указанном балансе.
     */
    public function canAfford(BotUser $user, float $amount, string $wallet = Transaction::WALLET_DEPOSIT): bool
    {
        $column = self::COLUMN[$wallet];

        return (float) $user->{$column} >= $amount;
    }

    /**
     * @param  float  $signedAmount  + зачисление / − списание
     */
    private function apply(
        BotUser $user,
        float $signedAmount,
        string $type,
        string $wallet,
        ?string $description,
        ?Model $source,
    ): Transaction {
        $column = self::COLUMN[$wallet] ?? throw new \InvalidArgumentException("Unknown wallet: {$wallet}");

        return DB::transaction(function () use ($user, $signedAmount, $type, $wallet, $column, $description, $source) {
            /** @var BotUser $locked */
            $locked = BotUser::whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $current = (float) $locked->{$column};
            $after = round($current + $signedAmount, 5);

            if ($after < 0) {
                throw new InsufficientBalanceException(
                    "Недостаточно средств: нужно " . abs($signedAmount) . ", доступно {$current}",
                );
            }

            $locked->{$column} = $after;

            // Общий депозит копим только при реальном пополнении депозита.
            if ($type === Transaction::TYPE_DEPOSIT && $wallet === Transaction::WALLET_DEPOSIT) {
                $locked->total_deposited = round((float) $locked->total_deposited + $signedAmount, 5);
            }

            $locked->save();

            $transaction = $locked->transactions()->create([
                'type' => $type,
                'wallet' => $wallet,
                'amount' => round($signedAmount, 5),
                'balance_after' => $after,
                'description' => $description,
                'sourceable_type' => $source?->getMorphClass(),
                'sourceable_id' => $source?->getKey(),
            ]);

            // обновим переданный объект, чтобы вызывающий код видел свежие значения
            $user->forceFill($locked->only(['deposit_balance', 'referral_balance', 'total_deposited']));

            return $transaction;
        });
    }
}

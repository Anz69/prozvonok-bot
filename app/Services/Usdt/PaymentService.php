<?php

namespace App\Services\Usdt;

use App\Models\LoyaltyBonus;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\BalanceService;
use App\Services\ReferralService;
use App\Telegram\Support\AdminNotifier;
use App\Telegram\Support\Notifier;
use App\Telegram\Support\Screens;
use App\Models\BotText;
use Illuminate\Support\Facades\DB;

/**
 * Приём USDT: матчинг входящих транзакций на счета, идемпотентное зачисление,
 * бонус лояльности и реферальные начисления (раздел 3.4).
 */
class PaymentService
{
    public function __construct(
        private readonly BalanceService $balance,
        private readonly ReferralService $referrals,
        private readonly Notifier $notifier,
        private readonly AdminNotifier $adminNotifier,
    ) {
    }

    /**
     * Опросить сеть и обработать все ожидающие счета. Возвращает число зачисленных.
     */
    public function pollAll(): int
    {
        $this->expireStale();

        $address = (string) config('dozvon.usdt.wallet');
        if ($address === '') {
            return 0;
        }

        $watcher = app(UsdtWatcher::class);
        $credited = 0;

        foreach ($watcher->fetchIncoming($address) as $tx) {
            if ($this->ingest($tx)) {
                $credited++;
            }
        }

        return $credited;
    }

    /**
     * Ручное подтверждение оплаты менеджером (по диплинку). Зачисляет полную сумму счёта.
     */
    public function confirmManual(Payment $payment, ?int $adminUserId = null): bool
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return false;
        }

        return DB::transaction(function () use ($payment, $adminUserId) {
            $payment->amount_received = $payment->amount_expected;
            $payment->confirmations = 999;
            $payment->confirmed_by = $adminUserId;
            $this->credit($payment); // депозит + бонус + рефералка + статус + уведомление
            $payment->save();

            return true;
        });
    }

    /**
     * Немедленная проверка одного счёта (кнопка «Я оплатил»). true — если зачислено.
     */
    public function checkOne(Payment $payment): bool
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return false;
        }

        $watcher = app(UsdtWatcher::class);
        foreach ($watcher->fetchIncoming($payment->address) as $tx) {
            if ($this->matchPayment($tx) ?->is($payment)) {
                return $this->ingest($tx);
            }
        }

        return false;
    }

    public function expireStale(): int
    {
        return Payment::where('status', Payment::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => Payment::STATUS_EXPIRED]);
    }

    /**
     * Обработать одну входящую транзакцию. true — если по ней произошло зачисление.
     *
     * @param  array{tx_hash:string, to:string, amount:float, confirmations:int}  $tx
     */
    private function ingest(array $tx): bool
    {
        if (($tx['tx_hash'] ?? '') === '') {
            return false;
        }

        return DB::transaction(function () use ($tx) {
            $payment = $this->matchPayment($tx);
            if ($payment === null) {
                return false; // несопоставленный платёж — ручная обработка через поддержку
            }

            $payment->tx_hash = $tx['tx_hash'];
            $payment->amount_received = round((float) $tx['amount'], 5);
            $payment->confirmations = (int) $tx['confirmations'];

            $required = (int) config('dozvon.usdt.confirmations', 19);
            $alreadyCredited = $payment->paid_at !== null;

            if (! $alreadyCredited && $payment->confirmations >= $required) {
                $this->credit($payment);
                $payment->save();

                return true;
            }

            $payment->save();

            return false;
        });
    }

    /**
     * Найти счёт под транзакцию: сначала по уже привязанному tx_hash (идемпотентность),
     * иначе — по уникальной сумме среди активных pending-счетов.
     *
     * @param  array{tx_hash:string, to:string, amount:float, confirmations:int}  $tx
     */
    private function matchPayment(array $tx): ?Payment
    {
        $byHash = Payment::where('tx_hash', $tx['tx_hash'])->first();
        if ($byHash !== null) {
            return $byHash;
        }

        return Payment::where('status', Payment::STATUS_PENDING)
            ->whereNull('tx_hash')
            ->where('address', $tx['to'])
            ->whereRaw('ABS(amount_expected - ?) < 0.000005', [round((float) $tx['amount'], 5)])
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();
    }

    /**
     * Зачисление подтверждённого платежа: депозит + бонус лояльности + реф.начисления.
     */
    private function credit(Payment $payment): void
    {
        $user = $payment->botUser()->lockForUpdate()->first();
        $amount = (float) $payment->amount_received;
        $expected = (float) $payment->amount_expected;

        // Депозит — по фактически поступившей сумме (недоплата/переплата → зачисляем факт)
        $this->balance->credit(
            $user,
            $amount,
            Transaction::TYPE_DEPOSIT,
            Transaction::WALLET_DEPOSIT,
            "Пополнение USDT {$payment->uid}",
            $payment,
        );

        // Бонус лояльности по факту
        $bonus = LoyaltyBonus::forAmount($amount);
        if ($bonus > 0) {
            $this->balance->credit(
                $user,
                $bonus,
                Transaction::TYPE_BONUS_LOYALTY,
                Transaction::WALLET_DEPOSIT,
                "Бонус лояльности к {$payment->uid}",
                $payment,
            );
            $payment->bonus_amount = $bonus;
        }

        // Реферальные начисления пригласившему (комиссия + бонус за депозит от порога)
        $this->referrals->onReferredDeposit($user->fresh(), $amount);

        // Статус с учётом недо-/переплаты
        $payment->status = match (true) {
            $amount < $expected - 0.000005 => Payment::STATUS_UNDERPAID,
            $amount > $expected + 0.000005 => Payment::STATUS_OVERPAID,
            default => Payment::STATUS_PAID,
        };
        $payment->paid_at = now();

        // Алерт админам о крупном пополнении
        $threshold = (float) Setting::get('large_payment_alert', 1000);
        if ($threshold > 0 && $amount >= $threshold) {
            $this->adminNotifier->alert(
                "Крупное пополнение: " . Screens::money($amount) . "\$ ({$payment->uid}) "
                . "от id {$user->telegram_id}",
            );
        }

        // Уведомление пользователю
        $balanceNow = Screens::money($user->fresh()->deposit_balance);
        if ($payment->status === Payment::STATUS_UNDERPAID) {
            $text = BotText::render('payment_underpaid', ['received' => Screens::money($amount)]);
        } else {
            $text = BotText::render('payment_received', [
                'amount' => Screens::money($amount),
                'bonus' => $bonus > 0 ? ' (+' . Screens::money($bonus) . '$ бонус)' : '',
                'balance' => $balanceNow,
            ]);
        }
        $this->notifier->notify($user, $text);
    }
}

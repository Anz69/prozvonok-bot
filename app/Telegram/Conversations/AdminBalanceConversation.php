<?php

namespace App\Telegram\Conversations;

use App\Models\AdminAudit;
use App\Models\BotUser;
use App\Models\Transaction;
use App\Services\BalanceService;
use App\Telegram\Support\Admin;
use App\Telegram\Support\Screen;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

/**
 * Корректировка баланса пользователя из админки в боте: ввод суммы (+/−) → применение.
 */
class AdminBalanceConversation extends BotConversation
{
    public ?int $targetTelegramId = null;

    public function start(Nutgram $bot, ?int $targetTelegramId = null): void
    {
        if (! Admin::is($bot->userId())) {
            $this->end();

            return;
        }

        $this->targetTelegramId = $targetTelegramId;
        $bot->sendMessage("💵 Введите сумму для id {$targetTelegramId} (+ зачислить / − списать), или /start для отмены:");
        $this->next('apply');
    }

    public function apply(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $amount = (float) str_replace(',', '.', preg_replace('/[^0-9.\-]/', '', (string) $bot->message()?->text));
        $user = BotUser::where('telegram_id', $this->targetTelegramId)->first();

        if (! $user || $amount === 0.0) {
            $bot->sendMessage('Введите ненулевую сумму (например 50 или -25).');

            return;
        }

        $balance = app(BalanceService::class);
        $tx = $amount > 0
            ? $balance->credit($user, $amount, Transaction::TYPE_ADMIN_ADJUST, Transaction::WALLET_DEPOSIT, 'Корректировка из бота')
            : $balance->debit($user, abs($amount), Transaction::TYPE_ADMIN_ADJUST, Transaction::WALLET_DEPOSIT, 'Корректировка из бота');

        AdminAudit::log('bot_balance_adjust', $user, ['amount' => $amount, 'balance_after' => $tx->balance_after]);

        // уведомляем пользователя об изменении баланса
        $sign = $amount > 0 ? '+' : '−';
        app(\App\Telegram\Support\Notifier::class)->notify(
            $user,
            "ℹ️ Баланс изменён администратором: <b>{$sign}" . \App\Telegram\Support\Screens::money(abs($amount))
            . "\$</b>.\nТекущий депозит: " . \App\Telegram\Support\Screens::money($tx->balance_after) . '$',
        );

        Screen::show($bot, AdminUserLookupConversation::card($user->fresh()), AdminUserLookupConversation::actions($user->fresh()));
        $this->end();
    }
}

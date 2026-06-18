<?php

namespace App\Telegram\Conversations;

use App\Models\BotText;
use App\Models\BotUser;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\BalanceService;
use App\Telegram\Support\Screen;
use App\Telegram\Support\Screens;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Вывод реф.баланса (раздел 3.4a) в режиме одного экрана.
 */
class WithdrawConversation extends BotConversation
{
    public float $amount = 0;

    private function homeKb(): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()->addRow(...Screen::navRow('home'));
    }

    public function start(Nutgram $bot): void
    {
        /** @var BotUser $user */
        $user = $bot->get('bot_user');

        if (! $user->canWithdraw()) {
            $this->screen($bot, BotText::render('err_withdraw_locked'), $this->homeKb());
            $this->end();

            return;
        }

        $this->screen($bot, '💸 Введите сумму вывода (реф. баланс: ' . Screens::money($user->referral_balance) . '$):', $this->homeKb());
        $this->next('handleAmount');
    }

    public function handleAmount(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $amount = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $bot->message()?->text));
        $min = (float) Setting::get('min_withdraw', 50);

        if ($amount < $min) {
            $this->screen($bot, "❌ Минимальная сумма вывода — {$min}\$.", $this->homeKb());

            return;
        }
        if ($amount > (float) $user->referral_balance) {
            $this->screen($bot, BotText::render('err_withdraw_amount', ['balance' => Screens::money($user->referral_balance)]), $this->homeKb());

            return;
        }

        $this->amount = $amount;
        $this->screen($bot, '📥 Введите адрес USDT TRC-20 для вывода:', $this->homeKb());
        $this->next('handleAddress');
    }

    public function handleAddress(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $address = trim((string) $bot->message()?->text);

        if (! preg_match('/^T[1-9A-HJ-NP-Za-km-z]{33}$/', $address)) {
            $this->screen($bot, '❌ Похоже, адрес некорректный. Пришлите адрес USDT TRC-20 (начинается с T).', $this->homeKb());

            return;
        }

        $balance = app(BalanceService::class);
        if (! $balance->canAfford($user, $this->amount, Transaction::WALLET_REFERRAL)) {
            $this->screen($bot, BotText::render('err_withdraw_amount', ['balance' => Screens::money($user->referral_balance)]), $this->homeKb());
            $this->end();

            return;
        }

        $withdrawal = Withdrawal::create([
            'bot_user_id' => $user->id,
            'amount' => $this->amount,
            'address' => $address,
            'network' => 'TRC20',
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        $balance->debit(
            $user,
            $this->amount,
            Transaction::TYPE_WITHDRAW,
            Transaction::WALLET_REFERRAL,
            "Заявка на вывод #{$withdrawal->id}",
            $withdrawal,
        );

        app(\App\Telegram\Support\AdminNotifier::class)->alert(
            "Новая заявка на вывод #{$withdrawal->id}: " . Screens::money($this->amount)
            . "\$ от {$user->displayName()} (id {$user->telegram_id})",
        );

        $this->screen($bot, BotText::render('withdraw_accepted'), $this->homeKb());
        $this->end();
    }
}

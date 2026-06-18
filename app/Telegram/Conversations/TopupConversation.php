<?php

namespace App\Telegram\Conversations;

use App\Models\BotText;
use App\Models\BotUser;
use App\Models\LoyaltyBonus;
use App\Models\Payment;
use App\Models\Setting;
use App\Telegram\Support\Links;
use App\Telegram\Support\Screen;
use App\Telegram\Support\Screens;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Пополнение через менеджера (режим одного экрана): сумма → ссылка на менеджера
 * с готовым текстом, внизу которого — диплинк подтверждения ?start=pay_<hash>.
 * Зачисление происходит, когда админ открывает этот диплинк (см. StartHandler).
 */
class TopupConversation extends BotConversation
{
    private function homeKb(): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()->addRow(...Screen::navRow('home'));
    }

    public function start(Nutgram $bot): void
    {
        $bonusTable = LoyaltyBonus::query()->where('is_active', true)->orderBy('threshold')->get()
            ->map(fn ($b) => sprintf('• %s$ → +%s$', (int) $b->threshold, (int) $b->bonus))
            ->implode("\n");

        $this->screen($bot, BotText::render('topup_intro', [
            'min' => (int) Setting::get('min_deposit', 10),
            'bonus_table' => $bonusTable ?: '—',
        ]), $this->homeKb());
        $this->next('amount');
    }

    public function amount(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $raw = str_replace(',', '.', (string) $bot->message()?->text);
        $amount = (float) preg_replace('/[^0-9.]/', '', $raw);
        $min = (float) Setting::get('min_deposit', 10);

        if ($amount < $min) {
            $this->screen($bot, BotText::render('err_min_deposit', ['min' => (int) $min]), $this->homeKb());

            return;
        }

        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $ttl = (int) Setting::get('payment_ttl_minutes', 30);

        $payment = Payment::create([
            'bot_user_id' => $user->id,
            'uid' => Str::random(24),
            'method' => Payment::METHOD_MANAGER,
            'network' => (string) Setting::get('usdt_network', 'TRC20'),
            'amount_expected' => round($amount, 5),
            'status' => Payment::STATUS_PENDING,
        ]);

        $payload = 'pay_' . $payment->uid;
        $amountStr = Screens::money($payment->amount_expected);

        $kb = InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make('✉️ Оплатить у менеджера', url: Links::managerPay($amountStr, $payload)))
            ->addRow(...Screen::navRow('home'));

        $this->screen($bot, BotText::render('topup_manager', [
            'amount' => $amountStr,
            'confirm_link' => Links::deepLink($payload),
        ]), $kb);
        $this->end();
    }
}

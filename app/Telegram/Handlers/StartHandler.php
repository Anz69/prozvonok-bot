<?php

namespace App\Telegram\Handlers;

use App\Models\BotText;
use App\Models\BotUser;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\OnboardingService;
use App\Services\ReferralService;
use App\Telegram\Support\Admin;
use App\Telegram\Support\Menu;
use App\Telegram\Support\Screens;
use SergiX44\Nutgram\Nutgram;

/**
 * /start — диплинки (pay_<hash> подтверждение оплаты менеджером, реф.привязка),
 * онбординг и главный экран (раздел 3.1). Главный экран всегда пересоздаётся.
 */
class StartHandler
{
    public function __construct(
        private readonly ReferralService $referrals,
        private readonly OnboardingService $onboarding,
    ) {
    }

    public function __invoke(Nutgram $bot): void
    {
        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $payload = $this->parsePayload((string) ($bot->message()?->text ?? ''));

        // Диплинк подтверждения оплаты менеджером: ?start=pay_<hash>
        if ($payload !== null && str_starts_with($payload, 'pay_')) {
            $this->promptPayment($bot, $user, substr($payload, 4));

            return;
        }

        // Админ: /start сразу открывает админку
        if (Admin::is((int) $user->telegram_id)) {
            app(AdminHandler::class)->menu($bot);

            return;
        }

        // Реферальная привязка: ?start=<referrer_id>
        if ($payload !== null && ctype_digit($payload)) {
            $this->referrals->bind($user, (int) $payload);
        }

        // Онбординг: подписка → капча → главный экран
        if (! $user->is_subscribed && ! $this->onboarding->isSubscribed($bot, (int) $user->telegram_id)) {
            Menu::sendSubscription($bot);

            return;
        }
        $user->update(['is_subscribed' => true]);

        if (! $user->passed_captcha && Setting::get('captcha_enabled', true)) {
            Menu::sendCaptcha($bot);

            return;
        }

        Menu::sendHome($bot);
    }

    /**
     * Открытие pay-диплинка: показываем админу карточку платежа с Подтвердить/Отменить.
     * Не-админу — пояснение. Само зачисление — по кнопке (PaymentHandler).
     */
    private function promptPayment(Nutgram $bot, BotUser $opener, string $hash): void
    {
        $payment = Payment::where('uid', $hash)->where('method', Payment::METHOD_MANAGER)->first();

        if ($payment === null || $payment->status !== Payment::STATUS_PENDING) {
            $bot->sendMessage('❌ Ссылка недействительна или платёж уже обработан.');

            return;
        }

        if (! Admin::is((int) $opener->telegram_id)) {
            $bot->sendMessage(BotText::render('payment_link_not_admin'));

            return;
        }

        $target = $payment->botUser;
        $text = "💳 <b>Подтверждение оплаты</b>\n\n"
            . "👤 Пользователь: {$target->displayName()} (id {$target->telegram_id})\n"
            . '💵 Сумма: ' . Screens::money($payment->amount_expected) . "\$\n\n"
            . 'Зачислить средства пользователю?';

        $kb = \SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup::make()->addRow(
            \SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton::make('✅ Подтвердить', callback_data: 'paycfm:' . $payment->uid),
            \SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton::make('❌ Отменить', callback_data: 'paycxl:' . $payment->uid),
        );

        \App\Telegram\Support\Screen::show($bot, $text, $kb);
    }

    private function parsePayload(string $text): ?string
    {
        if (preg_match('/^\/start(?:@\w+)?\s+(\S+)/', $text, $m)) {
            return $m[1];
        }

        return null;
    }
}

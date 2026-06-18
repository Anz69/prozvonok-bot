<?php

namespace App\Telegram\Handlers;

use App\Models\BotText;
use App\Models\BotUser;
use App\Models\Setting;
use App\Services\OnboardingService;
use App\Telegram\Support\Menu;
use SergiX44\Nutgram\Nutgram;

/**
 * Колбэки онбординга: «Я подписался» и выбор эмодзи в капче (раздел 3.1).
 */
class OnboardingHandler
{
    public function __construct(private readonly OnboardingService $onboarding)
    {
    }

    public function checkSubscription(Nutgram $bot): void
    {
        /** @var BotUser $user */
        $user = $bot->get('bot_user');

        if (! $this->onboarding->isSubscribed($bot, (int) $user->telegram_id)) {
            $bot->answerCallbackQuery(text: BotText::render('err_not_subscribed'), show_alert: true);

            return;
        }

        $user->update(['is_subscribed' => true]);
        $bot->answerCallbackQuery(text: '✅');
        $bot->deleteMessage($bot->chatId(), $bot->messageId());

        if (! $user->passed_captcha) {
            Menu::sendCaptcha($bot);

            return;
        }

        Menu::sendHome($bot);
    }

    public function verifyCaptcha(Nutgram $bot, string $choice): void
    {
        /** @var BotUser $user */
        $user = $bot->get('bot_user');

        $target = (string) Setting::get('captcha_target', '🍍');

        if ($choice !== $target) {
            $bot->answerCallbackQuery(
                text: BotText::render('err_captcha', ['target' => $target]),
                show_alert: true,
            );

            return;
        }

        $user->update(['passed_captcha' => true]);
        $bot->answerCallbackQuery(text: '✅');
        $bot->deleteMessage($bot->chatId(), $bot->messageId());

        Menu::sendHome($bot);
    }
}

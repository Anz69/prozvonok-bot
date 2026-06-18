<?php

namespace App\Telegram\Middleware;

use App\Models\BotUser;
use App\Services\OnboardingService;
use App\Telegram\Support\Menu;
use SergiX44\Nutgram\Nutgram;

/**
 * Гейт онбординга: до прохождения подписки и капчи пользователь к меню не допускается.
 */
class EnsureOnboarded
{
    public function __construct(private readonly OnboardingService $onboarding)
    {
    }

    public function __invoke(Nutgram $bot, callable $next): void
    {
        /** @var BotUser|null $user */
        $user = $bot->get('bot_user');

        if ($user === null) {
            return;
        }

        // Подписка
        if (! $user->is_subscribed) {
            if ($this->onboarding->isSubscribed($bot, (int) $user->telegram_id)) {
                $user->update(['is_subscribed' => true]);
            } else {
                Menu::sendSubscription($bot);

                return;
            }
        }

        // Капча (если включена в настройках)
        if (! $user->passed_captcha && \App\Models\Setting::get('captcha_enabled', true)) {
            Menu::sendCaptcha($bot);

            return;
        }

        $next($bot);
    }
}

<?php

namespace App\Telegram\Middleware;

use App\Models\BotUser;
use Illuminate\Support\Facades\RateLimiter;
use SergiX44\Nutgram\Nutgram;

/**
 * Анти-флуд: ограничивает частоту действий одного пользователя (раздел 6 — rate-limiting).
 * Лимит — настраивается в настройках (по умолчанию 20 действий в минуту).
 */
class ThrottleUser
{
    public function __invoke(Nutgram $bot, callable $next): void
    {
        /** @var BotUser|null $user */
        $user = $bot->get('bot_user');

        if ($user !== null) {
            $key = 'tg-throttle:' . $user->telegram_id;
            $limit = (int) \App\Models\Setting::get('rate_limit_per_minute', 20);

            if (RateLimiter::tooManyAttempts($key, $limit)) {
                if ($bot->callbackQuery() !== null) {
                    $bot->answerCallbackQuery(text: '⏳ Слишком часто. Подождите немного.', show_alert: true);
                } else {
                    $bot->sendMessage('⏳ Слишком часто. Подождите немного.');
                }

                return;
            }
            RateLimiter::hit($key, 60);
        }

        $next($bot);
    }
}

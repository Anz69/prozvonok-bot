<?php

namespace App\Telegram\Middleware;

use App\Models\BotUser;
use SergiX44\Nutgram\Nutgram;

/**
 * Глобальный middleware: находит/создаёт BotUser по Telegram-пользователю
 * и кладёт его в контекст обновления ($bot->get('bot_user')).
 */
class ResolveBotUser
{
    public function __invoke(Nutgram $bot, callable $next): void
    {
        $from = $bot->user();

        if ($from !== null && ! ($from->is_bot ?? false)) {
            $user = BotUser::firstOrNew(['telegram_id' => $from->id]);

            $user->username = $from->username;
            $user->first_name = $from->first_name;
            $user->last_name = $from->last_name;
            $user->language_code = $from->language_code;

            if ($user->isDirty() || ! $user->exists) {
                $user->save();
            }

            $bot->set('bot_user', $user);
        }

        $next($bot);
    }
}

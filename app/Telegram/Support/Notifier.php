<?php

namespace App\Telegram\Support;

use App\Models\BotUser;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use Throwable;

/**
 * Отправка сообщений пользователю из фоновых процессов (джобы, команды, вебхуки),
 * где нет активного update. Бот резолвится лениво — фоновые команды не падают,
 * если токен не задан или пользователь недоступен.
 */
class Notifier
{
    private function bot(): ?Nutgram
    {
        if (empty(config('nutgram.token'))) {
            return null;
        }

        try {
            return app(Nutgram::class);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public function notify(BotUser $user, string $text, mixed $keyboard = null, bool $repost = true): void
    {
        $bot = $this->bot();
        if ($bot === null) {
            return;
        }

        try {
            $bot->sendMessage(
                text: $text,
                chat_id: (int) $user->telegram_id,
                reply_markup: $keyboard,
                parse_mode: ParseMode::HTML,
            );

            // Управление всегда ниже уведомлений: перепостить экран вниз.
            if ($repost && $keyboard === null) {
                Screen::repostFor($bot, $user);
            }
        } catch (Throwable $e) {
            report($e); // пользователь мог заблокировать бота — не валим процесс
        }
    }

    /**
     * Отправить документ (выходной файл проверки) с подписью.
     */
    public function sendDocument(BotUser $user, string $absolutePath, string $caption = ''): void
    {
        $bot = $this->bot();
        if ($bot === null) {
            return;
        }

        try {
            $resource = fopen($absolutePath, 'rb');
            $bot->sendDocument(
                document: InputFile::make($resource, basename($absolutePath)),
                chat_id: (int) $user->telegram_id,
                caption: $caption !== '' ? $caption : null,
                parse_mode: ParseMode::HTML,
            );

            Screen::repostFor($bot, $user); // управление — ниже выданного файла
        } catch (Throwable $e) {
            report($e);
        }
    }
}

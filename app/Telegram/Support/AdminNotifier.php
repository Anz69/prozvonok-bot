<?php

namespace App\Telegram\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use Throwable;

/**
 * Алерты администраторам/менеджерам в Telegram (раздел 6 — мониторинг).
 * Список получателей — настройка admin_chat_ids (json). Бот резолвится лениво,
 * процесс не падает без токена/получателей.
 */
class AdminNotifier
{
    public function alert(string $text): void
    {
        $chatIds = (array) Setting::get('admin_chat_ids', []);
        if ($chatIds === [] || empty(config('nutgram.token'))) {
            Log::channel('integration')->info('AdminNotifier (нет получателей/токена): ' . $text);

            return;
        }

        try {
            $bot = app(Nutgram::class);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        foreach ($chatIds as $chatId) {
            try {
                $bot->sendMessage(text: '🛎 ' . $text, chat_id: (int) $chatId, parse_mode: ParseMode::HTML);
            } catch (Throwable $e) {
                report($e);
            }
        }

        Log::channel('integration')->info('AdminNotifier: ' . $text);
    }
}

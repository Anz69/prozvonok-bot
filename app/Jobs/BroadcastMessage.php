<?php

namespace App\Jobs;

use App\Models\BotUser;
use App\Telegram\Support\Notifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Рассылка сообщения всем пользователям бота (из админки в боте).
 * Бьём по чанкам, с паузой — чтобы не упереться в лимиты Telegram.
 */
class BroadcastMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $text)
    {
    }

    public function handle(Notifier $notifier): void
    {
        BotUser::where('is_banned', false)
            ->select(['id', 'telegram_id'])
            ->chunkById(100, function ($users) use ($notifier) {
                foreach ($users as $user) {
                    $notifier->notify($user, $this->text, repost: false); // массово экран не двигаем
                    usleep(40_000); // ~25 сообщений/сек, в пределах лимитов
                }
            });
    }
}

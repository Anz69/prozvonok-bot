<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

/**
 * Установка Telegram webhook на боевой URL.
 * Пример: php artisan bot:webhook:set https://example.com/<TELEGRAM_TOKEN>
 */
class SetWebhook extends Command
{
    protected $signature = 'bot:webhook:set {url}';

    protected $description = 'Установить webhook Telegram-бота на указанный URL';

    public function handle(Nutgram $bot): int
    {
        $url = (string) $this->argument('url');

        $ok = $bot->setWebhook($url);
        $this->info($ok ? "Webhook установлен: {$url}" : 'Не удалось установить webhook');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}

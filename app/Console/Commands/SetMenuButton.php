<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Command\MenuButtonDefault;
use SergiX44\Nutgram\Telegram\Types\Command\MenuButtonWebApp;
use SergiX44\Nutgram\Telegram\Types\WebApp\WebAppInfo;

/**
 * Настроить кнопку-меню бота (слева от поля ввода) на запуск Mini App.
 * URL берётся из MINIAPP_URL (config dozvon.miniapp_url) или из аргумента.
 * Пример: php artisan bot:menu:set https://example.com/app
 * Сброс на дефолт: php artisan bot:menu:set --reset
 */
class SetMenuButton extends Command
{
    protected $signature = 'bot:menu:set {url?} {--reset : Вернуть стандартную кнопку меню}';

    protected $description = 'Установить кнопку-меню бота на запуск Mini App';

    public function handle(Nutgram $bot): int
    {
        if ($this->option('reset')) {
            $ok = $bot->setChatMenuButton(menu_button: new MenuButtonDefault());
            $this->info($ok ? 'Кнопка меню сброшена на стандартную.' : 'Не удалось сбросить кнопку меню.');

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $url = (string) ($this->argument('url') ?: config('dozvon.miniapp_url'));

        if ($url === '') {
            $this->error('URL не задан. Передайте аргументом или укажите MINIAPP_URL в .env');

            return self::FAILURE;
        }

        $ok = $bot->setChatMenuButton(
            menu_button: new MenuButtonWebApp('🚀 Приложение', WebAppInfo::make($url)),
        );

        $this->info($ok ? "Кнопка меню установлена на: {$url}" : 'Не удалось установить кнопку меню.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}

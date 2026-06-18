<?php

namespace App\Telegram\Handlers;

use App\Telegram\Conversations\CheckBaseConversation;
use App\Telegram\Support\Menu;
use SergiX44\Nutgram\Nutgram;

/**
 * Нижняя навигационная клавиатура (reply): ⬅️ Назад / 🏠 Главная / 📂 Проверить базу.
 * Контент-разделы открываются инлайн-кнопками; здесь — только навигация снизу.
 */
class MenuRouter
{
    public function __invoke(Nutgram $bot): void
    {
        $text = trim((string) ($bot->message()?->text ?? ''));

        match ($text) {
            '🏠 Главная', '🏠 Меню', '/menu' => Menu::sendHome($bot),
            '⬅️ Назад' => app(Navigator::class)->back($bot),
            '📂 Проверить базу' => CheckBaseConversation::begin($bot),
            default => null,
        };
    }
}

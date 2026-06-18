<?php

namespace App\Telegram\Conversations;

use App\Models\BotButton;
use App\Telegram\Handlers\Navigator;
use App\Telegram\Handlers\StartHandler;
use App\Telegram\Support\Screen;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * База для диалогов: единый экран (Screen::show) + выход по /start, кнопке меню и
 * инлайн-навигации (🏠 Главная / ⬅️ Назад) из любого шага.
 */
abstract class BotConversation extends Conversation
{
    /** Показать/обновить единый экран бота. */
    protected function screen(Nutgram $bot, string $text, ?InlineKeyboardMarkup $keyboard = null): void
    {
        Screen::show($bot, $text, $keyboard);
    }

    /**
     * Перехват /start, кнопок меню и nav-навигации → завершаем диалог и отдаём управление.
     */
    protected function escaped(Nutgram $bot): bool
    {
        $text = $bot->message()?->text;
        if ($text !== null) {
            if ($text === '/start' || str_starts_with($text, '/start ')) {
                $this->end();
                app(StartHandler::class)($bot);

                return true;
            }
            // нижняя навигация (reply) или подпись кнопки меню → выходим из диалога
            $navButtons = ['⬅️ Назад', '🏠 Главная', '🏠 Меню', '📂 Проверить базу'];
            if (in_array($text, $navButtons, true) || collect(BotButton::menu('main'))->contains('label', $text)) {
                $this->end();
                app(\App\Telegram\Handlers\MenuRouter::class)($bot);

                return true;
            }
        }

        $data = $bot->callbackQuery()?->data;
        if ($data !== null && str_starts_with($data, 'nav:')) {
            $this->end();
            app(Navigator::class)->nav($bot, substr($data, 4));

            return true;
        }

        return false;
    }
}

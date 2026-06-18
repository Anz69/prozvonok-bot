<?php

namespace App\Telegram\Conversations;

use App\Models\BotUser;
use App\Models\SupportTicket;
use App\Telegram\Support\AdminNotifier;
use App\Telegram\Support\Screen;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Обращение в поддержку из бота (режим одного экрана): сообщение → тикет + алерт.
 */
class SupportConversation extends BotConversation
{
    private function homeKb(): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()->addRow(...Screen::navRow('home'));
    }

    public function start(Nutgram $bot): void
    {
        $this->screen($bot, '✉️ Опишите ваш вопрос одним сообщением — передам в поддержку.', $this->homeKb());
        $this->next('capture');
    }

    public function capture(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $text = trim((string) $bot->message()?->text);
        if ($text === '') {
            $this->screen($bot, 'Пришлите, пожалуйста, текст обращения.', $this->homeKb());

            return;
        }

        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $ticket = SupportTicket::create([
            'bot_user_id' => $user->id,
            'type' => SupportTicket::TYPE_GENERAL,
            'message' => $text,
        ]);

        app(AdminNotifier::class)->alert(
            "Новое обращение #{$ticket->id} от {$user->displayName()} (id {$user->telegram_id}):\n{$text}",
        );

        $this->screen($bot, '✅ Обращение принято. Поддержка скоро ответит.', $this->homeKb());
        $this->end();
    }
}

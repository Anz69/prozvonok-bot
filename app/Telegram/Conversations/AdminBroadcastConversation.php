<?php

namespace App\Telegram\Conversations;

use App\Jobs\BroadcastMessage;
use App\Models\BotUser;
use App\Telegram\Support\Admin;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Рассылка из админки в боте: ввод текста → подтверждение → постановка в очередь.
 */
class AdminBroadcastConversation extends BotConversation
{
    public ?string $text = null;

    public function start(Nutgram $bot): void
    {
        if (! Admin::is($bot->userId())) {
            $this->end();

            return;
        }

        $bot->sendMessage('📢 Пришлите текст рассылки одним сообщением (или /start для отмены).');
        $this->next('confirm');
    }

    public function confirm(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $this->text = trim((string) $bot->message()?->text);
        if ($this->text === '') {
            $bot->sendMessage('Пустой текст. Пришлите сообщение для рассылки.');

            return;
        }

        $count = BotUser::where('is_banned', false)->count();
        $bot->sendMessage(
            text: "Разослать <b>{$count}</b> пользователям?\n\n— — —\n{$this->text}",
            reply_markup: InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make('✅ Отправить', callback_data: 'adm:bcast:go'),
                InlineKeyboardButton::make('❌ Отмена', callback_data: 'adm:bcast:cancel'),
            ),
            parse_mode: ParseMode::HTML,
        );
        $this->next('send');
    }

    public function send(Nutgram $bot): void
    {
        $data = $bot->callbackQuery()?->data;
        if ($data === 'adm:bcast:go' && $this->text) {
            BroadcastMessage::dispatch($this->text);
            $bot->answerCallbackQuery(text: 'Рассылка запущена');
            $bot->sendMessage('✅ Рассылка поставлена в очередь.');
        } else {
            $bot->answerCallbackQuery(text: 'Отменено');
        }
        $this->end();
    }
}

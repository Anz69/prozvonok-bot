<?php

namespace App\Telegram\Support;

use App\Models\BotUser;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Движок «одного экрана»: в чате всегда живёт ровно одно сообщение бота.
 * - на нажатие инлайн-кнопки (callback) — редактируем текущее сообщение на месте;
 * - на команду/текст/файл — удаляем прошлый экран и присылаем новый.
 * message_id экрана хранится в bot_users.state['screen_id'].
 */
class Screen
{
    /** Карта «куда вести Назад» с каждого экрана. */
    public const BACK = [
        'home' => 'home',
        'profile' => 'home',
        'profile_full' => 'profile',
        'hist_checks' => 'profile',
        'hist_balance' => 'profile',
        'balance' => 'home',
        'referral' => 'home',
        'referral_details' => 'referral',
        'premium' => 'home',
        'info' => 'home',
    ];

    public static function show(Nutgram $bot, string $text, ?InlineKeyboardMarkup $keyboard = null, ?string $key = null): void
    {
        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $state = $user->state ?? [];
        if ($key !== null) {
            $state['screen'] = $key;
        }

        // Если пришли по инлайн-кнопке — редактируем то же сообщение
        $cb = $bot->callbackQuery();
        if ($cb !== null && $cb->message !== null) {
            try {
                $bot->editMessageText(
                    text: $text,
                    chat_id: $bot->chatId(),
                    message_id: $cb->message->message_id,
                    reply_markup: $keyboard,
                    parse_mode: ParseMode::HTML,
                );
                $state['screen_id'] = $cb->message->message_id;
                $user->update(['state' => $state]);

                return;
            } catch (\Throwable) {
                // нечего редактировать (текст не изменился/сообщение удалено) — упадём в send
            }
        }

        // Иначе — удаляем прошлый экран и шлём новый
        if (! empty($state['screen_id'])) {
            try {
                $bot->deleteMessage($bot->chatId(), (int) $state['screen_id']);
            } catch (\Throwable) {
            }
        }

        $message = $bot->sendMessage(
            text: $text,
            reply_markup: $keyboard,
            parse_mode: ParseMode::HTML,
        );

        $state['screen_id'] = $message?->message_id;
        $user->update(['state' => $state]);
    }

    /**
     * Перепостить контрольный экран пользователя ВНИЗ (после уведомления),
     * чтобы управление всегда было ниже уведомлений. Старый экран удаляется.
     * Не трогаем активный диалог (там экран и так внизу, ввод не прерываем).
     */
    public static function repostFor(Nutgram $bot, BotUser $user): void
    {
        $chatId = (int) $user->telegram_id;

        if ($bot->currentConversation($chatId, $chatId, null) !== null) {
            return;
        }

        $screen = Screens::byKey($user, (string) ($user->state['screen'] ?? 'home'));
        $state = $user->state ?? [];

        if (! empty($state['screen_id'])) {
            try {
                $bot->deleteMessage($chatId, (int) $state['screen_id']);
            } catch (\Throwable) {
            }
        }

        $message = $bot->sendMessage(
            text: $screen['text'],
            chat_id: $chatId,
            reply_markup: $screen['keyboard'],
            parse_mode: ParseMode::HTML,
        );

        $state['screen_id'] = $message?->message_id;
        $user->update(['state' => $state]);
    }

    /** Кнопки навигации для подэкранов: ⬅️ Назад (target) + 🏠 Главное меню. */
    public static function navRow(string $backTarget = 'home'): array
    {
        $row = [];
        if ($backTarget !== '' && $backTarget !== 'home') {
            $row[] = \SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton::make('⬅️ Назад', callback_data: 'nav:' . $backTarget);
        }
        $row[] = \SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton::make('🏠 Главное меню', callback_data: 'nav:home');

        return $row;
    }
}

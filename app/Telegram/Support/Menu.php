<?php

namespace App\Telegram\Support;

use App\Models\BotText;
use App\Models\BotUser;
use App\Models\RequiredChannel;
use App\Services\OnboardingService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

/**
 * Экраны онбординга и главного экрана. Контент — инлайн (одно сообщение, Screen),
 * навигация ⬅️ Назад / 🏠 Главная — нижняя reply-клавиатура (закрепляется один раз).
 */
class Menu
{
    /** Нижняя навигационная клавиатура (всегда снизу). */
    public static function navKeyboard(): ReplyKeyboardMarkup
    {
        return ReplyKeyboardMarkup::make(resize_keyboard: true, is_persistent: true)
            ->addRow(
                KeyboardButton::make('⬅️ Назад'),
                KeyboardButton::make('🏠 Главная'),
            )
            ->addRow(KeyboardButton::make('📂 Проверить базу'));
    }

    /** Закрепить нижнюю клавиатуру один раз (persist; сообщение-якорь сразу удаляем). */
    public static function ensureNavKeyboard(Nutgram $bot): void
    {
        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $state = $user->state ?? [];
        if (! empty($state['nav_kb'])) {
            return;
        }

        try {
            $msg = $bot->sendMessage('🔻', reply_markup: self::navKeyboard());
            if ($msg) {
                $bot->deleteMessage($bot->chatId(), $msg->message_id);
            }
        } catch (\Throwable) {
        }

        $state['nav_kb'] = true;
        $user->update(['state' => $state]);
    }

    /** Главный экран (welcome + инлайн-меню). */
    public static function sendHome(Nutgram $bot): void
    {
        self::ensureNavKeyboard($bot);

        /** @var BotUser $user */
        $user = $bot->get('bot_user');
        $screen = Screens::home($user);
        Screen::show($bot, $screen['text'], $screen['keyboard'], 'home');
    }

    public static function sendSubscription(Nutgram $bot): void
    {
        self::dropLegacyReplyKeyboard($bot);

        $kb = InlineKeyboardMarkup::make();
        foreach (RequiredChannel::active()->get() as $channel) {
            if ($channel->url) {
                $kb->addRow(InlineKeyboardButton::make(
                    '📢 Подписаться: ' . ($channel->title ?: $channel->chat_id),
                    url: $channel->url,
                ));
            }
        }
        $kb->addRow(InlineKeyboardButton::make('✅ Я подписался', callback_data: 'onb:check_sub'));

        Screen::show($bot, BotText::render('subscription_required'), $kb);
    }

    public static function sendCaptcha(Nutgram $bot): void
    {
        $captcha = app(OnboardingService::class)->makeCaptcha();

        $kb = InlineKeyboardMarkup::make();
        $kb->addRow(...array_map(
            fn ($emoji) => InlineKeyboardButton::make($emoji, callback_data: 'onb:captcha:' . $emoji),
            $captcha['options'],
        ));

        Screen::show($bot, BotText::render('captcha', ['target' => $captcha['target']]), $kb);
    }

}

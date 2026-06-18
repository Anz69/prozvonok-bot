<?php

namespace App\Telegram\Conversations;

use App\Models\BotUser;
use App\Telegram\Support\Admin;
use App\Telegram\Support\Screen;
use App\Telegram\Support\Screens;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Поиск пользователя по Telegram-ID из админки в боте: карточка + быстрые действия.
 */
class AdminUserLookupConversation extends BotConversation
{
    public function start(Nutgram $bot): void
    {
        if (! Admin::is($bot->userId())) {
            $this->end();

            return;
        }

        $bot->sendMessage('👤 Пришлите Telegram-ID пользователя (или /start для отмены).');
        $this->next('show');
    }

    public function show(Nutgram $bot): void
    {
        if ($this->escaped($bot)) {
            return;
        }

        $id = (int) preg_replace('/\D/', '', (string) $bot->message()?->text);
        $user = $id > 0 ? BotUser::where('telegram_id', $id)->first() : null;

        if (! $user) {
            $bot->sendMessage('Не найден. Пришлите корректный Telegram-ID.');

            return;
        }

        Screen::show($bot, self::card($user), self::actions($user));
        $this->end();
    }

    public static function card(BotUser $u): string
    {
        return "👤 <b>{$u->displayName()}</b>\n"
            . "🆔 {$u->telegram_id}" . ($u->username ? " (@{$u->username})" : '') . "\n"
            . '📅 С нами с: ' . $u->created_at?->format('d.m.Y') . "\n\n"
            . '💵 Депозит: ' . Screens::money($u->deposit_balance) . "\$\n"
            . '💳 Реф. баланс: ' . Screens::money($u->referral_balance) . "\$\n"
            . '🏆 Премиум: ' . ($u->hasActivePremium() ? $u->premium_tier . ' до ' . $u->premium_until->format('d.m.Y') : '---') . "\n"
            . '📞 Проверено: ' . $u->numbers_checked . " номеров\n"
            . '👥 Рефералов: ' . $u->referralsCount() . "\n"
            . '🚫 Бан: ' . ($u->is_banned ? 'да' : 'нет');
    }

    public static function actions(BotUser $u): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make(
                    $u->is_banned ? '✅ Разбанить' : '🚫 Забанить',
                    callback_data: 'adm:uban:' . $u->telegram_id,
                ),
                InlineKeyboardButton::make('💎 Премиум 30д', callback_data: 'adm:uprem:' . $u->telegram_id),
            )
            ->addRow(InlineKeyboardButton::make('💵 Изменить баланс', callback_data: 'adm:ubal:' . $u->telegram_id));
    }
}

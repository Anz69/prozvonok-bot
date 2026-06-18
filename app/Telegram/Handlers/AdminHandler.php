<?php

namespace App\Telegram\Handlers;

use App\Models\BotUser;
use App\Models\CheckJob;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Telegram\Conversations\AdminBalanceConversation;
use App\Telegram\Conversations\AdminUserLookupConversation;
use App\Telegram\Support\Admin;
use App\Telegram\Support\Screen;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Админка внутри бота (для admin_chat_ids). Рисуется единым экраном (Screen).
 */
class AdminHandler
{
    private function guard(Nutgram $bot): bool
    {
        if (Admin::is($bot->userId())) {
            return true;
        }
        if ($bot->callbackQuery() !== null) {
            $bot->answerCallbackQuery(text: 'Недостаточно прав', show_alert: true);
        }

        return false;
    }

    private function ack(Nutgram $bot): void
    {
        if ($bot->callbackQuery() !== null) {
            $bot->answerCallbackQuery();
        }
    }

    private function menuKeyboard(): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make('📊 Статистика', callback_data: 'adm:stats'))
            ->addRow(
                InlineKeyboardButton::make('📢 Рассылка', callback_data: 'adm:broadcast'),
                InlineKeyboardButton::make('👤 Юзер по ID', callback_data: 'adm:user'),
            )
            ->addRow(InlineKeyboardButton::make('⚙️ Настройки', callback_data: 'adm:settings'))
            ->addRow(InlineKeyboardButton::make('🏠 Меню пользователя', callback_data: 'nav:home'));
    }

    private function backRow(): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make('⬅️ В админку', callback_data: 'adm:home'));
    }

    /** /admin и /start для админа. */
    public function menu(Nutgram $bot): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        Screen::show($bot, "🛠 <b>Админка Сола</b>\nВыберите раздел:", $this->menuKeyboard());
    }

    public function home(Nutgram $bot): void
    {
        $this->menu($bot);
    }

    public function stats(Nutgram $bot): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);

        $depositToday = (float) Transaction::where('type', Transaction::TYPE_DEPOSIT)
            ->whereDate('created_at', today())->sum('amount');
        $text = "📊 <b>Статистика</b>\n\n"
            . '👥 Пользователей: ' . BotUser::count() . "\n"
            . '🆕 Сегодня: ' . BotUser::whereDate('created_at', today())->count() . "\n"
            . '💰 Пополнения сегодня: ' . number_format($depositToday, 2) . "\$\n"
            . '📂 Активные задания: ' . CheckJob::whereIn('status', [
                CheckJob::STATUS_QUEUED, CheckJob::STATUS_SCHEDULED, CheckJob::STATUS_PROCESSING,
            ])->count() . "\n"
            . '💸 Выводы в ожидании: ' . Withdrawal::where('status', Withdrawal::STATUS_PENDING)->count() . "\n"
            . '✉️ Открытые тикеты: ' . SupportTicket::where('status', 'open')->count();

        Screen::show($bot, $text, $this->backRow());
    }

    public function settings(Nutgram $bot): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);

        $sub = Setting::get('subscription_required', true) ? '✅ вкл' : '❌ выкл';
        $cap = Setting::get('captcha_enabled', true) ? '✅ вкл' : '❌ выкл';

        Screen::show($bot, "⚙️ <b>Быстрые настройки</b>\nНажмите, чтобы переключить:", InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make("Подписка: {$sub}", callback_data: 'adm:toggle:subscription_required'))
            ->addRow(InlineKeyboardButton::make("Капча: {$cap}", callback_data: 'adm:toggle:captcha_enabled'))
            ->addRow(InlineKeyboardButton::make('⬅️ В админку', callback_data: 'adm:home')));
    }

    public function toggle(Nutgram $bot, string $key): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        if (in_array($key, ['subscription_required', 'captcha_enabled'], true)) {
            Setting::put($key, Setting::get($key, true) ? '0' : '1', 'bool');
        }
        $this->settings($bot);
    }

    public function broadcast(Nutgram $bot): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        \App\Telegram\Conversations\AdminBroadcastConversation::begin($bot);
    }

    public function user(Nutgram $bot): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        AdminUserLookupConversation::begin($bot);
    }

    public function adjustBalance(Nutgram $bot, string $id): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        AdminBalanceConversation::begin($bot, data: [(int) $id]);
    }

    public function banToggle(Nutgram $bot, string $id): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $user = BotUser::where('telegram_id', (int) $id)->first();
        if (! $user) {
            $bot->answerCallbackQuery(text: 'Не найден', show_alert: true);

            return;
        }
        $user->update(['is_banned' => ! $user->is_banned]);
        \App\Models\AdminAudit::log($user->is_banned ? 'bot_ban' : 'bot_unban', $user);

        $this->ack($bot);
        Screen::show($bot, AdminUserLookupConversation::card($user), AdminUserLookupConversation::actions($user));
    }

    public function grantPremium(Nutgram $bot, string $id): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $user = BotUser::where('telegram_id', (int) $id)->first();
        if (! $user) {
            $bot->answerCallbackQuery(text: 'Не найден', show_alert: true);

            return;
        }
        $user->update([
            'premium_tier' => 'premium',
            'premium_until' => now()->addDays((int) Setting::get('premium_days', 30)),
            'check_discount' => (int) Setting::get('premium_discount', 25),
        ]);
        \App\Models\AdminAudit::log('bot_grant_premium', $user);
        app(\App\Telegram\Support\Notifier::class)->notify($user, '💎 Вам начислен Премиум на 30 дней!');

        $this->ack($bot);
        Screen::show($bot, AdminUserLookupConversation::card($user), AdminUserLookupConversation::actions($user));
    }
}

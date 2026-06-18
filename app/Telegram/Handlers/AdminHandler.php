<?php

namespace App\Telegram\Handlers;

use App\Models\BotText;
use App\Models\BotUser;
use App\Models\CheckJob;
use App\Models\Geo;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Telegram\Conversations\AdminBalanceConversation;
use App\Telegram\Conversations\AdminInputConversation;
use App\Telegram\Conversations\AdminUserLookupConversation;
use App\Telegram\Support\Admin;
use App\Telegram\Support\Screen;
use App\Telegram\Support\Screens;
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
                InlineKeyboardButton::make('👤 Юзер по ID', callback_data: 'adm:user'),
                InlineKeyboardButton::make('📢 Рассылка', callback_data: 'adm:broadcast'),
            )
            ->addRow(
                InlineKeyboardButton::make('💸 Выводы', callback_data: 'adm:withdrawals'),
                InlineKeyboardButton::make('✉️ Тикеты', callback_data: 'adm:tickets'),
            )
            ->addRow(
                InlineKeyboardButton::make('💵 Тарифы', callback_data: 'adm:tariffs'),
                InlineKeyboardButton::make('⚙️ Настройки', callback_data: 'adm:settings'),
            )
            ->addRow(InlineKeyboardButton::make('📝 Тексты', callback_data: 'adm:texts'))
            ->addRow(InlineKeyboardButton::make('🏠 Меню пользователя', callback_data: 'nav:home'));
    }

    /** Курируемые скалярные настройки, доступные для правки из бота. */
    private const EDITABLE_SETTINGS = [
        'free_numbers' => 'Бесплатных номеров',
        'max_numbers' => 'Максимум номеров',
        'min_deposit' => 'Мин. пополнение, $',
        'min_withdraw' => 'Мин. вывод, $',
        'working_hours_start' => 'Начало работы (час)',
        'working_hours_end' => 'Конец работы (час)',
        'avg_processing_minutes' => 'Среднее время, мин',
        'premium_price' => 'Цена Премиум, $',
        'premium_plus_price' => 'Цена Премиум+, $',
        'premium_discount' => 'Скидка Премиум, %',
        'premium_plus_discount' => 'Скидка Премиум+, %',
        'referral_percent_base' => 'Базовый реф. %',
        'large_payment_alert' => 'Порог алерта пополнения, $',
    ];

    /** Курируемые тексты для правки из бота. */
    private const EDITABLE_TEXTS = [
        'welcome' => 'Приветствие',
        'info' => 'Инфо',
        'balance' => 'Баланс',
        'profile_short' => 'Профиль',
        'topup_intro' => 'Пополнение',
        'topup_manager' => 'Пополнение (менеджер)',
        'calculator' => 'Калькулятор',
        'check_base_info' => 'Проверка базы',
        'subscription_required' => 'Экран подписки',
        'premium' => 'Премиум',
    ];

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

        $kb = InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make("Подписка: {$sub}", callback_data: 'adm:toggle:subscription_required'))
            ->addRow(InlineKeyboardButton::make("Капча: {$cap}", callback_data: 'adm:toggle:captcha_enabled'));

        foreach (self::EDITABLE_SETTINGS as $key => $label) {
            $kb->addRow(InlineKeyboardButton::make($label . ': ' . Setting::get($key), callback_data: 'adm:set:' . $key));
        }
        $kb->addRow(InlineKeyboardButton::make('⬅️ В админку', callback_data: 'adm:home'));

        Screen::show($bot, "⚙️ <b>Настройки</b>\nНажмите параметр, чтобы изменить:", $kb);
    }

    public function editSetting(Nutgram $bot, string $key): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        if (! isset(self::EDITABLE_SETTINGS[$key])) {
            $bot->answerCallbackQuery();

            return;
        }
        $this->ack($bot);
        AdminInputConversation::begin($bot, data: ['setting', $key,
            self::EDITABLE_SETTINGS[$key] . " — текущее: " . Setting::get($key) . "\nВведите новое значение:"]);
    }

    public function tariffs(Nutgram $bot): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);

        $kb = InlineKeyboardMarkup::make();
        foreach (Geo::orderBy('sort')->get() as $g) {
            $kb->addRow(InlineKeyboardButton::make(
                trim(($g->flag ?? '') . ' ' . $g->code) . ' — ' . Screens::money($g->price_per_1000) . '$ / 1000',
                callback_data: 'adm:tariff:' . $g->code,
            ));
        }
        $kb->addRow(InlineKeyboardButton::make('⬅️ В админку', callback_data: 'adm:home'));

        Screen::show($bot, "💵 <b>Тарифы</b>\nНажмите ГЕО, чтобы изменить цену за 1000 номеров:", $kb);
    }

    public function editTariff(Nutgram $bot, string $code): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        $geo = Geo::where('code', $code)->first();
        AdminInputConversation::begin($bot, data: ['tariff', $code,
            "Тариф {$code} — текущий: " . ($geo ? Screens::money($geo->price_per_1000) : '?') . "\$\nВведите новую цену за 1000:"]);
    }

    public function texts(Nutgram $bot): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);

        $kb = InlineKeyboardMarkup::make();
        foreach (self::EDITABLE_TEXTS as $key => $label) {
            $kb->addRow(InlineKeyboardButton::make('📝 ' . $label, callback_data: 'adm:text:' . $key));
        }
        $kb->addRow(InlineKeyboardButton::make('⬅️ В админку', callback_data: 'adm:home'));

        Screen::show($bot, "📝 <b>Тексты бота</b>\nВыберите, что изменить:", $kb);
    }

    public function editText(Nutgram $bot, string $key): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        $current = BotText::raw($key);
        $bot->sendMessage("📝 Текущий текст «{$key}»:\n\n" . $current);
        AdminInputConversation::begin($bot, data: ['text', $key, 'Пришлите новый текст одним сообщением (плейсхолдеры {в фигурных скобках} сохраняйте):']);
    }

    public function withdrawals(Nutgram $bot): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);

        $pending = Withdrawal::where('status', Withdrawal::STATUS_PENDING)->latest()->limit(10)->get();
        $kb = InlineKeyboardMarkup::make();
        foreach ($pending as $w) {
            $kb->addRow(InlineKeyboardButton::make(
                '#' . $w->id . ' — ' . Screens::money($w->amount) . '$ (id ' . $w->botUser->telegram_id . ')',
                callback_data: 'adm:wd:' . $w->id,
            ));
        }
        $kb->addRow(InlineKeyboardButton::make('⬅️ В админку', callback_data: 'adm:home'));

        Screen::show($bot, $pending->isEmpty() ? '💸 Заявок на вывод нет.' : '💸 <b>Заявки на вывод</b> (ожидают):', $kb);
    }

    public function withdrawalShow(Nutgram $bot, string $id): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        $w = Withdrawal::find((int) $id);
        if (! $w) {
            $this->withdrawals($bot);

            return;
        }
        $text = "💸 <b>Заявка #{$w->id}</b>\n"
            . '👤 id ' . $w->botUser->telegram_id . "\n"
            . '💵 Сумма: ' . Screens::money($w->amount) . "\$\n"
            . "📥 Адрес: <code>{$w->address}</code>\nСтатус: {$w->status}";
        Screen::show($bot, $text, InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('✅ Одобрить', callback_data: 'adm:wdok:' . $w->id),
                InlineKeyboardButton::make('❌ Отклонить', callback_data: 'adm:wdno:' . $w->id),
            )
            ->addRow(InlineKeyboardButton::make('⬅️ К списку', callback_data: 'adm:withdrawals')));
    }

    public function approveWithdrawal(Nutgram $bot, string $id): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        AdminInputConversation::begin($bot, data: ['wd_approve', $id, 'Введите хэш транзакции выплаты:']);
    }

    public function rejectWithdrawal(Nutgram $bot, string $id): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        AdminInputConversation::begin($bot, data: ['wd_reject', $id, 'Введите причину отклонения:']);
    }

    public function tickets(Nutgram $bot): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);

        $open = SupportTicket::where('status', 'open')->latest()->limit(10)->get();
        $kb = InlineKeyboardMarkup::make();
        foreach ($open as $t) {
            $kb->addRow(InlineKeyboardButton::make(
                '#' . $t->id . ' — ' . $t->type . ' (id ' . $t->botUser->telegram_id . ')',
                callback_data: 'adm:tk:' . $t->id,
            ));
        }
        $kb->addRow(InlineKeyboardButton::make('⬅️ В админку', callback_data: 'adm:home'));

        Screen::show($bot, $open->isEmpty() ? '✉️ Открытых тикетов нет.' : '✉️ <b>Открытые тикеты</b>:', $kb);
    }

    public function ticketShow(Nutgram $bot, string $id): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        $t = SupportTicket::find((int) $id);
        if (! $t) {
            $this->tickets($bot);

            return;
        }
        $text = "✉️ <b>Тикет #{$t->id}</b>\n👤 id {$t->botUser->telegram_id}\nТип: {$t->type}\n\n" . ($t->message ?: '—');
        Screen::show($bot, $text, InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('💬 Ответить', callback_data: 'adm:tkreply:' . $t->id),
                InlineKeyboardButton::make('✅ Закрыть', callback_data: 'adm:tkclose:' . $t->id),
            )
            ->addRow(InlineKeyboardButton::make('⬅️ К списку', callback_data: 'adm:tickets')));
    }

    public function replyTicket(Nutgram $bot, string $id): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        $this->ack($bot);
        AdminInputConversation::begin($bot, data: ['tk_reply', $id, 'Введите ответ пользователю:']);
    }

    public function closeTicket(Nutgram $bot, string $id): void
    {
        if (! $this->guard($bot)) {
            return;
        }
        SupportTicket::where('id', (int) $id)->update(['status' => 'closed']);
        $bot->answerCallbackQuery(text: 'Тикет закрыт');
        $this->tickets($bot);
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

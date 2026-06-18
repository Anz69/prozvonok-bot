<?php

namespace App\Telegram\Support;

use App\Models\BotButton;
use App\Models\BotText;
use App\Models\BotUser;
use App\Models\Geo;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\PricingService;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Контент экранов бота (режим «одного сообщения»). Каждый метод возвращает
 * [text, keyboard]; навигация — инлайн-кнопками (nav:/act:), внизу — ⬅️ Назад / 🏠 Главная.
 *
 * @phpstan-type Screen array{text: string, keyboard: InlineKeyboardMarkup}
 */
class Screens
{
    /** Действия, открывающие диалог (а не статический экран). */
    private const CONVERSATIONS = ['check_base', 'calculator', 'topup'];

    public static function money(float|string $v): string
    {
        return number_format((float) $v, 5, '.', '');
    }

    private static function premiumLabel(BotUser $user): string
    {
        if (! $user->hasActivePremium()) {
            return '---';
        }
        $name = $user->premium_tier === 'premium_plus' ? 'Премиум+' : 'Премиум';

        return $name . ' до ' . $user->premium_until->format('d.m.Y');
    }

    private static function withdrawLabel(BotUser $user): string
    {
        return $user->canWithdraw() ? '✅' : '❌';
    }

    /**
     * Экран по ключу навигации (единый резолвер для Navigator и переотправки).
     *
     * @return Screen
     */
    public static function byKey(BotUser $user, string $key): array
    {
        return match ($key) {
            'profile' => self::profileShort($user),
            'profile_full' => self::profileFull($user),
            'hist_checks' => self::historyChecks($user),
            'hist_balance' => self::historyBalance($user),
            'balance' => self::balance($user),
            'referral' => self::referralShort($user),
            'referral_details' => self::referralDetails($user),
            'premium' => self::premium($user),
            'info' => self::info(),
            default => self::home($user),
        };
    }

    /** Главный экран: приветствие + меню (из bot_buttons, row/sort). */
    public static function home(BotUser $user): array
    {
        $text = BotText::render('welcome', [
            'name' => $user->first_name ?: ($user->username ?: 'друг'),
            'free_numbers' => Setting::get('free_numbers', 100),
            'wh_start' => Setting::get('working_hours_start', 9),
            'wh_end' => Setting::get('working_hours_end', 21),
        ]);

        $kb = InlineKeyboardMarkup::make();
        $rows = collect(BotButton::menu('main'))->groupBy('row')->sortKeys();
        foreach ($rows as $rowButtons) {
            $buttons = collect($rowButtons)->sortBy('sort')->map(function ($b) {
                $prefix = in_array($b['action'], self::CONVERSATIONS, true) ? 'act:' : 'nav:';

                return InlineKeyboardButton::make($b['label'], callback_data: $prefix . $b['action']);
            })->values()->all();
            if ($buttons) {
                $kb->addRow(...$buttons);
            }
        }

        return ['text' => $text, 'keyboard' => $kb];
    }

    public static function profileShort(BotUser $user): array
    {
        $text = BotText::render('profile_short', [
            'id' => $user->telegram_id,
            'reg_date' => $user->created_at?->format('d.m.Y'),
            'checked' => $user->numbers_checked,
            'answered' => $user->numbers_answered,
            'failed' => $user->numbers_failed,
            'deposit' => self::money($user->deposit_balance),
            'ref_balance' => self::money($user->referral_balance),
            'percent' => $user->referral_percent,
            'discount' => $user->check_discount,
            'premium' => self::premiumLabel($user),
        ]);

        $kb = InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make('🔍 Подробное досье', callback_data: 'nav:profile_full'))
            ->addRow(
                InlineKeyboardButton::make('📅 История проверок', callback_data: 'nav:hist_checks'),
                InlineKeyboardButton::make('💳 История баланса', callback_data: 'nav:hist_balance'),
            );

        return ['text' => $text, 'keyboard' => $kb];
    }

    public static function profileFull(BotUser $user): array
    {
        $lastJob = $user->checkJobs()->latest()->first();
        $lastDeposit = $user->transactions()->where('type', Transaction::TYPE_DEPOSIT)->latest()->first();
        $avgIncome = (float) $user->transactions()
            ->whereIn('type', [Transaction::TYPE_REFERRAL_COMMISSION, Transaction::TYPE_REFERRAL_BONUS])
            ->avg('amount');

        $text = BotText::render('profile_full', [
            'reg_date' => $user->created_at?->format('d.m.Y'),
            'checked' => $user->numbers_checked,
            'answered' => $user->numbers_answered,
            'failed' => $user->numbers_failed,
            'deposit' => self::money($user->deposit_balance),
            'total_deposit' => self::money($user->total_deposited),
            'ref_balance' => self::money($user->referral_balance),
            'premium' => self::premiumLabel($user),
            'percent' => $user->referral_percent,
            'discount' => $user->check_discount,
            'id' => $user->telegram_id,
            'files' => $user->files_checked,
            'last_file_count' => $lastJob?->numbers_valid ?? 0,
            'last_file_date' => $lastJob?->created_at?->format('d.m.Y') ?? '-',
            'subscription' => $user->hasActivePremium() ? 'есть' : 'нет',
            'invited' => $user->referralsCount(),
            'last_topup' => $lastDeposit?->created_at?->format('d.m.Y') ?? '-',
            'avg_income' => self::money($avgIncome),
            'payback' => round((int) Setting::get('premium_payback_numbers', 294000) / 1000),
            'support_url' => Setting::get('support_url'),
        ]);

        return ['text' => $text, 'keyboard' => InlineKeyboardMarkup::make()];
    }

    public static function historyChecks(BotUser $user): array
    {
        $jobs = $user->checkJobs()->latest()->limit(15)->get();
        $text = $jobs->isEmpty()
            ? BotText::render('history_checks_empty')
            : "📅 <b>История проверок</b>\n\n" . $jobs->map(fn ($j) => sprintf(
                '• %s — %s, %d номеров, %s',
                $j->created_at->format('d.m.Y'),
                $j->geo_code,
                $j->numbers_valid,
                $j->status,
            ))->implode("\n");

        return ['text' => $text, 'keyboard' => InlineKeyboardMarkup::make()];
    }

    public static function historyBalance(BotUser $user): array
    {
        $txns = $user->transactions()->latest()->limit(15)->get();
        $text = $txns->isEmpty()
            ? BotText::render('history_balance_empty')
            : "💳 <b>История баланса</b>\n\n" . $txns->map(fn ($t) => sprintf(
                '• %s — %s%s$ (%s)',
                $t->created_at->format('d.m.Y'),
                (float) $t->amount >= 0 ? '+' : '',
                self::money($t->amount),
                $t->type,
            ))->implode("\n");

        return ['text' => $text, 'keyboard' => InlineKeyboardMarkup::make()];
    }

    public static function balance(BotUser $user): array
    {
        $pricing = app(PricingService::class);
        $available = (float) $user->deposit_balance + (float) $user->referral_balance;
        $discount = (int) $user->check_discount;
        $byCode = Geo::active()->get()->keyBy('code');
        $numbersFor = fn (string $c) => isset($byCode[$c]) ? $pricing->affordableNumbers($available, $byCode[$c], $discount) : 0;

        $text = BotText::render('balance', [
            'deposit' => self::money($user->deposit_balance),
            'ref_balance' => self::money($user->referral_balance),
            'ru_numbers' => $numbersFor('RU'),
            'kz_numbers' => $numbersFor('KZ'),
            'by_numbers' => $numbersFor('BY'),
            'withdraw' => self::withdrawLabel($user),
        ]);

        $kb = InlineKeyboardMarkup::make();
        $kb->addRow(InlineKeyboardButton::make('💰 Пополнить', callback_data: 'act:topup'));
        if ($user->canWithdraw()) {
            $kb->addRow(InlineKeyboardButton::make('💸 Вывести', callback_data: 'act:withdraw'));
        }

        return ['text' => $text, 'keyboard' => $kb];
    }

    public static function referralShort(BotUser $user): array
    {
        $text = BotText::render('referral_short', [
            'invited' => $user->referralsCount(),
            'ref_balance' => self::money($user->referral_balance),
            'percent' => $user->referral_percent,
            'withdraw' => self::withdrawLabel($user),
            'ref_link' => $user->referralLink(),
        ]);

        return ['text' => $text, 'keyboard' => InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make('🔍 Подробнее', callback_data: 'nav:referral_details'))];
    }

    public static function referralDetails(BotUser $user): array
    {
        $history = $user->transactions()
            ->whereIn('type', [Transaction::TYPE_REFERRAL_COMMISSION, Transaction::TYPE_REFERRAL_BONUS])
            ->latest()->limit(10)->get();
        $historyText = $history->isEmpty()
            ? '• Нет пополнений'
            : $history->map(fn ($t) => '• ' . $t->created_at->format('d.m.Y') . ' — +' . self::money($t->amount) . '$')->implode("\n");

        $text = BotText::render('referral_details', [
            'invited' => $user->referralsCount(),
            'ref_balance' => self::money($user->referral_balance),
            'percent' => $user->referral_percent,
            'withdraw' => self::withdrawLabel($user),
            'ref_link' => $user->referralLink(),
            'ref_history' => $historyText,
            'support_url' => Setting::get('support_url'),
        ]);

        return ['text' => $text, 'keyboard' => InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make('📈 Запрос %', callback_data: 'act:percent'))];
    }

    public static function premium(BotUser $user): array
    {
        $text = BotText::render('premium', [
            'premium_price' => (int) Setting::get('premium_price', 250),
            'premium_discount' => Setting::get('premium_discount', 25),
            'premium_plus_price' => (int) Setting::get('premium_plus_price', 499),
            'premium_plus_discount' => Setting::get('premium_plus_discount', 35),
            'trial_deposit' => (int) Setting::get('premium_trial_deposit', 1000),
        ]);

        return ['text' => $text, 'keyboard' => InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('💎 Премиум', callback_data: 'act:prem:premium'),
                InlineKeyboardButton::make('💠 Премиум+', callback_data: 'act:prem:premium_plus'),
            )
            ->addRow(InlineKeyboardButton::make('✨ Пробный премиум', callback_data: 'act:prem_trial'))];
    }

    public static function info(): array
    {
        $text = BotText::render('info', [
            'max_numbers' => Setting::get('max_numbers', 100000),
            'wh_start' => Setting::get('working_hours_start', 9),
            'wh_end' => Setting::get('working_hours_end', 21),
            'faq_url' => Setting::get('faq_url'),
            'guide_url' => Setting::get('guide_url'),
            'improve_url' => Setting::get('improve_url'),
            'support_url' => Setting::get('support_url'),
            'channel_url' => Setting::get('channel_url'),
        ]);

        return ['text' => $text, 'keyboard' => InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make('✉️ Поддержка', callback_data: 'act:support'))];
    }
}

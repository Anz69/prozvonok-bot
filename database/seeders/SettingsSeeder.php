<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Все «настраиваемые в админке» числовые/строковые параметры (раздел 5).
 * Меняются в Filament без правки кода.
 */
class SettingsSeeder extends Seeder
{
    /** Telegram-ID админов из env ADMIN_IDS (через запятую). */
    private static function adminIds(): array
    {
        return collect(explode(',', (string) env('ADMIN_IDS', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();
    }

    public function run(): void
    {
        $settings = [
            // [key, value, type, group, label]

            // --- Рабочее время и лимиты файла ---
            ['working_hours_start', 9, 'int', 'limits', 'Начало рабочих часов (час)'],
            ['working_hours_end', 21, 'int', 'limits', 'Конец рабочих часов (час)'],
            ['max_numbers', 100000, 'int', 'limits', 'Максимум номеров за проверку'],
            ['rate_limit_per_minute', 20, 'int', 'limits', 'Лимит действий пользователя в минуту'],

            // --- Уведомления админам ---
            ['admin_chat_ids', json_encode(self::adminIds()), 'json', 'admin', 'Telegram-ID админов для алертов'],
            ['large_payment_alert', 1000, 'float', 'admin', 'Порог крупного пополнения для алерта, $'],
            ['avg_processing_minutes', 15, 'int', 'limits', 'Среднее время обработки (мин)'],
            ['allowed_formats', json_encode(['xlsx', 'txt']), 'json', 'limits', 'Разрешённые форматы файлов'],

            // --- Звонок.com: маппинг статусов сервиса → answered/no_answer (раздел 4.4, TODO уточнить ключи) ---
            ['zvonok_status_map', json_encode([
                // call status Звонок.com → наш статус (in_process не указываем — значит «ещё в процессе»)
                'compl_finished' => 'answered',   // дозвонились, сценарий завершён
                'compl_nofinished' => 'answered', // ответили, но не до конца
                'user' => 'answered',             // ответил абонент
                'attempts_exc' => 'no_answer',    // исчерпаны попытки
                'novalid_button' => 'no_answer',
                'deleted' => 'no_answer',
            ]), 'json', 'integration', 'Маппинг статусов Звонок.com (call status → answered/no_answer)'],

            // --- Акция «первые N бесплатно» ---
            ['free_numbers', 100, 'int', 'promo', 'Бесплатных номеров новому пользователю'],
            ['free_numbers_once', true, 'bool', 'promo', 'Бесплатные номера — разово на пользователя'],

            // --- Финансы ---
            ['min_deposit', 10, 'float', 'finance', 'Мин. сумма пополнения, $'],         // TODO: согласовать
            ['min_withdraw', 50, 'float', 'finance', 'Мин. сумма вывода, $'],            // TODO: согласовать
            ['charge_policy', 'all', 'string', 'finance', 'Тарификация: all|answered'], // TODO раздел 8.1
            ['payment_ttl_minutes', 30, 'int', 'finance', 'Время жизни счёта на оплату (мин)'],
            ['usdt_network', 'TRC20', 'string', 'finance', 'Сеть приёма USDT'],

            // --- Реферальная система (раздел 3.7) ---
            ['referral_percent_base', 5, 'int', 'referral', 'Базовый реф. процент'],
            ['referral_percent_boost', 10, 'int', 'referral', 'Повышенный реф. процент'],
            ['referral_boost_deposit', 10000, 'float', 'referral', 'Депозит для повышения %, $'],
            ['referral_boost_referrals', 10, 'int', 'referral', 'Рефералов для повышения %'],
            ['referral_first_deposit_bonus', 10, 'float', 'referral', 'Бонус за реферала, $'],
            ['referral_first_deposit_min', 100, 'float', 'referral', 'Мин. депозит реферала для бонуса, $'],
            ['referral_first_topup_extra_percent', 10, 'int', 'referral', '+% к первому пополнению за приглашение'],
            ['withdraw_min_referrals', 10, 'int', 'referral', 'Рефералов для разблокировки вывода'],
            ['withdraw_min_deposit', 5000, 'float', 'referral', 'Депозит для разблокировки вывода, $'],

            // --- Премиум (раздел 3.10) ---
            ['premium_price', 250, 'float', 'premium', 'Цена Премиум, $/мес'],
            ['premium_plus_price', 499, 'float', 'premium', 'Цена Премиум+, $/мес'],
            ['premium_discount', 25, 'int', 'premium', 'Скидка Премиум, %'],
            ['premium_plus_discount', 35, 'int', 'premium', 'Скидка Премиум+, %'],
            ['premium_days', 30, 'int', 'premium', 'Срок подписки, дней'],
            ['premium_trial_deposit', 1000, 'float', 'premium', 'Депозит для пробного Премиума, $'],
            ['premium_auto_renew_default', false, 'bool', 'premium', 'Авто-продление по умолчанию'],
            ['premium_payback_numbers', 294000, 'int', 'premium', 'Окупаемость Премиума (≈ номеров)'],

            // --- Онбординг ---
            ['subscription_required', true, 'bool', 'onboarding', 'Требовать подписку на канал'],
            ['captcha_enabled', true, 'bool', 'onboarding', 'Включить капчу'],
            ['captcha_target', '🍍', 'string', 'onboarding', 'Целевой эмодзи капчи'],
            ['captcha_emojis', json_encode(['🍍', '🍎', '🍌', '🍇', '🍒', '🍓', '🥝', '🍉']), 'json', 'onboarding', 'Набор эмодзи капчи'],

            // --- Ссылки ---
            ['support_url', env('MANAGER_URL', 'https://t.me/SoulGoodmanSupp'), 'string', 'links', 'Менеджер/поддержка'],
            ['channel_url', env('CHANNEL_URL', 'https://t.me/SoulNewsBots'), 'string', 'links', 'Канал'],
            ['faq_url', 'https://telegra.ph/CHasto-zadavaemye-voprosy-10-07-8', 'string', 'links', 'FAQ'],
            ['guide_url', 'https://telegra.ph/Rukovodstvo-po-botu-08-23-2', 'string', 'links', 'Как пользоваться'],
            ['improve_url', 'https://telegra.ph/Ot-chego-zavisit-rezultat-proverki-i-kak-uluchshit-vash-dozvon-03-26', 'string', 'links', 'Как улучшить дозвон'],
        ];

        foreach ($settings as [$key, $value, $type, $group, $label]) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => $type,
                    'group' => $group,
                    'label' => $label,
                ],
            );
        }
    }
}

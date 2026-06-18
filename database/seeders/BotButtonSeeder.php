<?php

namespace Database\Seeders;

use App\Models\BotButton;
use Illuminate\Database\Seeder;

/**
 * Нижнее (reply) меню бота — раздел 3.2. Подписи/порядок/видимость правятся в админке.
 */
class BotButtonSeeder extends Seeder
{
    public function run(): void
    {
        // Компактная раскладка (row = ряд): главное действие сверху, остальное сгруппировано.
        // ▶ 📂 Проверить базу            (во всю ширину — главное действие)
        // ▶ 👤 Профиль · 🧮 Баланс · 💰 Пополнить
        // ▶ 💎 Премиум · 📣 Реф. система
        // ▶ 🧮 Калькулятор · ℹ️ Инфо
        $buttons = [
            ['key' => 'check_base', 'label' => '🔍 Проверить базу', 'action' => 'check_base', 'row' => 1, 'sort' => 1],
            ['key' => 'profile',    'label' => '👤 Профиль',        'action' => 'profile',    'row' => 2, 'sort' => 1],
            ['key' => 'balance',    'label' => '💼 Баланс',         'action' => 'balance',    'row' => 2, 'sort' => 2],
            ['key' => 'topup',      'label' => '💳 Пополнить',      'action' => 'topup',      'row' => 2, 'sort' => 3],
            ['key' => 'premium',    'label' => '💎 Премиум',        'action' => 'premium',    'row' => 3, 'sort' => 1],
            ['key' => 'referral',   'label' => '🤝 Партнёрам',      'action' => 'referral',   'row' => 3, 'sort' => 2],
            ['key' => 'calculator', 'label' => '🧮 Калькулятор',    'action' => 'calculator', 'row' => 4, 'sort' => 1],
            ['key' => 'info',       'label' => '📘 Инфо',           'action' => 'info',       'row' => 4, 'sort' => 2],
        ];

        foreach ($buttons as $b) {
            BotButton::updateOrCreate(
                ['key' => $b['key']],
                array_merge($b, ['menu' => 'main', 'is_visible' => true]),
            );
        }
    }
}

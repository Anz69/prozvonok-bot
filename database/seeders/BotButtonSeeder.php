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
        // Понятная раскладка для обычных пользователей: главное действие крупно сверху,
        // деньги вместе, «как это работает» рядом с расчётом.
        // ▶ 📂 Проверить базу               (во всю ширину — главное действие)
        // ▶ 💳 Пополнить счёт · 💼 Мой баланс
        // ▶ 🧮 Рассчитать стоимость · ❓ Как это работает
        // ▶ 💎 Премиум · 🤝 Пригласить друзей
        // ▶ 👤 Профиль
        $buttons = [
            ['key' => 'check_base', 'label' => '📂 Проверить базу',        'action' => 'check_base', 'row' => 1, 'sort' => 1],
            ['key' => 'topup',      'label' => '💳 Пополнить счёт',        'action' => 'topup',      'row' => 2, 'sort' => 1],
            ['key' => 'balance',    'label' => '💼 Мой баланс',            'action' => 'balance',    'row' => 2, 'sort' => 2],
            ['key' => 'calculator', 'label' => '🧮 Рассчитать стоимость',  'action' => 'calculator', 'row' => 3, 'sort' => 1],
            ['key' => 'info',       'label' => '❓ Как это работает',       'action' => 'info',       'row' => 3, 'sort' => 2],
            ['key' => 'premium',    'label' => '💎 Премиум',               'action' => 'premium',    'row' => 4, 'sort' => 1],
            ['key' => 'referral',   'label' => '🤝 Пригласить друзей',     'action' => 'referral',   'row' => 4, 'sort' => 2],
            ['key' => 'profile',    'label' => '👤 Профиль',               'action' => 'profile',    'row' => 5, 'sort' => 1],
        ];

        foreach ($buttons as $b) {
            // firstOrCreate: не затираем правки кнопок из админки при повторном --seed
            BotButton::firstOrCreate(
                ['key' => $b['key']],
                array_merge($b, ['menu' => 'main', 'is_visible' => true]),
            );
        }
    }
}

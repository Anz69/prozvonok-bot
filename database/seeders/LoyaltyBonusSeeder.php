<?php

namespace Database\Seeders;

use App\Models\LoyaltyBonus;
use Illuminate\Database\Seeder;

class LoyaltyBonusSeeder extends Seeder
{
    public function run(): void
    {
        // Бонусы лояльности при оплате USDT TRC-20 (раздел 3.4)
        $bonuses = [
            ['threshold' => 500,  'bonus' => 50,   'sort' => 1],
            ['threshold' => 1000, 'bonus' => 100,  'sort' => 2],
            ['threshold' => 2000, 'bonus' => 200,  'sort' => 3],
            ['threshold' => 5000, 'bonus' => 1000, 'sort' => 4],
        ];

        foreach ($bonuses as $b) {
            LoyaltyBonus::updateOrCreate(['threshold' => $b['threshold']], $b);
        }
    }
}

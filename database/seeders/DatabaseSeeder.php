<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            GeoSeeder::class,
            LoyaltyBonusSeeder::class,
            SettingsSeeder::class,
            BotButtonSeeder::class,
            RequiredChannelSeeder::class,
            BotTextSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}

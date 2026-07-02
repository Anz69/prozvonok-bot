<?php

namespace Database\Seeders;

use App\Models\Geo;
use Illuminate\Database\Seeder;

class GeoSeeder extends Seeder
{
    public function run(): void
    {
        $geos = [
            ['code' => 'RU', 'name' => 'Россия',   'flag' => '🇷🇺', 'price_per_1000' => 9,  'sort' => 1],
            ['code' => 'KZ', 'name' => 'Казахстан', 'flag' => '🇰🇿', 'price_per_1000' => 7,  'sort' => 2],
            ['code' => 'BY', 'name' => 'Беларусь',  'flag' => '🇧🇾', 'price_per_1000' => 11, 'sort' => 3],
        ];

        foreach ($geos as $geo) {
            // firstOrCreate: сеем дефолты только на пустую БД, не затираем правки цен из админки
            Geo::firstOrCreate(['code' => $geo['code']], $geo);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\RequiredChannel;
use Illuminate\Database\Seeder;

class RequiredChannelSeeder extends Seeder
{
    public function run(): void
    {
        // Канал обязательной подписки — из env (бот должен быть его админом).
        $chatId = (string) env('REQUIRED_CHANNEL', '@SoulNewsBots');
        $url = (string) env('CHANNEL_URL', 'https://t.me/' . ltrim($chatId, '@'));

        RequiredChannel::updateOrCreate(
            ['id' => 1],
            [
                'chat_id' => $chatId,
                'title' => 'Канал',
                'url' => $url,
                'is_active' => true,
                'sort' => 1,
            ],
        );
    }
}

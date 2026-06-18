<?php

namespace App\Telegram\Support;

use App\Models\Setting;

/**
 * Доступ к админке внутри бота. Список владельцев — настройка admin_chat_ids (json).
 */
class Admin
{
    /** @return list<int> */
    public static function ids(): array
    {
        return array_map('intval', (array) Setting::get('admin_chat_ids', []));
    }

    public static function is(?int $telegramId): bool
    {
        return $telegramId !== null && in_array($telegramId, self::ids(), true);
    }
}

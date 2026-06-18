<?php

namespace App\Telegram\Conversations;

use App\Models\Geo;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Инлайн-клавиатура выбора ГЕО для конверсаций (калькулятор / проверка базы).
 */
class GeoKeyboard
{
    public static function make(string $prefix): InlineKeyboardMarkup
    {
        $kb = InlineKeyboardMarkup::make();

        $buttons = Geo::active()->orderBy('sort')->get()
            ->map(fn (Geo $g) => InlineKeyboardButton::make(
                $g->flag . ' ' . $g->code,
                callback_data: $prefix . $g->code,
            ))->all();

        if ($buttons) {
            $kb->addRow(...$buttons);
        }

        return $kb;
    }

    /**
     * Извлечь код ГЕО из callback-данных вида "<prefix>RU". null — если не подходит.
     */
    public static function extract(Nutgram $bot, string $prefix): ?string
    {
        $data = $bot->callbackQuery()?->data;
        if ($data === null || ! str_starts_with($data, $prefix)) {
            return null;
        }

        $code = substr($data, strlen($prefix));

        return Geo::active()->where('code', $code)->exists() ? $code : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BotButton extends Model
{
    protected $fillable = ['key', 'label', 'menu', 'row', 'action', 'payload', 'sort', 'is_visible'];

    protected $casts = ['is_visible' => 'boolean'];

    public const CACHE_KEY = 'bot_buttons.all';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Видимые кнопки меню в порядке сортировки.
     *
     * @return \Illuminate\Support\Collection<int, BotButton>
     */
    public static function menu(string $menu = 'main')
    {
        $all = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->orderBy('sort')->get()->toArray(),
        );

        return collect($all)
            ->where('menu', $menu)
            ->where('is_visible', true)
            ->values();
    }
}

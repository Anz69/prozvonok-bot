<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Тексты бота с плейсхолдерами. Все строки интерфейса — отсюда (раздел 5.1 ТЗ).
 */
class BotText extends Model
{
    protected $fillable = ['key', 'content', 'description', 'placeholders'];

    protected $casts = ['placeholders' => 'array'];

    public const CACHE_KEY = 'bot_texts.all';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Сырой текст по ключу (или fallback).
     */
    public static function raw(string $key, string $default = ''): string
    {
        $all = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('content', 'key')->all(),
        );

        return $all[$key] ?? $default;
    }

    /**
     * Текст с подстановкой плейсхолдеров: {name}, {balance}, ...
     *
     * @param  array<string, mixed>  $placeholders
     */
    public static function render(string $key, array $placeholders = [], string $default = ''): string
    {
        $text = static::raw($key, $default);

        foreach ($placeholders as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }

        return $text;
    }
}

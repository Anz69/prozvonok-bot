<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Глобальные параметры бота. Всё, что в ТЗ «настраивается в админке»,
 * читается отсюда через Setting::get(), а не хардкодится.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    public const CACHE_KEY = 'settings.all';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Значение настройки с приведением типа. Кэшируется целиком.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->get(['key', 'value', 'type'])
                ->mapWithKeys(fn ($s) => [$s->key => ['value' => $s->value, 'type' => $s->type]])
                ->all(),
        );

        if (! isset($all[$key])) {
            return $default;
        }

        ['value' => $value, 'type' => $type] = $all[$key];

        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }

    public static function put(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
                'group' => $group,
            ],
        );
    }
}

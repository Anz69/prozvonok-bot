<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyBonus extends Model
{
    protected $fillable = ['threshold', 'bonus', 'is_active', 'sort'];

    protected $casts = [
        'threshold' => 'decimal:5',
        'bonus' => 'decimal:5',
        'is_active' => 'boolean',
    ];

    /**
     * Бонус лояльности для суммы пополнения: берём наибольший достигнутый порог.
     */
    public static function forAmount(float $amount): float
    {
        $row = static::query()
            ->where('is_active', true)
            ->where('threshold', '<=', $amount)
            ->orderByDesc('threshold')
            ->first();

        return $row ? (float) $row->bonus : 0.0;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckJob extends Model
{
    public const STATUS_PENDING = 'pending';      // создаётся / валидация
    public const STATUS_QUEUED = 'queued';        // оплачено, ждёт постановки
    public const STATUS_SCHEDULED = 'scheduled';  // отложено до рабочих часов
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'bot_user_id', 'geo_code', 'numbers_total', 'numbers_valid', 'cost',
        'discount_percent', 'free_applied', 'status', 'input_path', 'output_path',
        'summary', 'zvonok_campaign_id', 'queued_at', 'scheduled_for', 'completed_at',
    ];

    protected $casts = [
        'cost' => 'decimal:5',
        'summary' => 'array',
        'queued_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    public function numbers(): HasMany
    {
        return $this->hasMany(CheckNumber::class);
    }

    public function geo(): BelongsTo
    {
        return $this->belongsTo(Geo::class, 'geo_code', 'code');
    }
}

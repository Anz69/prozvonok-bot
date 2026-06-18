<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_UNDERPAID = 'underpaid';
    public const STATUS_OVERPAID = 'overpaid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const METHOD_USDT = 'usdt';
    public const METHOD_MANAGER = 'manager';

    protected $fillable = [
        'bot_user_id', 'uid', 'method', 'address', 'network',
        'amount_expected', 'amount_received', 'bonus_amount',
        'status', 'tx_hash', 'confirmed_by', 'confirmations', 'expires_at', 'paid_at',
    ];

    protected $casts = [
        'amount_expected' => 'decimal:5',
        'amount_received' => 'decimal:5',
        'bonus_amount' => 'decimal:5',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

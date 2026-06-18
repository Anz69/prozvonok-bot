<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_CHARGE = 'charge';
    public const TYPE_BONUS_LOYALTY = 'bonus_loyalty';
    public const TYPE_REFERRAL_COMMISSION = 'referral_commission';
    public const TYPE_REFERRAL_BONUS = 'referral_bonus';
    public const TYPE_WITHDRAW = 'withdraw';
    public const TYPE_PREMIUM_CHARGE = 'premium_charge';
    public const TYPE_ADMIN_ADJUST = 'admin_adjust';
    public const TYPE_REFUND = 'refund';

    public const WALLET_DEPOSIT = 'deposit';
    public const WALLET_REFERRAL = 'referral';

    protected $fillable = [
        'bot_user_id', 'type', 'wallet', 'amount', 'balance_after',
        'description', 'meta', 'sourceable_type', 'sourceable_id',
    ];

    protected $casts = [
        'amount' => 'decimal:5',
        'balance_after' => 'decimal:5',
        'meta' => 'array',
    ];

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = ['referrer_id', 'referred_id', 'first_deposit_bonus_paid', 'first_topup_bonus_paid'];

    protected $casts = [
        'first_deposit_bonus_paid' => 'boolean',
        'first_topup_bonus_paid' => 'boolean',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(BotUser::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(BotUser::class, 'referred_id');
    }
}

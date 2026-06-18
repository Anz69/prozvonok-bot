<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    public const TYPE_GENERAL = 'general';
    public const TYPE_PERCENT_REQUEST = 'percent_request';
    public const TYPE_HISTORY_REQUEST = 'history_request';
    public const TYPE_WITHDRAW_QUESTION = 'withdraw_question';
    public const TYPE_TRIAL_PREMIUM = 'trial_premium';

    protected $fillable = ['bot_user_id', 'type', 'message', 'status'];

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckNumber extends Model
{
    public const STATUS_ANSWERED = 'answered'; // 🟢 дозвон
    public const STATUS_NO_ANSWER = 'no_answer'; // 🔴 НДЗ

    protected $fillable = [
        'check_job_id', 'phone', 'status', 'operator', 'mnp_operator',
        'is_active', 'timezone', 'last_status', 'transcription', 'raw',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'raw' => 'array',
    ];

    public function checkJob(): BelongsTo
    {
        return $this->belongsTo(CheckJob::class);
    }
}

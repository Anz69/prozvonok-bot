<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZvonokCallback extends Model
{
    protected $fillable = ['check_job_id', 'check_number_id', 'payload', 'processed'];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
    ];

    public function checkJob(): BelongsTo
    {
        return $this->belongsTo(CheckJob::class);
    }

    public function checkNumber(): BelongsTo
    {
        return $this->belongsTo(CheckNumber::class);
    }
}

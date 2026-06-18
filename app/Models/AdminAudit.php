<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminAudit extends Model
{
    protected $fillable = ['user_id', 'action', 'auditable_type', 'auditable_id', 'changes', 'ip'];

    protected $casts = ['changes' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function log(string $action, ?Model $subject = null, array $changes = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'changes' => $changes ?: null,
            'ip' => request()->ip(),
        ]);
    }
}

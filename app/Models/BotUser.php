<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Клиент бота (Telegram-пользователь). Отдельно от админских `users` (Filament).
 */
class BotUser extends Model
{
    protected $fillable = [
        'telegram_id', 'username', 'first_name', 'last_name', 'language_code', 'timezone',
        'deposit_balance', 'referral_balance', 'total_deposited',
        'referral_percent', 'check_discount', 'referrer_id',
        'premium_tier', 'premium_until', 'premium_auto_renew', 'withdraw_unlocked',
        'is_subscribed', 'passed_captcha', 'used_free_numbers',
        'numbers_checked', 'numbers_answered', 'numbers_failed', 'files_checked',
        'state', 'is_banned',
    ];

    protected $casts = [
        'deposit_balance' => 'decimal:5',
        'referral_balance' => 'decimal:5',
        'total_deposited' => 'decimal:5',
        'premium_until' => 'datetime',
        'premium_auto_renew' => 'boolean',
        'withdraw_unlocked' => 'boolean',
        'is_subscribed' => 'boolean',
        'passed_captcha' => 'boolean',
        'used_free_numbers' => 'boolean',
        'is_banned' => 'boolean',
        'state' => 'array',
    ];

    // --- Связи ---

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(BotUser::class, 'referrer_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(BotUser::class, 'referrer_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function checkJobs(): HasMany
    {
        return $this->hasMany(CheckJob::class);
    }

    // --- Хелперы ---

    public function displayName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''))
            ?: ($this->username ? '@' . $this->username : (string) $this->telegram_id);
    }

    public function hasActivePremium(): bool
    {
        return $this->premium_tier !== null
            && $this->premium_until !== null
            && $this->premium_until->isFuture();
    }

    public function referralsCount(): int
    {
        return $this->referrals()->count();
    }

    /**
     * Право на вывод реф.баланса (раздел 3.4a):
     * активный премиум ИЛИ 10+ рефералов ИЛИ депозит от порога — ИЛИ ручная разблокировка.
     */
    public function canWithdraw(): bool
    {
        if ($this->withdraw_unlocked || $this->hasActivePremium()) {
            return true;
        }

        $minRefs = (int) Setting::get('withdraw_min_referrals', 10);
        $minDeposit = (float) Setting::get('withdraw_min_deposit', 5000);

        return $this->referralsCount() >= $minRefs
            || (float) $this->total_deposited >= $minDeposit;
    }

    public function referralLink(): string
    {
        $username = config('dozvon.bot_username');

        return "https://t.me/{$username}?start={$this->telegram_id}";
    }
}

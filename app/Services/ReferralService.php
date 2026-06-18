<?php

namespace App\Services;

use App\Models\BotUser;
use App\Models\Referral;
use App\Models\Setting;
use App\Models\Transaction;
use App\Telegram\Support\Notifier;
use App\Telegram\Support\Screens;
use Illuminate\Support\Facades\DB;

/**
 * Реферальная логика (раздел 3.7): привязка, комиссия с пополнений,
 * бонус за реферала с депозитом от порога.
 */
class ReferralService
{
    public function __construct(
        private readonly BalanceService $balance,
        private readonly Notifier $notifier,
    ) {
    }

    /**
     * Привязать пригласившего по его telegram_id. Только если ещё не привязан и это не сам пользователь.
     */
    public function bind(BotUser $user, int $referrerTelegramId): void
    {
        if ($user->referrer_id !== null || $referrerTelegramId === (int) $user->telegram_id) {
            return;
        }

        $referrer = BotUser::where('telegram_id', $referrerTelegramId)->first();
        if (! $referrer) {
            return;
        }

        DB::transaction(function () use ($user, $referrer) {
            $user->referrer_id = $referrer->id;
            $user->save();

            Referral::firstOrCreate(
                ['referred_id' => $user->id],
                ['referrer_id' => $referrer->id],
            );
        });
    }

    /**
     * Начисления пригласившему при пополнении реферала (раздел 3.7):
     *  - процент с пополнения (реф.процент пригласившего);
     *  - разовый бонус за реферала с депозитом от порога.
     */
    public function onReferredDeposit(BotUser $user, float $amount): void
    {
        $referrer = $user->referrer;
        if (! $referrer) {
            return;
        }

        // Процент с пополнения
        $percent = (int) ($referrer->referral_percent ?: Setting::get('referral_percent_base', 5));
        $commission = round($amount * $percent / 100, 5);
        if ($commission > 0) {
            $this->balance->credit(
                $referrer,
                $commission,
                Transaction::TYPE_REFERRAL_COMMISSION,
                Transaction::WALLET_REFERRAL,
                "Комиссия {$percent}% с пополнения реферала #{$user->telegram_id}",
                $user,
            );
            $this->notifier->notify(
                $referrer,
                "💸 Реферальный доход: <b>+" . Screens::money($commission) . "\$</b> ({$percent}% с пополнения партнёра).\n"
                . 'Реф. баланс: ' . Screens::money($referrer->fresh()->referral_balance) . '$',
            );
        }

        // Разовый бонус за реферала с депозитом от порога
        $minDeposit = (float) Setting::get('referral_first_deposit_min', 100);
        $bonus = (float) Setting::get('referral_first_deposit_bonus', 10);
        $referral = Referral::where('referred_id', $user->id)->first();

        if ($referral && ! $referral->first_deposit_bonus_paid
            && (float) $user->total_deposited >= $minDeposit && $bonus > 0) {
            $this->balance->credit(
                $referrer,
                $bonus,
                Transaction::TYPE_REFERRAL_BONUS,
                Transaction::WALLET_REFERRAL,
                "Бонус за реферала #{$user->telegram_id} (депозит от {$minDeposit}\$)",
                $user,
            );
            $referral->update(['first_deposit_bonus_paid' => true]);
        }

        // +% приведённому пользователю к его первому пополнению (A.1) — разовый acquisition-бонус
        $extraPercent = (int) Setting::get('referral_first_topup_extra_percent', 10);
        if ($referral && ! $referral->first_topup_bonus_paid && $extraPercent > 0) {
            $extra = round($amount * $extraPercent / 100, 5);
            if ($extra > 0) {
                $this->balance->credit(
                    $user,
                    $extra,
                    Transaction::TYPE_BONUS_LOYALTY,
                    Transaction::WALLET_DEPOSIT,
                    "Бонус +{$extraPercent}% к первому пополнению (по реф. ссылке)",
                    $user,
                );
            }
            $referral->update(['first_topup_bonus_paid' => true]);
        }
    }
}

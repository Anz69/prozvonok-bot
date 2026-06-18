<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\BotUser;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\CarbonImmutable;

/**
 * Премиум/Премиум+ (раздел 3.4b, 3.10): активация с депозита, скидка, срок, деактивация.
 */
class PremiumService
{
    public const TIER_PREMIUM = 'premium';
    public const TIER_PREMIUM_PLUS = 'premium_plus';

    public function __construct(private readonly BalanceService $balance)
    {
    }

    public function price(string $tier): float
    {
        return $tier === self::TIER_PREMIUM_PLUS
            ? (float) Setting::get('premium_plus_price', 499)
            : (float) Setting::get('premium_price', 250);
    }

    public function discount(string $tier): int
    {
        return $tier === self::TIER_PREMIUM_PLUS
            ? (int) Setting::get('premium_plus_discount', 35)
            : (int) Setting::get('premium_discount', 25);
    }

    /**
     * Активировать подписку, списав стоимость с депозита.
     *
     * @throws InsufficientBalanceException — если депозита не хватает
     */
    public function activate(BotUser $user, string $tier): CarbonImmutable
    {
        $price = $this->price($tier);

        if (! $this->balance->canAfford($user, $price)) {
            throw new InsufficientBalanceException('Недостаточно депозита для активации премиума');
        }

        $days = (int) Setting::get('premium_days', 30);

        // продление от текущей даты окончания, если подписка ещё активна
        $base = $user->hasActivePremium() && $user->premium_until
            ? CarbonImmutable::parse($user->premium_until)
            : CarbonImmutable::now();
        $until = $base->addDays($days);

        $this->balance->debit(
            $user,
            $price,
            Transaction::TYPE_PREMIUM_CHARGE,
            Transaction::WALLET_DEPOSIT,
            ($tier === self::TIER_PREMIUM_PLUS ? 'Премиум+' : 'Премиум') . " на {$days} дн.",
        );

        $user->update([
            'premium_tier' => $tier,
            'premium_until' => $until,
            'check_discount' => $this->discount($tier),
        ]);

        return $until;
    }

    /**
     * Снять истёкшую подписку (скидку и право вывода через премиум).
     */
    public function deactivate(BotUser $user): void
    {
        $user->update([
            'premium_tier' => null,
            'check_discount' => 0,
        ]);
    }
}

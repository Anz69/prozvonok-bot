<?php

namespace App\Services;

use App\Models\BotUser;
use App\Models\Geo;
use App\Models\Setting;

/**
 * Расчёт стоимости проверки (раздел 3.3.4): (номеров / 1000) × тариф ГЕО,
 * с учётом скидки премиума и акции «первые N номеров бесплатно».
 */
class PricingService
{
    /**
     * @return array{count:int, free:int, billable:int, discount:int, price_per_1000:float, cost:float}
     */
    public function quote(int $count, Geo $geo, int $discountPercent = 0, int $freeNumbers = 0): array
    {
        $free = max(0, min($freeNumbers, $count));
        $billable = max(0, $count - $free);

        $price = (float) $geo->price_per_1000;
        $gross = ($billable / 1000) * $price;
        $cost = round($gross * (1 - $discountPercent / 100), 5);

        return [
            'count' => $count,
            'free' => $free,
            'billable' => $billable,
            'discount' => $discountPercent,
            'price_per_1000' => $price,
            'cost' => $cost,
        ];
    }

    /**
     * Котировка для конкретного пользователя: учитывает скидку премиума
     * и доступную акцию бесплатных номеров (разово).
     *
     * @return array{count:int, free:int, billable:int, discount:int, price_per_1000:float, cost:float}
     */
    public function quoteForUser(BotUser $user, int $count, Geo $geo): array
    {
        $discount = (int) $user->check_discount;

        $freeNumbers = 0;
        if (! $user->used_free_numbers) {
            $freeNumbers = (int) Setting::get('free_numbers', 0);
        }

        return $this->quote($count, $geo, $discount, $freeNumbers);
    }

    /**
     * Сколько номеров можно проверить на сумму при тарифе ГЕО (для экрана «Баланс»).
     */
    public function affordableNumbers(float $amount, Geo $geo, int $discountPercent = 0): int
    {
        $price = (float) $geo->price_per_1000;
        if ($price <= 0) {
            return 0;
        }

        $effective = $price * (1 - $discountPercent / 100);
        if ($effective <= 0) {
            return 0;
        }

        return (int) floor($amount / $effective * 1000);
    }
}

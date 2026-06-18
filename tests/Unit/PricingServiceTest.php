<?php

namespace Tests\Unit;

use App\Models\Geo;
use App\Services\PricingService;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    public function test_quote_applies_free_numbers_and_discount(): void
    {
        $geo = new Geo(['code' => 'RU', 'price_per_1000' => 9]);

        // 2500 номеров, 100 бесплатно, 25% скидка → 2400/1000 * 9 * 0.75 = 16.2
        $quote = (new PricingService())->quote(2500, $geo, discountPercent: 25, freeNumbers: 100);

        $this->assertSame(100, $quote['free']);
        $this->assertSame(2400, $quote['billable']);
        $this->assertSame(16.2, $quote['cost']);
    }

    public function test_affordable_numbers(): void
    {
        $geo = new Geo(['code' => 'RU', 'price_per_1000' => 9]);

        $this->assertSame(1000, (new PricingService())->affordableNumbers(9, $geo));
        $this->assertSame(0, (new PricingService())->affordableNumbers(0, $geo));
    }
}

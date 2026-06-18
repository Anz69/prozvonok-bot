<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientBalanceException;
use App\Models\BotUser;
use App\Services\PremiumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_charges_deposit_and_applies_discount(): void
    {
        $user = BotUser::create(['telegram_id' => 1, 'deposit_balance' => 300]);

        $until = app(PremiumService::class)->activate($user, PremiumService::TIER_PREMIUM);

        $user->refresh();
        $this->assertSame('premium', $user->premium_tier);
        $this->assertSame(25, $user->check_discount);
        $this->assertSame('50.00000', $user->deposit_balance); // 300 − 250
        $this->assertTrue($until->isFuture());
        $this->assertTrue($user->hasActivePremium());
    }

    public function test_activation_without_deposit_throws(): void
    {
        $user = BotUser::create(['telegram_id' => 2, 'deposit_balance' => 10]);

        $this->expectException(InsufficientBalanceException::class);
        app(PremiumService::class)->activate($user, PremiumService::TIER_PREMIUM);
    }

    public function test_deactivate_clears_discount(): void
    {
        $user = BotUser::create([
            'telegram_id' => 3,
            'premium_tier' => 'premium',
            'premium_until' => now()->subDay(),
            'check_discount' => 25,
        ]);

        app(PremiumService::class)->deactivate($user);

        $user->refresh();
        $this->assertNull($user->premium_tier);
        $this->assertSame(0, $user->check_discount);
    }
}

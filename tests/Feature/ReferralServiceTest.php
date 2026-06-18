<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bind_links_referrer_once(): void
    {
        $referrer = BotUser::create(['telegram_id' => 100]);
        $user = BotUser::create(['telegram_id' => 200]);

        $service = app(ReferralService::class);
        $service->bind($user, 100);

        $this->assertSame($referrer->id, $user->fresh()->referrer_id);
        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_id' => $user->id,
        ]);

        // повторная привязка к другому — игнорируется
        $service->bind($user->fresh(), 999);
        $this->assertSame($referrer->id, $user->fresh()->referrer_id);
    }

    public function test_commission_and_first_deposit_bonus_credited(): void
    {
        $referrer = BotUser::create(['telegram_id' => 100, 'referral_percent' => 5]);
        $user = BotUser::create(['telegram_id' => 200, 'total_deposited' => 150]);

        $service = app(ReferralService::class);
        $service->bind($user, 100);

        // пополнение реферала на 150$: комиссия 5% (7.5) + бонус за депозит от 100$ (10) = 17.5
        $service->onReferredDeposit($user->fresh(), 150);

        $this->assertSame('17.50000', $referrer->fresh()->referral_balance);
    }
}

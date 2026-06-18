<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralFirstTopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_referred_user_gets_extra_percent_on_first_topup_once(): void
    {
        $referrer = BotUser::create(['telegram_id' => 100]);
        $user = BotUser::create(['telegram_id' => 200]);

        $service = app(ReferralService::class);
        $service->bind($user, 100);

        // Первое пополнение реферала на 100$: +10% (10$) ему на депозит
        $service->onReferredDeposit($user->fresh(), 100);
        $this->assertSame('10.00000', $user->fresh()->deposit_balance);

        // Повторное пополнение — бонус не начисляется снова (разовый)
        $service->onReferredDeposit($user->fresh(), 100);
        $this->assertSame('10.00000', $user->fresh()->deposit_balance);
    }
}

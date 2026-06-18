<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Models\LoyaltyBonus;
use App\Models\Payment;
use App\Services\Usdt\FakeUsdtWatcher;
use App\Services\Usdt\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PaymentCreditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('dozvon.usdt.wallet', 'TWALLET');
        config()->set('dozvon.usdt.confirmations', 1);
        Cache::flush();
    }

    public function test_payment_credited_once_with_loyalty_bonus_and_is_idempotent(): void
    {
        LoyaltyBonus::create(['threshold' => 100, 'bonus' => 10, 'is_active' => true]);

        $user = BotUser::create(['telegram_id' => 1]);
        $payment = Payment::create([
            'bot_user_id' => $user->id,
            'uid' => 'INV-TEST',
            'address' => 'TWALLET',
            'network' => 'TRC20',
            'amount_expected' => 100,
            'status' => Payment::STATUS_PENDING,
            'expires_at' => now()->addMinutes(30),
        ]);

        FakeUsdtWatcher::push('0xabc', 'TWALLET', 100.0, confirmations: 5);

        $credited = app(PaymentService::class)->pollAll();
        $this->assertSame(1, $credited);

        $user->refresh();
        $this->assertSame('110.00000', $user->deposit_balance); // 100 + бонус 10
        $payment->refresh();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertSame('0xabc', $payment->tx_hash);

        // Повторный опрос той же транзакции — без двойного зачисления (идемпотентность по tx_hash)
        $this->assertSame(0, app(PaymentService::class)->pollAll());
        $this->assertSame('110.00000', $user->fresh()->deposit_balance);
    }
}

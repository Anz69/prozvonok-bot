<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientBalanceException;
use App\Models\BotUser;
use App\Models\Transaction;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_and_debit_record_transactions_and_update_balance(): void
    {
        $user = BotUser::create(['telegram_id' => 1]);
        $service = new BalanceService();

        $service->credit($user, 100, Transaction::TYPE_DEPOSIT);
        $this->assertSame('100.00000', $user->fresh()->deposit_balance);
        $this->assertSame('100.00000', $user->fresh()->total_deposited); // общий депозит копится

        $service->debit($user, 30, Transaction::TYPE_CHARGE);
        $this->assertSame('70.00000', $user->fresh()->deposit_balance);

        $this->assertDatabaseHas('transactions', [
            'bot_user_id' => $user->id,
            'type' => Transaction::TYPE_CHARGE,
            'amount' => -30,
            'balance_after' => 70,
        ]);
    }

    public function test_debit_beyond_balance_throws(): void
    {
        $user = BotUser::create(['telegram_id' => 2]);

        $this->expectException(InsufficientBalanceException::class);
        (new BalanceService())->debit($user, 5, Transaction::TYPE_CHARGE);
    }
}

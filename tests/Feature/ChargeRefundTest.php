<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Models\CheckJob;
use App\Models\CheckNumber;
use App\Models\Setting;
use App\Services\Zvonok\ResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChargeRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_answered_policy_refunds_share_for_no_answer(): void
    {
        Storage::fake('local');
        Setting::put('charge_policy', 'answered');

        $user = BotUser::create(['telegram_id' => 1, 'deposit_balance' => 0]);
        $job = CheckJob::create([
            'bot_user_id' => $user->id,
            'geo_code' => 'RU',
            'numbers_total' => 10,
            'numbers_valid' => 10,
            'cost' => 9,
            'status' => CheckJob::STATUS_PROCESSING,
        ]);

        for ($i = 0; $i < 7; $i++) {
            $job->numbers()->create(['phone' => "+7900000000{$i}", 'status' => CheckNumber::STATUS_ANSWERED]);
        }
        for ($i = 7; $i < 10; $i++) {
            $job->numbers()->create(['phone' => "+7900000000{$i}", 'status' => CheckNumber::STATUS_NO_ANSWER]);
        }

        app(ResultService::class)->finalizeIfComplete($job->fresh());

        // Возврат за 3 из 10 НДЗ: 9 * 3/10 = 2.7
        $this->assertSame('2.70000', $user->fresh()->deposit_balance);
        $this->assertSame(CheckJob::STATUS_COMPLETED, $job->fresh()->status);
    }
}

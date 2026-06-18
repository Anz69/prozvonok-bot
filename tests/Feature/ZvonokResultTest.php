<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Models\CheckJob;
use App\Services\Zvonok\ResultService;
use App\Services\Zvonok\ZvonokClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ZvonokResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_results_applied_aggregated_and_file_exported(): void
    {
        Storage::fake('local');

        $user = BotUser::create(['telegram_id' => 1]);
        $job = CheckJob::create([
            'bot_user_id' => $user->id,
            'geo_code' => 'RU',
            'numbers_total' => 3,
            'numbers_valid' => 3,
            'cost' => 0,
            'status' => CheckJob::STATUS_PROCESSING,
            'zvonok_campaign_id' => 'fake-x',
        ]);

        $phones = ['+79001234561', '+79001234562', '+79001234563'];
        foreach ($phones as $p) {
            $job->numbers()->create(['phone' => $p]);
        }

        $client = app(ZvonokClient::class); // FakeZvonokClient (драйвер fake в phpunit.xml)
        $results = $client->fetchResults('fake-x', $phones);
        app(ResultService::class)->applyResults($job, $results);

        $job->refresh();
        $this->assertSame(CheckJob::STATUS_COMPLETED, $job->status);
        $this->assertSame(3, $job->summary['total']);
        $this->assertNotNull($job->output_path);
        Storage::disk('local')->assertExists($job->output_path);

        $user->refresh();
        $this->assertSame(3, (int) $user->numbers_checked);
        $this->assertSame(
            (int) $user->numbers_answered + (int) $user->numbers_failed,
            (int) $user->numbers_checked,
        );
    }
}

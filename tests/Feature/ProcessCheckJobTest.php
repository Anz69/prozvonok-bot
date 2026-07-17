<?php

namespace Tests\Feature;

use App\Jobs\ProcessCheckJob;
use App\Models\BotUser;
use App\Models\CheckJob;
use App\Services\NumberFileParser;
use App\Services\Zvonok\ZvonokClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProcessCheckJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeJob(): CheckJob
    {
        $user = BotUser::create(['telegram_id' => 9010001, 'first_name' => 'T']);

        $job = new CheckJob([
            'geo_code' => 'RU',
            'numbers_total' => 2,
            'numbers_valid' => 2,
            'cost' => 0,
            'status' => CheckJob::STATUS_QUEUED,
            'queued_at' => now(),
        ]);
        $job->bot_user_id = $user->id;
        $job->save();

        $job->numbers()->insert([
            ['check_job_id' => $job->id, 'phone' => '+79001112233', 'created_at' => now(), 'updated_at' => now()],
            ['check_job_id' => $job->id, 'phone' => '+79001112244', 'created_at' => now(), 'updated_at' => now()],
        ]);

        return $job;
    }

    /**
     * Регрессия: повторный запуск джобы (ретрай $tries=3 / ручной re-dispatch)
     * НЕ должен ставить номера в обзвон второй раз — иначе Звонок звонит базу
     * несколько раз и деньги списываются повторно.
     */
    public function test_calls_are_placed_only_once_on_repeat_run(): void
    {
        $job = $this->makeJob();

        $client = Mockery::mock(ZvonokClient::class);
        $client->shouldReceive('createCalls')->once()->andReturn('camp-1');
        $this->app->instance(ZvonokClient::class, $client);

        $parser = app(NumberFileParser::class);

        // первый прогон — звонки ставятся
        (new ProcessCheckJob($job->id))->handle($parser, app(ZvonokClient::class));
        $this->assertSame(CheckJob::STATUS_PROCESSING, $job->fresh()->status);
        $this->assertSame('camp-1', $job->fresh()->zvonok_campaign_id);

        // повторный прогон — createCalls больше не вызывается (Mockery ->once())
        (new ProcessCheckJob($job->id))->handle($parser, app(ZvonokClient::class));
        (new ProcessCheckJob($job->id))->handle($parser, app(ZvonokClient::class));

        $this->assertSame(CheckJob::STATUS_PROCESSING, $job->fresh()->status);
    }
}

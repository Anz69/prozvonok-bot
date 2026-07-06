<?php

namespace App\Console\Commands;

use App\Models\CheckJob;
use App\Services\Zvonok\ResultService;
use App\Services\Zvonok\ZvonokClient;
use Illuminate\Console\Command;

/**
 * Polling-фолбэк выгрузки результатов обзвона (раздел 4): для заданий в processing
 * массово тянет результаты и применяет; при завершении — финализация и выдача файла.
 * В расписании — каждую минуту.
 */
class PollZvonokResults extends Command
{
    protected $signature = 'checks:poll-results';

    protected $description = 'Опросить результаты обзвона Звонок.com и финализировать готовые задания';

    public function handle(ZvonokClient $client, ResultService $results): int
    {
        $processed = 0;

        CheckJob::where('status', CheckJob::STATUS_PROCESSING)
            ->whereNotNull('zvonok_campaign_id')
            ->cursor()
            ->each(function (CheckJob $job) use ($client, $results, &$processed) {
                $phones = $job->numbers()->whereNull('status')->pluck('phone')->all();
                if ($phones === []) {
                    $results->finalizeIfComplete($job);

                    return;
                }

                $data = $client->fetchResults((string) $job->zvonok_campaign_id, $phones, ['geo' => $job->geo_code]);
                if ($data !== []) {
                    $results->applyResults($job, $data);
                    $processed++;
                }
            });

        $this->info("Обработано заданий: {$processed}");

        return self::SUCCESS;
    }
}

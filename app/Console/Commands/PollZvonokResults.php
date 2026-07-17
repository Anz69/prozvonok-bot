<?php

namespace App\Console\Commands;

use App\Models\CheckJob;
use App\Models\CheckNumber;
use App\Models\Setting;
use App\Services\Zvonok\ResultService;
use App\Services\Zvonok\ZvonokClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

                // Страховка: одна «зависшая» строка не должна блокировать выдачу всей базы.
                $this->closeStale($job->fresh(), $results);
            });

        $this->info("Обработано заданий: {$processed}");

        return self::SUCCESS;
    }

    /**
     * Если задание висит дольше лимита — закрываем оставшиеся номера как НДЗ и отдаём файл.
     * Без этого база может не выгрузиться никогда: достаточно одного номера, который навсегда
     * остался in_process или пришёл со статусом, которого нет в zvonok_status_map.
     */
    private function closeStale(?CheckJob $job, ResultService $results): void
    {
        if ($job === null || $job->status !== CheckJob::STATUS_PROCESSING) {
            return;
        }

        $timeout = (int) Setting::get('check_timeout_minutes', 180);
        if ($timeout <= 0) {
            return;
        }

        $startedAt = $job->queued_at ?? $job->created_at;
        if ($startedAt === null || $startedAt->gt(now()->subMinutes($timeout))) {
            return;
        }

        $stuck = $job->numbers()->whereNull('status')->count();
        if ($stuck === 0) {
            return;
        }

        $job->numbers()->whereNull('status')->update([
            'status' => CheckNumber::STATUS_NO_ANSWER,
            'is_active' => false,
            'last_status' => 'timeout',
        ]);

        Log::channel('integration')->warning(
            "CheckJob #{$job->id}: {$stuck} номеров без финального статуса за {$timeout} мин — закрыты как НДЗ, файл выдаём",
        );

        $results->finalizeIfComplete($job->fresh());
    }
}

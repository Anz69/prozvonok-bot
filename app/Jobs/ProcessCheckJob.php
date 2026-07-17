<?php

namespace App\Jobs;

use App\Models\CheckJob;
use App\Services\NumberFileParser;
use App\Services\Zvonok\ZvonokClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Постановка задания в обзвон Звонок.com (раздел 4): создаёт check_numbers из входного
 * файла и массово отправляет номера в сервис. Результаты собирает PollZvonokResults/webhook.
 */
class ProcessCheckJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $checkJobId)
    {
    }

    public function handle(NumberFileParser $parser, ZvonokClient $client): void
    {
        $job = CheckJob::find($this->checkJobId);
        if ($job === null) {
            return;
        }
        if (in_array($job->status, [CheckJob::STATUS_COMPLETED, CheckJob::STATUS_FAILED, CheckJob::STATUS_CANCELLED], true)) {
            return;
        }

        // Идемпотентность: звонки уже поставлены (ретрай джобы, повторный dispatch,
        // двойное нажатие) — НЕ отправляем номера в кампанию второй раз, иначе
        // Звонок обзвонит одну и ту же базу несколько раз и спишет деньги повторно.
        if ($job->status === CheckJob::STATUS_PROCESSING && $job->zvonok_campaign_id) {
            return;
        }

        // Создаём номера задания из входного файла (идемпотентно)
        if ($job->numbers()->doesntExist() && $job->input_path) {
            $parsed = $parser->parse(Storage::disk('local')->path($job->input_path), $job->geo_code);
            collect($parsed['valid'])
                ->chunk(1000)
                ->each(fn ($chunk) => $job->numbers()->insert(
                    $chunk->map(fn ($phone) => [
                        'check_job_id' => $job->id,
                        'phone' => $phone,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all(),
                ));
        }

        $phones = $job->numbers()->pluck('phone')->all();

        // Массовая постановка в обзвон
        $campaignId = $client->createCalls($phones, ['geo' => $job->geo_code]);

        $job->update([
            'status' => CheckJob::STATUS_PROCESSING,
            'zvonok_campaign_id' => $campaignId,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        CheckJob::where('id', $this->checkJobId)->update(['status' => CheckJob::STATUS_FAILED]);

        \Illuminate\Support\Facades\Log::channel('integration')
            ->error("ProcessCheckJob #{$this->checkJobId} провалилось: {$e->getMessage()}");
        app(\App\Telegram\Support\AdminNotifier::class)
            ->alert("⚠️ Задание проверки #{$this->checkJobId} завершилось ошибкой: {$e->getMessage()}");
    }
}

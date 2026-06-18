<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCheckJob;
use App\Models\CheckJob;
use App\Services\WorkingHours;
use Illuminate\Console\Command;

/**
 * Постановка отложенных заданий (вне рабочих часов) в обзвон, когда наступило окно
 * по часовому поясу абонента (раздел 3.3.7). В расписании — каждую минуту.
 */
class DispatchScheduledChecks extends Command
{
    protected $signature = 'checks:dispatch-scheduled';

    protected $description = 'Поставить отложенные задания проверки в обзвон по наступлению рабочих часов';

    public function handle(WorkingHours $hours): int
    {
        $dispatched = 0;

        CheckJob::where('status', CheckJob::STATUS_SCHEDULED)
            ->where(fn ($q) => $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now()))
            ->with('botUser')
            ->cursor()
            ->each(function (CheckJob $job) use ($hours, &$dispatched) {
                if (! $hours->isWithin($job->botUser->timezone)) {
                    return;
                }
                $job->update(['status' => CheckJob::STATUS_QUEUED, 'queued_at' => now()]);
                ProcessCheckJob::dispatch($job->id);
                $dispatched++;
            });

        $this->info("Поставлено в обзвон: {$dispatched}");

        return self::SUCCESS;
    }
}

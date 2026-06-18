<?php

namespace App\Filament\Widgets;

use App\Models\BotUser;
use App\Models\CheckJob;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Сводка на дашборде админки: пользователи, пополнения за сегодня, активные задания,
 * заявки на вывод в ожидании.
 */
class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $depositToday = (float) Transaction::where('type', Transaction::TYPE_DEPOSIT)
            ->whereDate('created_at', today())
            ->sum('amount');

        $activeJobs = CheckJob::whereIn('status', [
            CheckJob::STATUS_QUEUED, CheckJob::STATUS_SCHEDULED, CheckJob::STATUS_PROCESSING,
        ])->count();

        $pendingWithdrawals = Withdrawal::where('status', Withdrawal::STATUS_PENDING)->count();

        return [
            Stat::make('Пользователей', (string) BotUser::count())
                ->description('Всего в боте')
                ->color('primary'),
            Stat::make('Пополнения сегодня', number_format($depositToday, 2) . '$')
                ->description('Сумма депозитов за сегодня')
                ->color('success'),
            Stat::make('Активные задания', (string) $activeJobs)
                ->description('В очереди / обработке')
                ->color('info'),
            Stat::make('Выводы в ожидании', (string) $pendingWithdrawals)
                ->description('Требуют модерации')
                ->color($pendingWithdrawals > 0 ? 'warning' : 'gray'),
        ];
    }
}

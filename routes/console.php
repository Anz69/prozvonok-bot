<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Расписание (раздел 6)
|--------------------------------------------------------------------------
*/

// Приём USDT и авто-зачисление
Schedule::command('payments:poll')->everyMinute()->withoutOverlapping();

// Премиум: уведомления и деактивация
Schedule::command('premium:expire')->dailyAt('10:00');

// Звонок.com: постановка отложенных заданий и опрос результатов
Schedule::command('checks:dispatch-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('checks:poll-results')->everyMinute()->withoutOverlapping();

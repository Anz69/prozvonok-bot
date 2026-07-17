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

// ВАЖНО: withoutOverlapping(N) — короткий лок на N минут. По умолчанию Laravel держит
// его 1440 мин (сутки): если процесс убили на середине (рестарт контейнера при деплое),
// лок не освобождается и команда молча не запускается ЦЕЛЫЕ СУТКИ. Из-за этого
// переставали собираться результаты обзвона и не выгружались базы.

// Приём USDT и авто-зачисление
Schedule::command('payments:poll')->everyMinute()->withoutOverlapping(5);

// Премиум: уведомления и деактивация
Schedule::command('premium:expire')->dailyAt('10:00');

// Звонок.com: постановка отложенных заданий и опрос результатов
Schedule::command('checks:dispatch-scheduled')->everyMinute()->withoutOverlapping(5);
Schedule::command('checks:poll-results')->everyMinute()->withoutOverlapping(10);

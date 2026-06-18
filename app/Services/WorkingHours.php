<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * Рабочие часы проверок (раздел 3.3.7): 09:00–21:00 по часовому поясу абонента.
 * Файл вне окна принимается, но уходит в работу после старта.
 */
class WorkingHours
{
    public function start(): int
    {
        return (int) Setting::get('working_hours_start', 9);
    }

    public function end(): int
    {
        return (int) Setting::get('working_hours_end', 21);
    }

    public function isWithin(string $timezone): bool
    {
        $now = CarbonImmutable::now($this->safeTz($timezone));
        $hour = (int) $now->format('G');

        return $hour >= $this->start() && $hour < $this->end();
    }

    /**
     * Ближайший момент начала рабочего окна (для отложенной постановки).
     */
    public function nextStart(string $timezone): CarbonImmutable
    {
        $tz = $this->safeTz($timezone);
        $now = CarbonImmutable::now($tz);
        $todayStart = $now->setTime($this->start(), 0);

        if ($now->lt($todayStart)) {
            return $todayStart;
        }

        // если уже отработали сегодня — завтра в час старта
        if ((int) $now->format('G') >= $this->end()) {
            return $todayStart->addDay();
        }

        return $todayStart;
    }

    private function safeTz(string $timezone): string
    {
        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'Europe/Moscow';
    }
}

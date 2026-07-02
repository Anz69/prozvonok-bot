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

    /**
     * Примерный момент готовности файла: старт (сейчас либо начало рабочего окна)
     * + средняя обработка + время на объём базы.
     */
    public function estimatedReadyAt(string $timezone, int $numbers): CarbonImmutable
    {
        $start = $this->isWithin($timezone)
            ? CarbonImmutable::now($this->safeTz($timezone))
            : $this->nextStart($timezone);

        $avg = (int) Setting::get('avg_processing_minutes', 15);
        $perMin = max(1, (int) Setting::get('check_numbers_per_minute', 100));
        $minutes = $avg + (int) ceil(max(0, $numbers) / $perMin);

        return $start->addMinutes($minutes);
    }

    /**
     * Мягкая метка готовности для пользователя (без пугающего точного времени):
     * в рабочее время — «обычно в течение 15–30 минут», вне — «после 09:00».
     */
    public function etaLabel(string $timezone, int $numbers): string
    {
        if (! $this->isWithin($timezone)) {
            return sprintf('после %02d:00 по часовому поясу абонента', $this->start());
        }

        $tz = $this->safeTz($timezone);
        $mins = max(5, (int) round($this->estimatedReadyAt($timezone, $numbers)->diffInMinutes(CarbonImmutable::now($tz))));
        $low = max(5, (int) floor($mins / 5) * 5);
        $high = $low + 15;

        return "обычно в течение {$low}–{$high} минут";
    }

    private function safeTz(string $timezone): string
    {
        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'Europe/Moscow';
    }
}

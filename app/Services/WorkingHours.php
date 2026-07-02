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

    /** Человеко-читаемая метка готовности: «к 16:35», «завтра к 09:20», «05.07 в 09:20». */
    public function etaLabel(string $timezone, int $numbers): string
    {
        $tz = $this->safeTz($timezone);
        $eta = $this->estimatedReadyAt($timezone, $numbers)->setTimezone($tz);
        $now = CarbonImmutable::now($tz);

        if ($eta->isSameDay($now)) {
            return 'к ' . $eta->format('H:i');
        }
        if ($eta->isSameDay($now->addDay())) {
            return 'завтра к ' . $eta->format('H:i');
        }

        return $eta->format('d.m в H:i');
    }

    private function safeTz(string $timezone): string
    {
        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'Europe/Moscow';
    }
}

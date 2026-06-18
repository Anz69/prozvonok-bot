<?php

namespace App\Services\Zvonok;

use Illuminate\Support\Str;

/**
 * Фейковый клиент: имитирует обзвон детерминированно (для локали/тестов).
 * Результат зависит от последней цифры номера — стабильно воспроизводимо.
 */
class FakeZvonokClient implements ZvonokClient
{
    public function createCalls(array $phones, array $options = []): string
    {
        return 'fake-' . Str::random(8);
    }

    public function fetchResults(string $campaignId, array $phones = []): array
    {
        $operators = ['МТС', 'Билайн', 'МегаФон', 'Tele2'];
        $results = [];

        foreach ($phones as $phone) {
            $digit = (int) substr(preg_replace('/\D/', '', $phone), -1);
            $answered = $digit % 3 !== 0; // ~2/3 дозвон

            $results[$phone] = [
                'status' => $answered ? 'answered' : 'no_answer',
                'operator' => $operators[$digit % count($operators)],
                'mnp_operator' => $digit % 4 === 0 ? $operators[($digit + 1) % count($operators)] : null,
                'is_active' => $answered,
                'timezone' => 'Europe/Moscow',
                'last_status' => $answered ? 'online' : 'unknown',
                'transcription' => $answered ? 'Алло, да, слушаю вас' : null,
                'raw' => ['fake' => true, 'digit' => $digit],
            ];
        }

        return $results;
    }
}

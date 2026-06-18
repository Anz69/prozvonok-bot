<?php

namespace App\Services\Zvonok;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Прод-реализация клиента Звонок.com.
 *
 * Троттлинг ≤20 rps (лимит API), ретраи/backoff на 429/5xx.
 *
 * // TODO: уточнить по https://api-docs.zvonok.com точные эндпоинты/имена полей:
 *   - создание звонков (массово) и идентификатор кампании,
 *   - формат выгрузки результатов (статусы, MNP-оператор, транскрипция),
 *   - маппинг статусов сервиса → answered/no_answer (settings: zvonok_status_map).
 */
class HttpZvonokClient implements ZvonokClient
{
    private function http(): PendingRequest
    {
        $this->throttle();

        return Http::baseUrl((string) config('dozvon.zvonok.base_url'))
            ->retry(3, 1000, throw: false) // backoff на 429/5xx
            ->withOptions(['query' => ['public_key' => config('dozvon.zvonok.api_key')]]);
    }

    /**
     * Не более rate_limit запросов в секунду к API.
     */
    private function throttle(): void
    {
        $limit = (int) config('dozvon.zvonok.rate_limit', 20);
        while (RateLimiter::tooManyAttempts('zvonok-api', $limit)) {
            usleep(50_000);
        }
        RateLimiter::hit('zvonok-api', 1);
    }

    public function createCalls(array $phones, array $options = []): string
    {
        $response = $this->http()->post('/phones/new/', array_merge([
            'campaign_id' => config('dozvon.zvonok.campaign_id'),
            'phones' => implode(',', $phones),
        ], $options));

        // TODO: вернуть реальный идентификатор кампании/пакета из ответа
        return (string) ($response->json('campaign_id') ?? config('dozvon.zvonok.campaign_id'));
    }

    public function fetchResults(string $campaignId, array $phones = []): array
    {
        $response = $this->http()->get('/statistic/', ['campaign_id' => $campaignId]);
        $map = (array) config('dozvon.zvonok.status_map', []);

        $results = [];
        foreach ((array) $response->json('data', []) as $row) {
            $phone = (string) ($row['phone'] ?? '');
            if ($phone === '') {
                continue;
            }
            $rawStatus = (string) ($row['status'] ?? '');
            $results[$phone] = [
                'status' => $map[$rawStatus] ?? 'no_answer',
                'operator' => $row['operator'] ?? null,
                'mnp_operator' => $row['mnp_operator'] ?? null,
                'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : null,
                'timezone' => $row['timezone'] ?? null,
                'last_status' => $row['last_status'] ?? null,
                'transcription' => $row['transcription'] ?? null,
                'raw' => $row,
            ];
        }

        return $results;
    }
}

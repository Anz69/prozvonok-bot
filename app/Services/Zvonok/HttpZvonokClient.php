<?php

namespace App\Services\Zvonok;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Боевой клиент Звонок.com (cabapi_external API v1).
 *
 * Эндпоинты (база https://zvonok.com/manager/cabapi_external/api/v1):
 *   - POST phones/call/            — поставить номер в обзвон (public_key, campaign_id, phone[, text, speaker])
 *   - GET  phones/calls_by_phone/  — статус(ы) звонка по номеру (public_key, campaign_id, phone)
 *
 * Статусы звонка (call status): in_process | user | attempts_exc | compl_nofinished
 *   | compl_finished | novalid_button | deleted. Маппинг → answered/no_answer в settings.zvonok_status_map.
 * Троттлинг ≤20 rps, ретраи на 429/5xx.
 */
class HttpZvonokClient implements ZvonokClient
{
    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('dozvon.zvonok.base_url'), '/'))
            ->timeout(20)
            ->retry(3, 1000, throw: false);
    }

    private function key(): ?string
    {
        return config('dozvon.zvonok.api_key');
    }

    /** Не более rate_limit запросов в секунду. */
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
        $campaign = (string) config('dozvon.zvonok.campaign_id');

        foreach ($phones as $phone) {
            $this->throttle();
            $response = $this->http()->asForm()->post('/phones/call/', array_filter([
                'public_key' => $this->key(),
                'campaign_id' => $campaign,
                'phone' => $phone,
                'text' => $options['text'] ?? null,
            ]));

            if (! $response->successful()) {
                Log::channel('integration')->warning('Zvonok createCall failed', [
                    'phone' => $phone, 'http' => $response->status(), 'body' => $response->body(),
                ]);
            }
        }

        return $campaign;
    }

    public function fetchResults(string $campaignId, array $phones = []): array
    {
        $map = (array) Setting::get('zvonok_status_map', []);
        $results = [];

        foreach ($phones as $phone) {
            $this->throttle();
            $response = $this->http()->get('/phones/calls_by_phone/', [
                'public_key' => $this->key(),
                'campaign_id' => $campaignId,
                'phone' => $phone,
            ]);

            if (! $response->successful()) {
                continue;
            }

            // Звонок отдаёт при успехе ГОЛЫЙ массив звонков [{...}], а при ошибке —
            // объект {"status":"error","data":"..."}. Достаём звонки из обоих форматов.
            $json = $response->json();
            $data = (is_array($json) && array_key_exists('data', $json)) ? $json['data'] : $json;
            $call = $this->latestCall($data);
            if ($call === null) {
                continue;
            }

            $rawStatus = (string) ($call['status'] ?? '');
            $mapped = $map[$rawStatus] ?? null;
            if ($mapped === null) {
                continue; // звонок ещё в процессе (in_process) — оставляем номер без результата
            }

            $results[$phone] = [
                'status' => $mapped,
                'operator' => $call['phone_oper'] ?? ($call['operator'] ?? null),
                'mnp_operator' => $call['mnp_oper'] ?? null,
                'is_active' => $mapped === 'answered',
                'timezone' => $call['phone_region'] ?? null,
                'last_status' => $rawStatus,
                'transcription' => $call['recognized_speech'] ?? ($call['data'] ?? null),
                'raw' => $call,
            ];
        }

        return $results;
    }

    /**
     * Звонок.com возвращает либо один объект, либо массив звонков — берём самый свежий.
     */
    private function latestCall(mixed $data): ?array
    {
        if (! is_array($data) || $data === []) {
            return null;
        }

        // массив звонков
        if (array_is_list($data)) {
            return (array) end($data);
        }

        return $data; // один объект
    }
}

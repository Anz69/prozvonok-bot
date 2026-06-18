<?php

namespace App\Services\Zvonok;

/**
 * Клиент сервиса обзвона Звонок.com (раздел 4).
 * Реализации: FakeZvonokClient (локаль/тесты), HttpZvonokClient (прод, ≤20 rps, ретраи).
 */
interface ZvonokClient
{
    /**
     * Массово поставить номера в обзвон. Возвращает id кампании/пакета.
     *
     * @param  list<string>  $phones  номера в E.164
     * @param  array<string, mixed>  $options
     */
    public function createCalls(array $phones, array $options = []): string;

    /**
     * Массово выгрузить результаты по кампании.
     *
     * @return array<string, array{status:string, operator:?string, mnp_operator:?string, is_active:?bool, timezone:?string, last_status:?string, transcription:?string, raw:array}>
     *         ключ — номер E.164
     */
    public function fetchResults(string $campaignId, array $phones = []): array;
}

<?php

namespace App\Services\Usdt;

use Illuminate\Support\Facades\Http;

/**
 * Прод-реализация: входящие TRC20 (USDT) переводы на адрес через публичный API Tronscan.
 *
 * // TODO: подтвердить эндпоинт/поля и при необходимости ключ API (config dozvon.usdt.tronscan_key).
 *         USDT-TRC20 контракт: TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t (6 знаков).
 */
class TronscanWatcher implements UsdtWatcher
{
    private const USDT_CONTRACT = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';

    public function fetchIncoming(string $address): array
    {
        $response = Http::baseUrl('https://apilist.tronscanapi.com')
            ->when(
                config('dozvon.usdt.tronscan_key'),
                fn ($http) => $http->withHeaders(['TRON-PRO-API-KEY' => config('dozvon.usdt.tronscan_key')]),
            )
            ->retry(3, 500)
            ->get('/api/token_trc20/transfers', [
                'relatedAddress' => $address,
                'contract_address' => self::USDT_CONTRACT,
                'limit' => 50,
                'start' => 0,
                'direction' => 'in',
            ]);

        if (! $response->ok()) {
            return [];
        }

        $result = [];
        foreach ((array) $response->json('token_transfers', []) as $tx) {
            if (($tx['to_address'] ?? null) !== $address) {
                continue;
            }
            $decimals = (int) ($tx['tokenInfo']['tokenDecimal'] ?? 6);
            $result[] = [
                'tx_hash' => (string) ($tx['transaction_id'] ?? $tx['hash'] ?? ''),
                'to' => (string) $tx['to_address'],
                'amount' => (float) ($tx['quant'] ?? 0) / (10 ** $decimals),
                'confirmations' => (int) ($tx['confirmed'] ?? 0) === 1 ? 999 : 0,
            ];
        }

        return $result;
    }
}

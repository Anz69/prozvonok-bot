<?php

namespace App\Services\Usdt;

use Illuminate\Support\Facades\Cache;

/**
 * Фейковый вотчер для локали/тестов. Входящие транзакции кладутся в кэш
 * (например тестом или artisan-командой), отсюда читаются как «из сети».
 */
class FakeUsdtWatcher implements UsdtWatcher
{
    public const CACHE_KEY = 'usdt.fake.incoming';

    public function fetchIncoming(string $address): array
    {
        $all = Cache::get(self::CACHE_KEY, []);

        return array_values(array_filter(
            $all,
            fn ($tx) => ($tx['to'] ?? null) === $address,
        ));
    }

    /**
     * Хелпер для тестов/отладки: «прислать» транзакцию.
     */
    public static function push(string $txHash, string $to, float $amount, int $confirmations = 30): void
    {
        $all = Cache::get(self::CACHE_KEY, []);
        $all[] = ['tx_hash' => $txHash, 'to' => $to, 'amount' => $amount, 'confirmations' => $confirmations];
        Cache::put(self::CACHE_KEY, $all, now()->addDay());
    }
}

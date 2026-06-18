<?php

namespace App\Services\Usdt;

/**
 * Источник входящих USDT TRC-20 транзакций на наш адрес приёма.
 * Реализации: FakeUsdtWatcher (локаль/тесты), TronscanWatcher (прод).
 */
interface UsdtWatcher
{
    /**
     * Входящие транзакции на адрес.
     *
     * @return list<array{tx_hash:string, to:string, amount:float, confirmations:int}>
     */
    public function fetchIncoming(string $address): array;
}

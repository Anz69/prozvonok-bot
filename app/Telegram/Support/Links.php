<?php

namespace App\Telegram\Support;

use App\Models\Setting;

/**
 * Диплинки бота и ссылка на менеджера с предзаполненным текстом оплаты.
 */
class Links
{
    public static function botUsername(): string
    {
        return (string) config('dozvon.bot_username');
    }

    /** Диплинк на бота: https://t.me/<bot>?start=<payload> */
    public static function deepLink(string $payload): string
    {
        return 'https://t.me/' . self::botUsername() . '?start=' . $payload;
    }

    /** Username менеджера из support_url (https://t.me/SoulGoodmanSupp → SoulGoodmanSupp). */
    public static function managerUsername(): string
    {
        $url = (string) Setting::get('support_url', '');

        return ltrim((string) parse_url($url, PHP_URL_PATH), '/') ?: 'SoulGoodmanSupp';
    }

    /**
     * Ссылка «написать менеджеру» с готовым текстом: сумма + диплинк подтверждения.
     */
    public static function managerPay(string $amount, string $confirmPayload): string
    {
        $text = "Здравствуйте! Хочу пополнить баланс на {$amount}\$.\n\n"
            . "Менеджер, подтвердите оплату по ссылке после получения перевода:\n"
            . self::deepLink($confirmPayload);

        return 'https://t.me/' . self::managerUsername() . '?text=' . rawurlencode($text);
    }
}

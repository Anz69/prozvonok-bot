<?php

/*
|--------------------------------------------------------------------------
| Dozvon Bot — конфигурация интеграций
|--------------------------------------------------------------------------
| Здесь только секреты/инфраструктурные параметры из .env.
| Бизнес-настройки (тарифы, лимиты, тексты) живут в БД (settings/bot_texts)
| и редактируются в Filament-админке — см. App\Models\Setting.
*/

return [

    // Telegram
    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'DozvonRfRbKz_Bot'),

    // Звонок.com — интеграция обзвона (Part 3)
    'zvonok' => [
        'driver'       => env('ZVONOK_DRIVER', 'fake'), // fake | http
        'base_url'     => env('ZVONOK_BASE_URL', 'https://zvonok.com/manager/cabapi_external/api/v1'),
        'api_key'      => env('ZVONOK_API_KEY'),
        'campaign_id'  => env('ZVONOK_CAMPAIGN_ID'),
        'rate_limit'   => (int) env('ZVONOK_RATE_LIMIT', 20), // запросов в секунду (лимит API)
        'postback_secret' => env('ZVONOK_POSTBACK_SECRET'), // защита webhook
    ],

    // USDT TRC-20 — приём платежей (Part 2)
    'usdt' => [
        'wallet'         => env('USDT_WALLET'),            // адрес TRC-20 для приёма
        'confirmations'  => (int) env('USDT_CONFIRMATIONS', 19),
        'provider'       => env('USDT_PROVIDER', 'manual'), // manual | tronscan | ... (TODO: выбрать ноду/провайдера)
        'tronscan_key'   => env('TRONSCAN_API_KEY'),
    ],
];

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

    // Mini App (Telegram WebApp) — HTTPS-ссылка на веб-интерфейс /app.
    // Пусто = кнопка запуска не показывается (например, локально без HTTPS).
    'miniapp_url' => env('MINIAPP_URL'),

    // Звонок.com — интеграция обзвона (Part 3)
    'zvonok' => [
        'driver'       => env('ZVONOK_DRIVER', 'fake'), // fake | http
        'base_url'     => env('ZVONOK_BASE_URL', 'https://zvonok.com/manager/cabapi_external/api/v1'),
        'rate_limit'   => (int) env('ZVONOK_RATE_LIMIT', 20), // запросов в секунду (лимит API)
        'postback_secret' => env('ZVONOK_POSTBACK_SECRET'), // защита webhook

        // Гео по умолчанию, если у задания не задан geo_code.
        'default_geo'  => env('ZVONOK_DEFAULT_GEO', 'RU'),

        // Отдельный аккаунт Звонок.com (public_key + campaign_id) на каждую страну (geo_code).
        // Выбор аккаунта — по geo_code задания (см. HttpZvonokClient::account()).
        // Если для страны ключи не заданы — фолбэк на legacy-пару ZVONOK_API_KEY/ZVONOK_CAMPAIGN_ID.
        'accounts' => [
            'RU' => [
                'api_key'     => env('ZVONOK_RU_API_KEY', env('ZVONOK_API_KEY')),
                'campaign_id' => env('ZVONOK_RU_CAMPAIGN_ID', env('ZVONOK_CAMPAIGN_ID')),
            ],
            'BY' => [
                'api_key'     => env('ZVONOK_BY_API_KEY'),
                'campaign_id' => env('ZVONOK_BY_CAMPAIGN_ID'),
            ],
            'KZ' => [
                'api_key'     => env('ZVONOK_KZ_API_KEY'),
                'campaign_id' => env('ZVONOK_KZ_CAMPAIGN_ID'),
            ],
        ],

        // legacy-пара — общий фолбэк, если для страны не настроен свой аккаунт.
        'api_key'      => env('ZVONOK_API_KEY'),
        'campaign_id'  => env('ZVONOK_CAMPAIGN_ID'),
    ],

    // USDT TRC-20 — приём платежей (Part 2)
    'usdt' => [
        'wallet'         => env('USDT_WALLET'),            // адрес TRC-20 для приёма
        'confirmations'  => (int) env('USDT_CONFIRMATIONS', 19),
        'provider'       => env('USDT_PROVIDER', 'manual'), // manual | tronscan | ... (TODO: выбрать ноду/провайдера)
        'tronscan_key'   => env('TRONSCAN_API_KEY'),
    ],
];

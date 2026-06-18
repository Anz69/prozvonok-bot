<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Telegram\Handlers\AdminHandler;
use App\Telegram\Handlers\MenuRouter;
use App\Telegram\Handlers\Navigator;
use App\Telegram\Handlers\OnboardingHandler;
use App\Telegram\Handlers\PaymentHandler;
use App\Telegram\Handlers\StartHandler;
use App\Telegram\Middleware\EnsureOnboarded;
use App\Telegram\Middleware\ResolveBotUser;
use App\Telegram\Middleware\ThrottleUser;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| Telegram-бот «Сол Гудман / Dozvon Bot»
|--------------------------------------------------------------------------
| Режим одного экрана: всё рисуется/редактируется одним сообщением (Screen).
| Навигация — инлайн nav:{key} / act:{action}. Онбординг без гейта.
*/

$bot->middleware(ResolveBotUser::class);

// /start — диплинки (pay_/реф), онбординг, главный экран.
// Регистрируем обе формы: голый /start и /start <payload> (Nutgram матчит их раздельно).
$bot->onCommand('start', StartHandler::class);
$bot->onCommand('start {payload}', StartHandler::class);

// Онбординг (без гейта)
$bot->onCallbackQueryData('onb:check_sub', [OnboardingHandler::class, 'checkSubscription']);
$bot->onCallbackQueryData('onb:captcha:{choice}', [OnboardingHandler::class, 'verifyCaptcha']);

// Админка в боте (для admin_chat_ids; проверка прав внутри)
$bot->onCommand('admin', [AdminHandler::class, 'menu'])->description('Админка');
$bot->onCallbackQueryData('adm:home', [AdminHandler::class, 'home']);
$bot->onCallbackQueryData('adm:stats', [AdminHandler::class, 'stats']);
$bot->onCallbackQueryData('adm:settings', [AdminHandler::class, 'settings']);
$bot->onCallbackQueryData('adm:toggle:{key}', [AdminHandler::class, 'toggle']);
$bot->onCallbackQueryData('adm:broadcast', [AdminHandler::class, 'broadcast']);
$bot->onCallbackQueryData('adm:user', [AdminHandler::class, 'user']);
$bot->onCallbackQueryData('adm:uban:{id}', [AdminHandler::class, 'banToggle']);
$bot->onCallbackQueryData('adm:uprem:{id}', [AdminHandler::class, 'grantPremium']);
$bot->onCallbackQueryData('adm:ubal:{id}', [AdminHandler::class, 'adjustBalance']);

// Подтверждение/отмена оплаты менеджером (после открытия pay-диплинка; проверка прав внутри)
$bot->onCallbackQueryData('paycfm:{uid}', [PaymentHandler::class, 'confirmManager']);
$bot->onCallbackQueryData('paycxl:{uid}', [PaymentHandler::class, 'cancelManager']);

// Меню и экраны — только после онбординга
$bot->group(function (Nutgram $bot) {
    // Единый экран: навигация и действия
    $bot->onCallbackQueryData('nav:{key}', [Navigator::class, 'nav']);
    $bot->onCallbackQueryData('act:{action}', [Navigator::class, 'act']);

    // Текст (нижняя навигация) → «🏠 Меню/Назад/Проверить базу»
    $bot->onMessage(MenuRouter::class);
})->middleware(ThrottleUser::class)->middleware(EnsureOnboarded::class);

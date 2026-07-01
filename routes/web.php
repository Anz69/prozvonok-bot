<?php

use App\Http\Controllers\MiniApp\MiniAppController;
use App\Http\Controllers\ZvonokWebhookController;
use App\Http\Middleware\EnsureTelegramSession;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Postback от Звонок.com при смене статуса звонка (защита секретом в URL)
Route::post('/webhooks/zvonok/{secret}', ZvonokWebhookController::class)
    ->name('webhooks.zvonok');

/*
|--------------------------------------------------------------------------
| Telegram Mini App (Vue + Inertia)
|--------------------------------------------------------------------------
*/
Route::prefix('/app')->middleware(HandleInertiaRequests::class)->group(function () {
    // Авторизация по initData (без гейта)
    Route::post('session', [MiniAppController::class, 'session'])->name('miniapp.session');

    Route::middleware(EnsureTelegramSession::class)->group(function () {
        Route::get('/', [MiniAppController::class, 'dashboard'])->name('miniapp.dashboard');
        Route::get('check', [MiniAppController::class, 'check'])->name('miniapp.check');
        Route::post('check/quote', [MiniAppController::class, 'checkQuote']);
        Route::post('check/confirm', [MiniAppController::class, 'checkConfirm']);
        Route::get('topup', [MiniAppController::class, 'topup'])->name('miniapp.topup');
        Route::post('topup', [MiniAppController::class, 'topupStore']);
        Route::get('calculator', [MiniAppController::class, 'calculator'])->name('miniapp.calculator');
        Route::get('profile', [MiniAppController::class, 'profile'])->name('miniapp.profile');
        Route::get('referral', [MiniAppController::class, 'referral'])->name('miniapp.referral');
        Route::get('premium', [MiniAppController::class, 'premium'])->name('miniapp.premium');
        Route::post('premium/activate', [MiniAppController::class, 'premiumActivate']);
        Route::post('premium/trial', [MiniAppController::class, 'premiumTrial']);
        Route::get('faq', [MiniAppController::class, 'faq'])->name('miniapp.faq');
        Route::get('info', [MiniAppController::class, 'info'])->name('miniapp.info');
    });
});

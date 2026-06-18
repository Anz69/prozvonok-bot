<?php

use App\Http\Controllers\ZvonokWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Postback от Звонок.com при смене статуса звонка (защита секретом в URL)
Route::post('/webhooks/zvonok/{secret}', ZvonokWebhookController::class)
    ->name('webhooks.zvonok');

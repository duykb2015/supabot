<?php

use App\Http\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to('admin');
});

Route::post('/telegram/{bot:slug}/{secret}', TelegramWebhookController::class)
    ->name('telegram.webhook');

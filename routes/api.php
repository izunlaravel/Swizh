<?php

use App\Http\Controllers\Api\SwizhMobileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [SwizhMobileController::class, 'login']);
    Route::post('/payment/webhook', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'handle']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [SwizhMobileController::class, 'dashboard']);
        Route::get('/sellers/nearby', [SwizhMobileController::class, 'nearbySellers']);
        Route::post('/qris/pay', [SwizhMobileController::class, 'payQris']);
    });
});

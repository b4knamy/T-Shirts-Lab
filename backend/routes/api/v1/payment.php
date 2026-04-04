<?php

use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Support\Facades\Route;

Route::controller(PaymentController::class)->group(function () {
    // Authenticated — checkout flow
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/payments/create-intent',    'createIntent');
        Route::post('/payments/confirm',          'confirm');
        Route::get('/payments/{paymentIntentId}', 'status');
    });

    // Admin — refunds
    Route::middleware(['jwt.auth', 'admin'])->group(function () {
        Route::post('/payments/refund', 'refund');
    });
});

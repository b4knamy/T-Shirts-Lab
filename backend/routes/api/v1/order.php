<?php

use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Support\Facades\Route;

Route::controller(OrderController::class)->group(function () {
    // Authenticated — customer orders
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/orders', 'store');
        Route::get('/orders/my-orders', 'myOrders');
        Route::get('/orders/{id}', 'show');
    });

    // Admin — order management
    Route::middleware(['jwt.auth', 'admin'])->group(function () {
        Route::get('/orders', 'index');
        Route::patch('/orders/{id}/status', 'updateStatus');
    });
});

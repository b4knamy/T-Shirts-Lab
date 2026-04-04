<?php

use App\Http\Controllers\Api\V1\CouponController;
use Illuminate\Support\Facades\Route;

Route::controller(CouponController::class)->group(function () {
    // Public — promotional banners
    Route::get('/coupons/active', 'publicActive');

    // Authenticated — validate coupon at checkout
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/coupons/validate', 'validate');
    });

    // Admin — coupon management
    Route::middleware(['jwt.auth', 'admin'])->prefix('coupons')->group(function () {
        Route::get('/',        'index');
        Route::get('/{id}',    'show');
        Route::post('/',       'store');
        Route::patch('/{id}',  'update');
        Route::delete('/{id}', 'destroy');
    });
});

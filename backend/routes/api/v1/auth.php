<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->middleware('throttle:register');
    Route::post('/login', 'login')->middleware('throttle:login');
    Route::post('/refresh', 'refresh')->middleware('throttle:refresh');
    Route::post('/logout', 'logout')->middleware(['jwt.auth', 'jwt.password']);
    Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:password-reset');
    Route::post('/reset-password', 'resetPassword')->middleware('throttle:password-reset');
});

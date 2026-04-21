<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->middleware('throttle:10,1');
    Route::post('/login', 'login')->middleware('throttle:10,1');
    Route::post('/refresh', 'refresh')->middleware('throttle:20,1');
    Route::post('/logout', 'logout')->middleware('jwt.auth');
});

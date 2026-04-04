<?php

use App\Http\Controllers\Api\V1\CategoryController;
use Illuminate\Support\Facades\Route;

// Admin — category management
Route::middleware(['jwt.auth', 'admin'])
    ->controller(CategoryController::class)
    ->group(function () {
        Route::get('/categories',        'index');
        Route::post('/categories',       'store');
        Route::patch('/categories/{id}', 'update');
        Route::delete('/categories/{id}', 'destroy');
    });

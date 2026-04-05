<?php

use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductImageController;
use App\Http\Controllers\Api\V1\ProductReviewController;
use Illuminate\Support\Facades\Route;

// Public — browsing
Route::prefix('products')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/featured', 'featured');
    Route::get('/categories', 'categories');
    Route::get('/slug/{slug}', 'showBySlug');
    Route::get('/{id}', 'show');
    Route::get('/{id}/reviews', [ProductReviewController::class, 'index']);
});

// Authenticated — reviews
Route::middleware('jwt.auth')->controller(ProductReviewController::class)->group(function () {
    Route::post('/products/{id}/reviews', 'store');
    Route::patch('/reviews/{id}', 'update');
});

// Admin — product management
Route::middleware(['jwt.auth', 'admin'])->controller(ProductController::class)->group(function () {
    Route::post('/products', 'store');
    Route::patch('/products/{id}', 'update');
    Route::delete('/products/{id}', 'destroy');
});

// Admin — product image management
Route::middleware(['jwt.auth', 'admin'])
    ->prefix('products/{productId}/images')
    ->controller(ProductImageController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::post('/upload', 'upload');
        Route::patch('/{imageId}', 'update');
        Route::delete('/{imageId}', 'destroy');
    });

// Admin — review moderation
Route::middleware(['jwt.auth', 'admin'])->controller(ProductReviewController::class)->group(function () {
    Route::get('/reviews', 'adminIndex');
    Route::post('/reviews/{id}/reply', 'adminReply');
    Route::delete('/reviews/{id}', 'destroy');
});

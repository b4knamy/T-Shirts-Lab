<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - T-Shirts Lab
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api automatically by Laravel.
| We add /v1 prefix for versioning.
| Frontend expects all endpoints under /api/v1/
|
*/

Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', [HealthController::class, 'check']);

    // Auth (public)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
    });

    // Products (public)
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/featured', [ProductController::class, 'featured']);
        Route::get('/categories', [ProductController::class, 'categories']);
        Route::get('/slug/{slug}', [ProductController::class, 'showBySlug']);
        Route::get('/{id}', [ProductController::class, 'show']);
    });

    // Authenticated routes
    Route::middleware('jwt.auth')->group(function () {
        // Users
        Route::get('/users/me', [UserController::class, 'me']);
        Route::patch('/users/me', [UserController::class, 'updateProfile']);

        // Orders
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/my-orders', [OrderController::class, 'myOrders']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);

        // Payments
        Route::post('/payments/create-intent', [PaymentController::class, 'createIntent']);
        Route::post('/payments/confirm', [PaymentController::class, 'confirm']);
        Route::get('/payments/{paymentIntentId}', [PaymentController::class, 'status']);

        // Admin routes
        Route::middleware('admin')->group(function () {
            // Products management
            Route::post('/products', [ProductController::class, 'store']);
            Route::patch('/products/{id}', [ProductController::class, 'update']);
            Route::delete('/products/{id}', [ProductController::class, 'destroy']);

            // Orders management
            Route::get('/orders', [OrderController::class, 'index']);
            Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);

            // Payments
            Route::post('/payments/refund', [PaymentController::class, 'refund']);
        });
    });
});

// Stripe webhooks (no auth, verified by signature)
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);

<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductImageController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductReviewController;
use App\Http\Controllers\Api\V1\UserManagementController;
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

    // Coupons (public — promotional banners)
    Route::get('/coupons/active', [CouponController::class, 'publicActive']);

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

        // Coupon validation
        Route::post('/coupons/validate', [CouponController::class, 'validate']);

        // Admin routes
        Route::middleware('admin')->group(function () {
            // Products management
            Route::post('/products', [ProductController::class, 'store']);
            Route::patch('/products/{id}', [ProductController::class, 'update']);
            Route::delete('/products/{id}', [ProductController::class, 'destroy']);

            // Product Images management
            Route::prefix('products/{productId}/images')->group(function () {
                Route::get('/', [ProductImageController::class, 'index']);
                Route::post('/', [ProductImageController::class, 'store']);
                Route::post('/upload', [ProductImageController::class, 'upload']);
                Route::patch('/{imageId}', [ProductImageController::class, 'update']);
                Route::delete('/{imageId}', [ProductImageController::class, 'destroy']);
            });

            // Categories management
            Route::get('/categories', [CategoryController::class, 'index']);
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::patch('/categories/{id}', [CategoryController::class, 'update']);
            Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

            // Orders management
            Route::get('/orders', [OrderController::class, 'index']);
            Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);

            // Coupons management
            Route::prefix('coupons')->group(function () {
                Route::get('/', [CouponController::class, 'index']);
                Route::get('/{id}', [CouponController::class, 'show']);
                Route::post('/', [CouponController::class, 'store']);
                Route::patch('/{id}', [CouponController::class, 'update']);
                Route::delete('/{id}', [CouponController::class, 'destroy']);
            });

            // Reviews management (admin reply)
            Route::get('/reviews', [ProductReviewController::class, 'adminIndex']);
            Route::post('/reviews/{id}/reply', [ProductReviewController::class, 'adminReply']);
            Route::delete('/reviews/{id}', [ProductReviewController::class, 'destroy']);

            // User management (staff)
            Route::get('/users', [UserManagementController::class, 'index']);
            Route::post('/users', [UserManagementController::class, 'store']);
            Route::patch('/users/{id}', [UserManagementController::class, 'update']);

            // Payments
            Route::post('/payments/refund', [PaymentController::class, 'refund']);
        });
    });
});

// Stripe webhooks (no auth, verified by signature)
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);

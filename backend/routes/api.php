<?php

use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - T-Shirts Lab
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api automatically by Laravel.
| We add /v1 prefix for versioning.
| Each domain is split into its own file under routes/api/.
|
*/

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/v1/health.php';
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/user.php';
    require __DIR__.'/api/v1/product.php';
    require __DIR__.'/api/v1/category.php';
    require __DIR__.'/api/v1/coupon.php';
    require __DIR__.'/api/v1/order.php';
    require __DIR__.'/api/v1/payment.php';
});

// Stripe webhook — outside /v1, verified by Stripe signature (no JWT)
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VerificationController;

/*
|--------------------------------------------------------------------------
| Health & Test
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => response()->json(['status' => 'Assetly API is alive 🔥']));
Route::get('/test', fn () => 'API works');

/*
|--------------------------------------------------------------------------
| Public Routes (Guest Browsing)
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:api')->group(function () {

    // Auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::middleware('throttle:login')->post('/login', [AuthController::class, 'login']);

    // Email Verification
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->name('verification.verify');

    // Products & Categories - open to everyone
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);

    // Guest Cart Routes
    Route::post('/cart/add', [OrderController::class, 'addToCart']);       // add items
    Route::get('/cart', [OrderController::class, 'Cart']);             // view cart
    Route::delete('/cart/remove/{item}', [OrderController::class, 'removeFromCart']); // remove item
});

/*
|
| Webhooks
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->group(function () {
    Route::post('/paystack', [WebhookController::class, 'handlePaystack']);
    Route::post('/stripe', [WebhookController::class, 'handleStripe']);
    Route::post('/moniepoint', [WebhookController::class, 'handleMoniepoint']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {
        Route::patch('/orders/{order}/status', [OrderManagementController::class, 'updateStatus']);
        Route::apiResource('categories', CategoryController::class)
            ->only(['store', 'update', 'destroy']);
    });

/*
|--------------------------------------------------------------------------
| Protected Routes (Logged-in Users)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Checkout / Orders - only for logged-in users
    Route::apiResource('orders', OrderController::class)
        ->only(['store', 'index', 'show']);

    // Merge guest cart on login and view user cart
    Route::get('/cart/user', [OrderController::class, 'userCart'])->name('cart.user');

    // Seller/Admin Product Management
    Route::middleware(['role:admin|seller'])->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    // Subscriptions & Credits
    Route::post('/subscribe', [SubscriptionController::class, 'subscribePlan']);
    Route::post('/credits/daily', [SubscriptionController::class, 'claimDailyCredits']);
    Route::get('/credits', [SubscriptionController::class, 'balanceAndHistory']);
    Route::post('/credits/recharge', [SubscriptionController::class, 'recharge']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

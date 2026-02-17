<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Health & Test
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    return response()->json(['status' => 'Assetly API is alive 🔥']);
});
Route::get('/test', function () {
    return 'API works';
});

/*
|--------------------------------------------------------------------------
| Public Routes (No Auth)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['throttle:login'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});


Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Webhooks (No Auth)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/paystack', [WebhookController::class, 'handlePaystack']);
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);
Route::post('/webhooks/moniepoint', [WebhookController::class, 'handleMoniepoint']);

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Update order status
    Route::patch('/orders/{order}/status', [OrderManagementController::class, 'updateStatus']);

    // Admin CRUD for categories (optional)
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Auth Required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Products (Seller/Admin only)
    Route::post('/products', [ProductController::class, 'store'])->middleware('role:seller|admin');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('role:seller|admin');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('role:seller|admin');

    // Orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Subscriptions / Credits
    Route::post('/subscribe', [SubscriptionController::class, 'subscribePlan']);
    Route::post('/credits/daily', [SubscriptionController::class, 'claimDailyCredits']);
    Route::get('/credits', [SubscriptionController::class, 'balanceAndHistory']);
    Route::post('/credits/recharge', [SubscriptionController::class, 'recharge']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

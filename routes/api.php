<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AdminController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'Assetly API is alive 🔥'
    ]);
});

Route::get('/test', function () {
    return 'API works';
});


// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

Route::post('/products', [ProductController::class, 'store'])
     ->middleware('role:seller|admin'); 

    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/orders', [OrderController::class, 'store']);
      Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);

    Route::get('/me', function (Request $request) {
        return $request->user();
    });

});

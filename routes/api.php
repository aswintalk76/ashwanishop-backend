<?php

use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\PaymentQrController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingController as PublicSettingController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('health', fn () => response()->json([
        'ok' => true,
        'message' => 'Ashwani Shop API',
    ]));

    // Public
    Route::get('settings/public', [PublicSettingController::class, 'public']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);

    // Auth
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Guest cart (session token header)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart', [CartController::class, 'store']);
        Route::put('cart/{id}', [CartController::class, 'update']);
        Route::delete('cart/{id}', [CartController::class, 'destroy']);
        Route::delete('cart', [CartController::class, 'clear']);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('auth/resend-verification', [AuthController::class, 'resendVerification']);

        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist', [WishlistController::class, 'store']);
        Route::delete('wishlist/{productId}', [WishlistController::class, 'destroy']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{orderNumber}', [OrderController::class, 'show']);
        Route::post('orders/{orderNumber}/payment-proof', [OrderController::class, 'submitPaymentProof']);

        Route::post('reviews', [ReviewController::class, 'store']);
    });

    // Admin
    Route::prefix('admin')->group(function () {
        Route::post('auth/login', [AuthController::class, 'adminLogin']);

        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::get('analytics', [AnalyticsController::class, 'index']);
            Route::apiResource('products', AdminProductController::class);
            Route::apiResource('categories', AdminCategoryController::class);
            Route::apiResource('users', AdminUserController::class)->only(['index', 'show', 'update', 'destroy']);
            Route::get('orders', [AdminOrderController::class, 'index']);
            Route::get('orders/{orderNumber}', [AdminOrderController::class, 'show']);
            Route::patch('orders/{orderNumber}/status', [AdminOrderController::class, 'updateStatus']);
            Route::post('orders/{orderNumber}/verify-payment', [AdminOrderController::class, 'verifyPayment']);
            Route::get('payment-qr', [PaymentQrController::class, 'show']);
            Route::post('payment-qr', [PaymentQrController::class, 'update']);
            Route::get('reviews', [AdminReviewController::class, 'index']);
            Route::patch('reviews/{id}', [AdminReviewController::class, 'update']);
            Route::get('settings', [SettingController::class, 'index']);
            Route::put('settings', [SettingController::class, 'update']);
            Route::post('qr/scan', [QrController::class, 'scanDelivery']);
        });
    });
});

<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PosWebhookController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Middleware\ValidatePosWebhook;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/login', [AuthController::class, 'login']);

// POS System Webhook (protected by API key)
Route::middleware(ValidatePosWebhook::class)->prefix('pos')->group(function () {
    Route::post('/orders', [PosWebhookController::class, 'createOrder']);
    Route::get('/health', [PosWebhookController::class, 'healthCheck']);
});

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Supervisor routes (you can add middleware to check a role if needed)
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::post('/{order}/assign', [OrderController::class, 'assign']);
        Route::post('/{order}/reassign', [OrderController::class, 'reassign']);
        Route::post('/{order}/status', [OrderController::class, 'updateStatus']);
    });

    Route::prefix('riders')->group(function () {
        Route::get('/', [RiderController::class, 'index']);
        Route::post('/', [RiderController::class, 'store']);
        Route::get('/{rider}', [RiderController::class, 'show']);
        Route::put('/{rider}', [RiderController::class, 'update']);
    });

    // Branch routes
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index']);
        Route::post('/', [BranchController::class, 'store']);
        Route::get('/{branch}', [BranchController::class, 'show']);
        Route::put('/{branch}', [BranchController::class, 'update']);
        Route::delete('/{branch}', [BranchController::class, 'destroy']);
        Route::post('/{branch}/activate', [BranchController::class, 'activate']);
        Route::post('/{branch}/deactivate', [BranchController::class, 'deactivate']);
    });

    Route::prefix('map')->group(function () {
        Route::get('/riders', [MapController::class, 'riderPositions']);
    });

    // Settings
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);

    // Rider-specific routes
    Route::prefix('rider')->group(function () {
        Route::get('/me', [RiderController::class, 'me']);
        Route::get('/me/order', [RiderController::class, 'myOrder']);
        Route::post('/locations', [RiderController::class, 'ingestLocations'])
            ->middleware('throttle:60,1'); // 60 requests per minute
    });
});

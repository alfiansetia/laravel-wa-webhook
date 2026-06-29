<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index']);

// Dashboard APIs
Route::prefix('api')->group(function () {
    // Accounts CRUD
    Route::get('/accounts', [DashboardController::class, 'accounts']);
    Route::post('/accounts', [DashboardController::class, 'storeAccount']);
    Route::put('/accounts/{id}', [DashboardController::class, 'updateAccount']);
    Route::delete('/accounts/{id}', [DashboardController::class, 'destroyAccount']);

    // Chats & Messages
    Route::get('/accounts/{accountId}/chats', [DashboardController::class, 'chats']);
    Route::delete('/chats/{id}', [DashboardController::class, 'destroyChat']);
    Route::get('/chats/{chatId}/messages', [DashboardController::class, 'messages']);
    Route::post('/chats/{chatId}/send', [DashboardController::class, 'sendMessage']);
    Route::delete('/messages/{id}', [DashboardController::class, 'destroyMessage']);

    // Media Proxy for 401 Protected Assets
    Route::get('/media', [DashboardController::class, 'proxyMedia']);
});

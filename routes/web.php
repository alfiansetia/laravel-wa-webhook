<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index']);

// Dashboard APIs
Route::prefix('api')->group(function () {
    // Accounts CRUD
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::put('/accounts/{id}', [AccountController::class, 'update']);
    Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);

    // Chats & Messages
    Route::get('/accounts/{accountId}/chats', [ChatController::class, 'index']);
    Route::post('/accounts/{accountId}/chats', [ChatController::class, 'store']);
    Route::delete('/chats/{id}', [ChatController::class, 'destroy']);
    Route::get('/chats/{chatId}/messages', [MessageController::class, 'index']);
    Route::post('/chats/{chatId}/send', [MessageController::class, 'store']);
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']);

    // Media Proxy for 401 Protected Assets
    Route::get('/media', [MessageController::class, 'proxyMedia']);
});

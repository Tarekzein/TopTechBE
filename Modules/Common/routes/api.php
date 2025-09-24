<?php

use Illuminate\Support\Facades\Route;
use Modules\Common\Http\Controllers\CommonController;

use Modules\Common\Http\Controllers\NotificationController;
use Modules\Common\Http\Controllers\ChatController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications', [NotificationController::class, 'store']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});


Route::post('/chat/ask', [ChatController::class, 'ask']);
Route::get('/chat/history', [ChatController::class, 'history']);
Route::post('/chat/compare', [ChatController::class, 'compare']);

<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ConversationController;
use Modules\Chat\Http\Controllers\MessageController;
use Modules\Chat\Http\Controllers\AttachmentController;

Route::middleware(['auth:sanctum'])->group(function () {

    /**
     * Conversations Routes
     */
    Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::post('/conversations/open', [ConversationController::class, 'openOrCreate']);
    Route::get('conversations/{id}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::patch('conversations/{id}/close', [ConversationController::class, 'close'])->name('conversations.close');
    
    /**
     * Messages Routes
     */
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');

    /**
     * Attachments Routes
     */
    Route::post('attachments', [AttachmentController::class, 'store'])->name('attachments.store');
});

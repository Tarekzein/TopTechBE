<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ConversationController;
use Modules\Chat\Http\Controllers\MessageController;
use Modules\Chat\Http\Controllers\AttachmentController;
use Modules\Chat\Http\Controllers\AdminChatController;

Route::middleware(['auth:sanctum'])->group(function () {

    /**
     * Conversations Routes
     */
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::post('conversations', [ConversationController::class, 'store']);
    Route::post('conversations/open', [ConversationController::class, 'openOrCreate']);
    Route::get('conversations/{id}', [ConversationController::class, 'show']);
    Route::patch('conversations/{id}/close', [ConversationController::class, 'close']);
    
    /**
     * Messages Routes
     */
    Route::post('messages', [MessageController::class, 'store']);
    Route::patch('messages/{id}/read', [MessageController::class, 'markAsRead']);

    /**
     * Attachments Routes
     */
    Route::post('attachments', [AttachmentController::class, 'store']);

    /**
     * Admin Chat Routes (Super Admin only)
     */
    Route::prefix('admin')->group(function () {
        Route::get('conversations', [AdminChatController::class, 'getAdminConversations']);
        Route::get('conversations/unassigned', [AdminChatController::class, 'getUnassignedConversations']);
        Route::post('conversations/auto-assign', [AdminChatController::class, 'autoAssignConversation']);
        Route::get('chat-history', [AdminChatController::class, 'getChatHistory']);
        Route::get('chat-analytics', [AdminChatController::class, 'getChatAnalytics']);
        Route::post('conversations/assign', [AdminChatController::class, 'assignConversation']);
        Route::post('conversations/transfer', [AdminChatController::class, 'transferConversation']);
    });
});




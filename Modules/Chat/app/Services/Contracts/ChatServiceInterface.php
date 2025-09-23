<?php

namespace Modules\Chat\Services\Contracts;

use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;
use Illuminate\Http\UploadedFile;

interface ChatServiceInterface
{
    public function openOrCreateConversation(int $userId, ?int $adminId = null): Conversation;
    public function sendMessage(int $conversationId, int $senderId, string $senderType, string $message, array $attachments = []): Message;
    public function markMessageAsRead(int $messageId): void;
}



<?php

namespace Modules\Chat\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Modules\Chat\Models\Attachment;
use Modules\Chat\Models\Message;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Repositories\Contracts\ConversationRepositoryInterface;
use Modules\Chat\Repositories\Contracts\MessageRepositoryInterface;
use Modules\Chat\Services\Contracts\ChatServiceInterface;
use Modules\Common\Services\CloudImageService;
use Modules\Chat\Events\MessageSent;

class ChatService implements ChatServiceInterface
{
    public function __construct(
        protected ConversationRepositoryInterface $conversations,
        protected MessageRepositoryInterface $messages,
        protected CloudImageService $cloudImages,
    ) {}

    public function openOrCreateConversation(int $userId, ?int $adminId = null): Conversation
    {
        $conversation = $this->conversations->findOpenByUserId($userId);
        if (!$conversation) {
            $conversation = $this->conversations->create([
                'user_id' => $userId,
                'admin_id' => $adminId,
                'status' => 'open',
            ]);
        }
        return $conversation;
    }

    public function sendMessage(int $conversationId, int $senderId, string $senderType, string $message, array $attachments = []): Message
    {
        return DB::transaction(function () use ($conversationId, $senderId, $senderType, $message, $attachments) {
            $msg = $this->messages->create([
                'conversation_id' => $conversationId,
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'message' => $message,
            ]);

            foreach ($attachments as $file) {
                if ($file instanceof UploadedFile) {
                    $upload = $this->cloudImages->upload($file->getRealPath(), [
                        'folder' => 'chat_attachments',
                        'resource_type' => 'auto',
                    ]);
                    Attachment::create([
                        'message_id' => $msg->id,
                        'file_path' => $upload['secure_url'] ?? '',
                        'file_type' => $file->getClientMimeType(),
                    ]);
                }
            }

            $this->conversations->touch($msg->conversation);

            $msg->load('attachments');
            event(new MessageSent($msg));

            return $msg;
        });
    }

    public function markMessageAsRead(int $messageId): void
    {
        $this->messages->markAsRead($messageId);
    }
}



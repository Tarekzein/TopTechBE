<?php

namespace Modules\Chat\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Repositories\Contracts\ConversationRepositoryInterface;
use Modules\Chat\Services\Contracts\ConversationServiceInterface;

class ConversationService implements ConversationServiceInterface
{
    public function __construct(
        protected ConversationRepositoryInterface $conversations
    ) {}

    public function indexForUser(int $userId, bool $isAdmin): iterable
    {
        if ($isAdmin) {
            return Conversation::with(['messages.attachments', 'user'])
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        return Conversation::with('messages.attachments')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function storeIfNotExists(int $userId, ?int $adminId = null): Conversation
    {
        $existing = $this->conversations->findOpenByUserId($userId);
        if ($existing) {
            return $existing;
        }
        return $this->conversations->create([
            'user_id' => $userId,
            'admin_id' => $adminId,
            'status' => 'open',
        ]);
    }

    public function show(int $id): Conversation
    {
        return $this->conversations->findById($id, ['messages.attachments', 'user']);
    }

    public function close(int $id): Conversation
    {
        $conversation = $this->conversations->findById($id);
        $conversation->update(['status' => 'closed']);
        return $conversation;
    }
}



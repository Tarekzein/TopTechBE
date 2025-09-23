<?php

namespace Modules\Chat\Repositories\Eloquent;

use Modules\Chat\Models\Conversation;
use Modules\Chat\Repositories\Contracts\ConversationRepositoryInterface;

class EloquentConversationRepository implements ConversationRepositoryInterface
{
    public function findById(int $id, array $with = []): Conversation
    {
        return Conversation::with($with)->findOrFail($id);
    }

    public function findOpenByUserId(int $userId): ?Conversation
    {
        return Conversation::where('user_id', $userId)
            ->where('status', 'open')
            ->first();
    }

    public function create(array $data): Conversation
    {
        return Conversation::create($data);
    }

    public function touch(Conversation $conversation): void
    {
        $conversation->touch();
    }
}



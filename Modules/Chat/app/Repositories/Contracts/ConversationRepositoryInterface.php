<?php

namespace Modules\Chat\Repositories\Contracts;

use Modules\Chat\Models\Conversation;

interface ConversationRepositoryInterface
{
    public function findById(int $id, array $with = []): Conversation;
    public function findOpenByUserId(int $userId): ?Conversation;
    public function create(array $data): Conversation;
    public function touch(Conversation $conversation): void;
}



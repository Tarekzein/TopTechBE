<?php

namespace Modules\Chat\Services\Contracts;

use Modules\Chat\Models\Conversation;

interface ConversationServiceInterface
{
    public function indexForUser(int $userId, bool $isAdmin): iterable;
    public function storeIfNotExists(int $userId, ?int $adminId = null): Conversation;
    public function show(int $id): Conversation;
    public function close(int $id): Conversation;
}



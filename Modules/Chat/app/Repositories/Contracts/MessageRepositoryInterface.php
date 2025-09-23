<?php

namespace Modules\Chat\Repositories\Contracts;

use Modules\Chat\Models\Message;

interface MessageRepositoryInterface
{
    public function create(array $data): Message;
    public function markAsRead(int $id): void;
}



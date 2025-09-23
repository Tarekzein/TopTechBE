<?php

namespace Modules\Chat\Repositories\Eloquent;

use Modules\Chat\Models\Message;
use Modules\Chat\Repositories\Contracts\MessageRepositoryInterface;

class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function create(array $data): Message
    {
        return Message::create($data);
    }

    public function markAsRead(int $id): void
    {
        Message::where('id', $id)->update(['read_at' => now()]);
    }
}



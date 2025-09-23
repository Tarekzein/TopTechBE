<?php

namespace Modules\Chat\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Chat\Models\Message;

class ChatMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'chat_message',
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'body' => $this->message->message,
            'sender_id' => $this->message->sender_id,
            'sender_type' => $this->message->sender_type,
        ];
    }
}



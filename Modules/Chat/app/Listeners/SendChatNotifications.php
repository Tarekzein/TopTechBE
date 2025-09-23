<?php

namespace Modules\Chat\Listeners;

use Modules\Chat\Events\MessageSent;
use Modules\User\Services\FirebaseService;
use App\Models\User;

class SendChatNotifications
{
    public function __construct(protected FirebaseService $firebase)
    {
    }

    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $sender = User::find($message->sender_id);

        $recipients = collect();
        if ($sender && $sender->hasRole('user')) {
            $recipients = User::role(['admin', 'super-admin'])->get();
        } else {
            $user = $message->conversation->user;
            if ($user) $recipients->push($user);
        }

        foreach ($recipients as $recipient) {
            try {
                // Adjust to your FirebaseService API signature
                $this->firebase->notifyUser(
                    $recipient->id,
                    'New chat message',
                    $message->message,
                    [
                        'conversation_id' => $message->conversation_id,
                        'message_id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'sender_type' => $message->sender_type,
                    ]
                );
            } catch (\Throwable $e) {
                // Swallow notification errors to not affect request flow
            }
        }
    }
}



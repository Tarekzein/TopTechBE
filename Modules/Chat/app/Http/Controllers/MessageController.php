<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Models\Message;
use Modules\Chat\Models\Conversation;
use App\Models\User;
use Modules\Chat\Services\Contracts\ChatServiceInterface;

class MessageController extends Controller
{
    public function __construct(protected ChatServiceInterface $chat)
    {}

    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'sender_type' => 'required|in:user,admin,super-admin',
            'message' => 'required|string|max:5000',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt',
        ]);

        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Check permissions
        if (!$user->hasRole(['admin', 'super-admin']) && $conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate sender_type matches user role
        if ($user->hasRole('user') && $request->sender_type !== 'user') {
            return response()->json(['error' => 'Invalid sender type'], 400);
        }

        try {
            $attachments = $request->hasFile('attachments') ? $request->file('attachments') : [];
            $message = $this->chat->sendMessage($conversation->id, $user->id, $request->sender_type, $request->message, $attachments);
            return response()->json($message, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }

    private function sendNotifications(Message $message, User $sender)
    {
        $content = $message->message;
        $recipients = collect();

        if ($sender->hasRole('user')) {
            $recipients = User::role(['admin', 'super-admin'])->get();
        } else {
            $user = User::find($message->conversation->user_id);
            if ($user) $recipients->push($user);
        }

        // Send notifications to recipients
        foreach ($recipients as $recipient) {
            // Your existing notification logic here
        }
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        $message = Message::findOrFail($id);
        
        // Check permissions
        if (!$user->hasRole(['admin', 'super-admin']) && $message->conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->chat->markMessageAsRead($message->id);
        
        return response()->json(['message' => 'Message marked as read']);
    }
}



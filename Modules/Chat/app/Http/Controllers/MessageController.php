<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Modules\Chat\Models\Message;
use Modules\Chat\Models\Attachment;
use App\Models\User;
use Modules\User\Services\FirebaseService;
use Modules\Common\Services\CloudImageService;
use Modules\Common\Notifications\GenericNotification;

class MessageController extends Controller
{
    protected FirebaseService $firebase;
    protected CloudImageService $cloudImageService;

    public function __construct(FirebaseService $firebase, CloudImageService $cloudImageService)
    {
        $this->firebase = $firebase;
        $this->cloudImageService = $cloudImageService;
    }

   public function store(Request $request)
{
    $request->validate([
        'conversation_id' => 'required|exists:conversations,id',
        'sender_type'     => 'required|in:user,admin,super-admin',
        'message'         => 'required|string',
        'attachments.*'   => 'nullable|file|max:10240',
    ]);

    $message = Message::create([
        'conversation_id' => $request->conversation_id,
        'sender_type'     => $request->sender_type,
        'sender_id'       => Auth::id(),
        'message'         => $request->message,
    ]);

    // Upload attachments
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $uploadResult = $this->cloudImageService->upload($file->getRealPath(), [
                'folder' => 'chat_attachments',
                'resource_type' => 'auto'
            ]);

            Attachment::create([
                'message_id' => $message->id,
                'file_path'  => $uploadResult['secure_url'],
                'file_type'  => $file->getClientMimeType(),
            ]);
        }
    }

    // ===== Notification Logic =====
    $sender = Auth::user();
    $content = $message->message;

    // Determine recipients
    $recipients = collect();

    if ($sender->hasRole('user')) {
        $recipients = User::role(['admin', 'super-admin'])->get();
    } else {
        $user = User::find($message->conversation->user_id);
        if ($user) $recipients->push($user);
    }

    // Send one notification via all channels (mail, db, fcm)
    foreach ($recipients as $recipient) {
        Notification::send($recipient, new GenericNotification(
            type: 'message',
            content: $content,
            notificationId: $message->id,
            isAlert: true,
            fcmData: [
                'conversation_id' => $message->conversation_id,
                'message_id'      => $message->id,
            ]
        ));
    }

    return response()->json($message->load('attachments'), 201);
}

}

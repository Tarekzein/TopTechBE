<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Models\Message;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'sender_type'     => 'required|in:user,admin',
            'message'         => 'required|string',
        ]);

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_type'     => $request->sender_type,
            'sender_id'       => Auth::id(), 
            'message'         => $request->message,
        ]);

        return response()->json($message->load('attachments'), 201);
    }
}

<?php

namespace Modules\Chat\Http\Controllers;


use App\Http\Controllers\Controller;
use Modules\Chat\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ConversationController extends Controller
{
    public function index()
    {
        return Conversation::with('messages')->get();
    }

    public function store(Request $request)
    {
        return Conversation::create([
            'user_id' =>Auth::id(), 
            'status' => 'open',
        ]);
    }

    public function show($id)
    {
        return Conversation::with('messages.attachments')->findOrFail($id);
    }

    public function close($id)
    {
        $conversation = Conversation::findOrFail($id);
        $conversation->update(['status' => 'closed']);

        return $conversation;
    }
}

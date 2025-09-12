<?php

namespace Modules\Chat\Http\Controllers;


use App\Http\Controllers\Controller;
use Modules\Chat\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ConversationController extends Controller
{
    public function openOrCreate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $adminId = Auth::id();
        $userId  = $request->user_id;

        // شوف لو فيه محادثة قديمة بين اليوزر والـ admin
        $conversation = Conversation::where('user_id', $userId)
            ->where('admin_id', $adminId)
            ->first();

        if (!$conversation) {
            // لو مش موجودة، نعمل واحدة جديدة
            $conversation = Conversation::create([
                'user_id'  => $userId,
                'admin_id' => $adminId,
                'status'   => 'open',
            ]);
        }

        return response()->json($conversation, 200);
    }
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

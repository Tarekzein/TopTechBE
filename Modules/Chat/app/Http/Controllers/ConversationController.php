<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Chat\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Services\Contracts\ConversationServiceInterface;
use Modules\Chat\Http\Requests\OpenOrCreateConversationRequest;

class ConversationController extends Controller
{
    public function __construct(protected ConversationServiceInterface $service)
    {}
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole(['admin', 'super-admin']);
        return $this->service->indexForUser($user->id, $isAdmin);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $conversation = $this->service->storeIfNotExists($user->id);
        return response()->json($conversation, 200);
    }

    public function openOrCreate(OpenOrCreateConversationRequest $request)
    {
        $user = Auth::user();
        $userId = $request->user_id;

        // If it's a regular user, they can only create their own conversation
        if (!$user->hasRole(['admin', 'super-admin']) && $user->id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation = $this->service->storeIfNotExists($userId, $user->hasRole(['admin','super-admin']) ? $user->id : null);
        return response()->json($conversation->load('messages.attachments'), 200);
    }

    public function show($id)
    {
        $user = Auth::user();
        $conversation = $this->service->show($id);

        // Check permissions
        if (!$user->hasRole(['admin', 'super-admin']) && $conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $conversation;
    }

    public function close($id)
    {
        $user = Auth::user();
        $conversation = Conversation::findOrFail($id);

        // Check permissions
        if (!$user->hasRole(['admin', 'super-admin']) && $conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $this->service->close($id);
    }
}


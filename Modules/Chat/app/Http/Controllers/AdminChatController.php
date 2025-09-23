<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminChatController extends Controller
{
    public function getChatHistory(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['super-admin' , 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Conversation::with([
            // Avoid selecting columns that may not exist (e.g., name)
            'user',
            'admin',
            'messages.sender',
            'messages' => function($q) {
                $q->select('id', 'conversation_id', 'sender_id', 'sender_type', 'message', 'created_at');
            }
        ]);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->admin_id) {
            $query->where('admin_id', $request->admin_id);
        }

        $conversations = $query->orderBy('updated_at', 'desc')
            ->paginate($request->per_page ?? 20);

        // Transform data to include additional info
        $transformedData = $conversations->getCollection()->map(function ($conversation) {
            $messageCount = $conversation->messages->count();
            $duration = $this->calculateDuration($conversation);
            $participants = $this->getParticipants($conversation);

            return [
                'conversation' => $conversation,
                'participants' => $participants,
                'message_count' => $messageCount,
                'duration' => $duration,
                'status_changes' => $this->getStatusChanges($conversation->id),
            ];
        });

        return response()->json($transformedData);
    }

    public function getChatAnalytics(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['super-admin' , 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Safely parse incoming dates (supports ISO 8601) with fallbacks
        try {
            $dateFrom = $request->filled('date_from')
                ? Carbon::parse($request->date_from)
                : Carbon::now()->subDays(30);
        } catch (\Throwable $e) {
            $dateFrom = Carbon::now()->subDays(30);
        }
        try {
            $dateTo = $request->filled('date_to')
                ? Carbon::parse($request->date_to)
                : Carbon::now();
        } catch (\Throwable $e) {
            $dateTo = Carbon::now();
        }

        // Ensure correct order
        if ($dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy(), $dateFrom->copy()];
        }

        $analytics = [
            'total_conversations' => Conversation::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'active_conversations' => Conversation::where('status', 'open')->count(),
            'closed_conversations' => Conversation::where('status', 'closed')
                ->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'total_messages' => Message::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'average_response_time' => $this->calculateAverageResponseTime($dateFrom, $dateTo),
            'top_admins' => $this->getTopAdmins($dateFrom, $dateTo),
            'daily_stats' => $this->getDailyStats($dateFrom, $dateTo),
        ];

        return response()->json($analytics);
    }

    public function assignConversation(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'admin_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'super-admin' , 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation = Conversation::findOrFail($request->conversation_id);
        $conversation->update(['admin_id' => $request->admin_id]);

        return response()->json(['message' => 'Conversation assigned successfully']);
    }

    public function transferConversation(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'new_admin_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'super-admin' , 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // Log the transfer
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => $user->hasRole('super-admin' , 'admin') ? 'super-admin' : 'admin',
            'message' => "Chat transferred from {$user->name} to admin ID: {$request->new_admin_id}",
        ]);

        $conversation->update(['admin_id' => $request->new_admin_id]);

        return response()->json(['message' => 'Conversation transferred successfully']);
    }

    private function calculateDuration($conversation)
    {
        $start = Carbon::parse($conversation->created_at);
        $end = $conversation->status === 'closed' 
            ? Carbon::parse($conversation->updated_at) 
            : Carbon::now();
        
        return $start->diffForHumans($end, true);
    }

    private function getParticipants($conversation)
    {
        $adminIds = $conversation->messages
            ->whereIn('sender_type', ['admin', 'super-admin'])
            ->pluck('sender_id')
            ->unique();

        $admins = \App\Models\User::whereIn('id', $adminIds)
            ->select('id', 'email')
            ->get()
            ->map(function($admin) use ($conversation) {
                $role = $conversation->messages
                    ->where('sender_id', $admin->id)
                    ->first()->sender_type ?? 'admin';
                
                $admin->role = $role;
                // Provide a fallback name if not present in schema
                if (!isset($admin->name)) {
                    $admin->name = $admin->email;
                }
                return $admin;
            });

        return [
            'user' => $conversation->user,
            'admins' => $admins,
        ];
    }

    private function getStatusChanges($conversationId)
    {
        // This would require a separate table to track status changes
        // For now, return empty array
        return [];
    }

    private function calculateAverageResponseTime($dateFrom, $dateTo)
    {
        // Calculate average time between user message and admin response
        $conversations = Conversation::whereBetween('created_at', [$dateFrom, $dateTo])
            ->with('messages')
            ->get();

        $totalResponseTime = 0;
        $responseCount = 0;

        foreach ($conversations as $conversation) {
            $messages = $conversation->messages->sortBy('created_at');
            $lastUserMessage = null;

            foreach ($messages as $message) {
                if ($message->sender_type === 'user') {
                    $lastUserMessage = $message;
                } elseif ($lastUserMessage && in_array($message->sender_type, ['admin', 'super-admin'])) {
                    $responseTime = Carbon::parse($message->created_at)
                        ->diffInMinutes(Carbon::parse($lastUserMessage->created_at));
                    $totalResponseTime += $responseTime;
                    $responseCount++;
                    $lastUserMessage = null;
                }
            }
        }

        if ($responseCount === 0) {
            return '0 minutes';
        }

        $averageMinutes = $totalResponseTime / $responseCount;
        
        if ($averageMinutes < 60) {
            return round($averageMinutes) . ' minutes';
        } else {
            return round($averageMinutes / 60, 1) . ' hours';
        }
    }

    private function getTopAdmins($dateFrom, $dateTo)
    {
        $rows = DB::table('messages')
            ->join('users', 'messages.sender_id', '=', 'users.id')
            ->whereIn('messages.sender_type', ['admin', 'super-admin'])
            ->whereBetween('messages.created_at', [$dateFrom, $dateTo])
            ->select('users.id', 'users.email', DB::raw('COUNT(*) as message_count'))
            ->groupBy('users.id', 'users.email')
            ->orderBy('message_count', 'desc')
            ->limit(5)
            ->get();

        // Ensure each row has a display name even if the "name" column is absent in schema
        return $rows->map(function ($r) {
            if (!isset($r->name)) {
                $r->name = $r->email;
            }
            return $r;
        });
    }

    private function getDailyStats($dateFrom, $dateTo)
    {
        $stats = [];
        $current = $dateFrom->copy();

        while ($current <= $dateTo) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $stats[] = [
                'date' => $current->format('Y-m-d'),
                'conversations' => Conversation::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'messages' => Message::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            ];

            $current->addDay();
        }

        return $stats;
    }

    public function getAdminConversations(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'super-admin' , 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Conversation::with([
            'user',
            'admin',
            'messages' => function($q) {
                $q->latest()->limit(1);
            }
        ]);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $conversations = $query->orderBy('updated_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($conversations);
    }

    public function getUnassignedConversations(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'super-admin' , 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversations = Conversation::with([
            'user',
            'messages' => function($q) {
                $q->latest()->limit(1);
            }
        ])
        ->whereNull('admin_id')
        ->where('status', 'open')
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json($conversations);
    }

    public function autoAssignConversation(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'super-admin' , 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // If already assigned, return current assignment
        if ($conversation->admin_id) {
            return response()->json($conversation->load(['user', 'admin']));
        }

        // Auto-assign to current admin if they're an admin
        if ($user->hasRole('admin' , 'super-admin' , 'admin')) {
            $conversation->update(['admin_id' => $user->id]);
        } else {
            // Super admin can assign to any available admin
            $availableAdmin = User::role('admin')->first();
            if ($availableAdmin) {
                $conversation->update(['admin_id' => $availableAdmin->id]);
            }
        }

        return response()->json($conversation->load(['user', 'admin']));
    }
}

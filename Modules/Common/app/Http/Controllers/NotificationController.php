<?php

namespace Modules\Common\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Common\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('receiver_id', $user->id)
            ->orWhere('is_admin', true)
            ->latest()
            ->get();

        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'       => 'required|string',
            'content'    => 'required|string',
            'receiver_id'=> 'nullable|exists:users,id',
            'is_alert'   => 'boolean',
            'is_admin'   => 'boolean',
        ]);

        $data['sender_id'] = $request->user()->id ?? null;

        $notification = Notification::create($data);

        return response()->json($notification, 201);
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read']);
    }
}

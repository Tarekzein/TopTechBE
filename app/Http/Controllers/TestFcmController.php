<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;

class TestFcmController extends Controller
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendTest(Request $request)
    {
        // استقبل token من الـ query string أو حطه ثابت للتجربة
        $deviceToken = $request->get('token'); 
        if (!$deviceToken) {
            return response()->json(['error' => 'Please provide token ?token=xxx'], 400);
        }

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(Notification::create('🚀 Test FCM', 'Hello from Laravel + Firebase!'))
            ->withData([
                'type' => 'test',
                'custom_key' => 'custom_value',
            ]);

        try {
            $this->messaging->send($message);
            return response()->json(['success' => 'Notification sent successfully']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

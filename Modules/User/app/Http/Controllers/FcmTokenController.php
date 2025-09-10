<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Models\FcmToken;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string',
        ]);

        $user = $request->user();

        // حفظ أو تحديث التوكين
        $fcmToken = FcmToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $user->id,
                'device_type' => $request->device_type,
            ]
        );

        return response()->json([
            'message' => 'FCM token saved successfully',
            'data' => $fcmToken
        ]);
    }
}

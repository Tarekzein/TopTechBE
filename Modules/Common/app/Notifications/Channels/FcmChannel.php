<?php

namespace Modules\Common\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Modules\User\Services\FirebaseService;

class FcmChannel
{
    public FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        $fcmData = $notification->fcmData ?? [];

        foreach ($notifiable->fcmTokens as $token) {
            if (!empty($token->token)) {
                try {
                    $this->firebase->sendNotification(
                        $token->token,
                        ucfirst($notification->type),
                        $notification->content,
                        $fcmData
                    );

                    Log::info("📲 Notification sent via FcmChannel", [
                        'user_id' => $notifiable->id,
                        'token' => $token->token,
                        'type' => $notification->type,
                        'content' => $notification->content,
                        'notification_id' => $notification->notificationId
                    ]);
                } catch (\Exception $e) {
                    Log::error("❌ FCM Channel error", [
                        'user_id' => $notifiable->id,
                        'token' => $token->token,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}

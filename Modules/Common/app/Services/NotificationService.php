<?php

namespace Modules\Common\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Common\Models\Notification;
use Modules\Common\Notifications\GenericNotification;

class NotificationService
{
    /**
     * إرجاع إشعارات المستخدم
     */
    public function getUserNotifications(int $userId, string $role = 'user')
    {
        $query = Notification::where('receiver_id', $userId);

        if ($role === 'admin') {
            $query->orWhere('is_admin', true);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * إرسال إشعار جديد
     */
    public function send(
        string $type,
        string $content,
        ?int $receiverId = null,
        ?int $senderId = null,
        bool $isAlert = false,
        bool $isAdmin = false,
        array $fcmData = []
    ) {
        $notificationId = (string) Str::uuid();

        // 1️⃣ نخزن في جدول notifications المخصص
        $notification = Notification::create([
            'id'          => $notificationId,
            'type'        => $type,
            'content'     => $content,
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'is_alert'    => $isAlert,
            'is_admin'    => $isAdmin,
        ]);

        // 2️⃣ نرسل عبر Laravel Notifications (mail, fcm)
        if ($receiverId) {
            $user = User::find($receiverId);

            if ($user) {
                try {
                    $user->notify(new GenericNotification(
                        $type,
                        $content,
                        $notification->id,
                        $isAlert,
                        $fcmData
                    ));

                    Log::info("✅ Notification dispatched", [
                        'notification_id' => $notification->id,
                        'receiver_id' => $receiverId,
                        'type' => $type,
                    ]);
                } catch (\Exception $e) {
                    Log::error("❌ Failed to send notification", [
                        'error' => $e->getMessage(),
                        'notification_id' => $notification->id,
                    ]);
                }
            }
        }

        return $notification;
    }

    /**
     * تعليم الإشعار كمقروء
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->update([
            'read_at' => now(),
        ]);
    }
}

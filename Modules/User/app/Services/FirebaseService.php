<?php

namespace Modules\User\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\DB;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));
        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($fcmToken, $title, $body, $data = [])
    {
        $message = CloudMessage::withTarget('token', $fcmToken)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        return $this->messaging->send($message);
    }

    /**
     * Convenience: notify a user by user id, iterating over all stored FCM tokens.
     * Silently continues on individual token errors.
     */
    public function notifyUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DB::table('fcm_tokens')
            ->where('user_id', $userId)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values();

        foreach ($tokens as $token) {
            try {
                $this->sendNotification($token, $title, $body, $data);
            } catch (\Throwable $e) {
                // ignore failed token
            }
        }
    }
}

<?php

namespace Modules\Common\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Common\Notifications\Channels\FcmChannel;

class GenericNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $type;
    public string $content;
    public int $notificationId;
    public bool $isAlert;
    public array $fcmData;

    public function __construct(
        string $type,
        string $content,
        int $notificationId,
        bool $isAlert = false,
        array $fcmData = []
    ) {
        $this->type = $type;
        $this->content = $content;
        $this->notificationId = $notificationId;
        $this->isAlert = $isAlert;
        $this->fcmData = $fcmData;
    }

    public function via($notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($notifiable->fcmTokens->isNotEmpty()) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info("📧 Sending email notification", [
            'to' => $notifiable->email,
            'type' => $this->type,
            'content' => $this->content,
        ]);

        return (new MailMessage)
            ->subject("New " . ucfirst($this->type) . " - " . config('app.name'))
            ->line($this->content)
            ->line($this->isAlert ? "⚠️ Important alert." : "ℹ️ Info notification.")
            ->line('Thank you for using ' . config('app.name'));
    }

    public function toDatabase($notifiable): array
    {
        Log::info("💾 Storing notification in DB", [
            'user_id' => $notifiable->id,
            'type' => $this->type,
            'content' => $this->content,
        ]);

        return [
            'id'          => (string) Str::uuid(),
            'type'        => $this->type,
            'content'     => $this->content,
            'sender_id'   => auth()->id(),
            'receiver_id' => $notifiable->id,
            'is_alert'    => $this->isAlert,
            'is_admin'    => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

    public function toFcm($notifiable)
    {
        $firebase = app(\Modules\User\Services\FirebaseService::class);

        foreach ($notifiable->fcmTokens as $token) {
            if (!empty($token->token)) {
                try {
                    $firebase->sendNotification(
                        $token->token,
                        ucfirst($this->type),
                        $this->content,
                        $this->fcmData
                    );

                    Log::info("📲 FCM notification sent", [
                        'user_id' => $notifiable->id,
                        'token' => $token->token,
                        'type' => $this->type,
                        'content' => $this->content,
                    ]);
                } catch (\Exception $e) {
                    Log::error("❌ FCM send error", [
                        'user_id' => $notifiable->id,
                        'token' => $token->token,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}

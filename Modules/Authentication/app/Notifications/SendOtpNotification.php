<?php

namespace Modules\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your OTP Code - ' . config('app.name'))
            ->line('Your OTP code is: ' . $this->otp)
            ->line('This code will expire in 15 minutes.')
            ->line('If you did not request this, please ignore this message.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'otp' => $this->otp,
            'message' => 'Your OTP code is: ' . $this->otp,
            'expires_at' => now()->addMinutes(15),
            'action_url' => null,
            'type' => 'otp_notification'
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'otp' => $this->otp,
            'message' => 'OTP sent successfully',
            'expires_at' => now()->addMinutes(15)
        ];
    }
}
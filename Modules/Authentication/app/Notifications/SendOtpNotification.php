<?php

namespace Modules\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $otp;
    public $channel;

    public function __construct(string $otp, string $channel = 'mail')
    {
        $this->otp = $otp;
        $this->channel = $channel;
    }

    public function via($notifiable)
    {
        return [$this->channel];
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

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'otp' => $this->otp,
            'message' => 'Your OTP code has been sent.',
            'created_at' => now()
        ]);
    }

    public function toVonage($notifiable)
    {
        return (new VonageMessage)
            ->content('Your OTP code is: ' . $this->otp . '. Expires in 15 minutes.');
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
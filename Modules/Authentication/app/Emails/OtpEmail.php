<?php

namespace Modules\Authentication\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Your Password Reset OTP')
                    ->view('authentication::emails.otp')
                    ->with(['otp' => $this->otp]);
    }
}
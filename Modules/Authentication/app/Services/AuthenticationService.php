<?php

namespace Modules\Authentication\Services;

use Modules\Authentication\Interfaces\AuthenticationRepositoryInterface;
use Modules\Authentication\Interfaces\AuthenticationServiceInterface;
use Modules\Vendor\Interfaces\VendorRepositoryInterface;
use Modules\Authentication\Models\OTP;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Authentication\Emails\OtpEmail;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Notifications\SendOtpNotification;
class AuthenticationService implements AuthenticationServiceInterface
{
    protected $auth_repository;
    protected $vendor_repository;
    public function __construct(AuthenticationRepositoryInterface $auth_repository, VendorRepositoryInterface $vendor_repository)
    {
        $this->auth_repository = $auth_repository;
        $this->vendor_repository = $vendor_repository;
    }

    public function register(array $data)
    {
        return $this->auth_repository->register($data);
    }

    public function vendorRegister(array $data)
    {
        $reponse= $this->auth_repository->vendorRegister($data);
        $data["user_id"]= $reponse['user']->id;
        $vendor= $this->vendor_repository->create($data);
        $reponse['user']->vendor= $vendor;
        return $reponse;
    }
    public function login(array $credentials)
    {
        return $this->auth_repository->login($credentials);
    }

    public function logout($user)
    {
        return $this->auth_repository->logout($user);
    }
    public function dashboardLogin(array $credentials)
    {
        return $this->auth_repository->dashboardLogin($credentials);
    }
    public function forgotPassword(string $email)
{
    // Delete any existing OTPs for this email
    DB::table('otps')->where('email', $email)->delete();

    // Generate OTP (6 digits)
    $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Create OTP record
    $token = Str::random(60);
    $expiresAt = Carbon::now()->addMinutes(15);
    
    DB::table('otps')->insert([
        'email' => $email,
        'otp' => $otpCode,
        'token' => $token,
        'expires_at' => $expiresAt,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Find user and send notification
    $user = User::where('email', $email)->first();
    
    if ($user) {
        try {
            // Send OTP notification to mail and database
            $user->notify(new SendOtpNotification($otpCode));
            
            Log::info("OTP sent to: " . $email);
        } catch (Exception $e) {
            Log::error("Notification failed: " . $e->getMessage());
            // Continue anyway - OTP is stored
        }
    }

    return [
        'email' => $email,
        'expires_at' => $expiresAt,
        'token' => $token,
        'message' => 'OTP sent successfully'
    ];
}

    /**
     * Verify OTP
     */
    public function verifyOtp(string $email, string $otp)
    {
        $otpRecord = OTP::where('email', $email)
            ->where('otp', $otp)
            ->first();

        if (!$otpRecord) {
            throw new Exception('Invalid OTP');
        }

        if ($otpRecord->isExpired()) {
            throw new Exception('OTP has expired');
        }

        if ($otpRecord->verified_at) {
            throw new Exception('OTP has already been used');
        }

        // Mark OTP as verified
        $otpRecord->update(['verified_at' => Carbon::now()]);

        return [
            'email' => $email,
            'verified' => true,
            'token' => $otpRecord->token
        ];
    }

    /**
     * Reset password
     */
    public function resetPassword(array $data)
    {
        // Verify OTP first
        $otpRecord = OTP::where('email', $data['email'])
            ->where('otp', $data['otp'])
            ->where('token', $data['token'] ?? null)
            ->first();

        if (!$otpRecord) {
            throw new Exception('Invalid OTP or token');
        }

        if (!$otpRecord->verified_at) {
            throw new Exception('OTP not verified');
        }

        if ($otpRecord->isExpired()) {
            throw new Exception('OTP has expired');
        }

        // Find user and update password
        $user = User::where('email', $data['email'])->first();
        
        if (!$user) {
            throw new Exception('User not found');
        }

        $user->update([
            'password' => Hash::make($data['password'])
        ]);

        // Delete the used OTP
        $otpRecord->delete();

        return [
            'email' => $data['email'],
            'reset' => true
        ];
    }

    /**
     * Send OTP email
     */
    protected function sendOtpEmail(string $email, string $otp)
    {
        try {
            Mail::to($email)->send(new OtpEmail($otp));
        } catch (Exception $e) {
            Log::error('Failed to send OTP email: ' . $e->getMessage());
            throw new Exception('Failed to send OTP email');
        }
    }
}

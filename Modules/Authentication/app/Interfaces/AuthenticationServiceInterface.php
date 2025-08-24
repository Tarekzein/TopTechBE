<?php

namespace Modules\Authentication\Interfaces;

interface AuthenticationServiceInterface
{
    public function register(array $data);

    public function vendorRegister(array $data);
    public function login(array $credentials);
    public function logout($user);
    public function dashboardLogin(array $credentials);
    public function forgotPassword(string $email);
    public function verifyOtp(string $email, string $otp);
    public function resetPassword(array $data);
}

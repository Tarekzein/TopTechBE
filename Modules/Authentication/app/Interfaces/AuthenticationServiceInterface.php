<?php

namespace Modules\Authentication\Interfaces;

interface AuthenticationServiceInterface
{
    public function register(array $data);
    public function login(array $credentials);
    public function logout($user);
}

<?php

namespace Modules\Authentication\Interfaces;

interface AuthenticationRepositoryInterface
{
    public function register(array $data);
    public function vendorRegister(array $data);
    public function login(array $credentials);
    public function logout($user);
}

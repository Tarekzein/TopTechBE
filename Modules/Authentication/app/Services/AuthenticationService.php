<?php

namespace Modules\Authentication\Services;

use Modules\Authentication\Interfaces\AuthenticationRepositoryInterface;
use Modules\Authentication\Interfaces\AuthenticationServiceInterface;

class AuthenticationService implements AuthenticationServiceInterface
{
    protected $auth_repository;

    public function __construct(AuthenticationRepositoryInterface $auth_repository)
    {
        $this->auth_repository = $auth_repository;
    }

    public function register(array $data)
    {
        return $this->auth_repository->register($data);
    }

    public function login(array $credentials)
    {
        return $this->auth_repository->login($credentials);
    }

    public function logout($user)
    {
        return $this->auth_repository->logout($user);
    }
}

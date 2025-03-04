<?php

namespace Modules\Authentication\Services;

use Modules\Authentication\Interfaces\AuthenticationRepositoryInterface;
use Modules\Authentication\Interfaces\AuthenticationServiceInterface;
use Modules\Vendor\Interfaces\VendorRepositoryInterface;

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
}

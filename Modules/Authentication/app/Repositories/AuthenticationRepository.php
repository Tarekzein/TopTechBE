<?php

namespace Modules\Authentication\Repositories;

use Modules\Authentication\Interfaces\AuthenticationRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Modules\User\Interfaces\UserRepositoryInterface;

class AuthenticationRepository implements AuthenticationRepositoryInterface
{
    protected $users_repository;

    public function __construct(UserRepositoryInterface $users_repository)
    {
        $this->users_repository = $users_repository;
    }
    public function register(array $data)
    {
        $user = $this->users_repository->create($data);
        $user->assignRole('customer');
        $user->load('roles');
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function vendorRegister(array $data)
    {
        $user = $this->users_repository->create($data);
        $user->assignRole('vendor');
        $user->load('roles');
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
    public function login(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout($user)
    {
        $user->tokens()->delete();
        return ['message' => 'Logged out successfully'];
    }
}

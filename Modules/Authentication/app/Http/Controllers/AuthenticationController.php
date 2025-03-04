<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Authentication\Interfaces\AuthenticationServiceInterface;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    protected $auth_service;

    public function __construct(AuthenticationServiceInterface $auth_service)
    {
        $this->auth_service = $auth_service;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        return response()->json($this->auth_service->register($data), 201);
    }

    public function vendorRegister(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'store_name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string',
            'logo' => 'sometimes',
            'banner' => 'sometimes',
        ]);

        return response()->json($this->auth_service->vendorRegister($data), 201);
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        return response()->json($this->auth_service->login($credentials), 200);
    }

    public function logout()
    {
        return response()->json($this->auth_service->logout(Auth::user()), 200);
    }
}

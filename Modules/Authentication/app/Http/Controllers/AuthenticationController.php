<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Authentication\Interfaces\AuthenticationServiceInterface;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    protected $auth_service;

    public function __construct(AuthenticationServiceInterface $auth_service)
    {
        $this->auth_service = $auth_service;
    }

    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
            ]);

            $result = $this->auth_service->register($data);
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function vendorRegister(Request $request)
    {
        try {
            $data = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
                'corporate_name' => 'required|string|max:255',
                'tax_number' => 'required|string',
                'device_type' => 'required|string',
                'with_components' => 'boolean',
            ]);

            $result = $this->auth_service->vendorRegister($data);
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Vendor registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $result = $this->auth_service->login($credentials);
            
            if (isset($result['message']) && $result['message'] === 'Invalid credentials') {
                return response()->json($result, 401);
            }
            
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'message' => 'No authenticated user found'
                ], 401);
            }

            $result = $this->auth_service->logout(Auth::user());
            return response()->json($result, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

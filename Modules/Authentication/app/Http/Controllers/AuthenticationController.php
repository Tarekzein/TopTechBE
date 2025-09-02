<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Authentication\Interfaces\AuthenticationServiceInterface;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Validation\ValidationException;
use App\Models\User;
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
            
            // If the result is already a JsonResponse, return it directly
            if ($result instanceof \Illuminate\Http\JsonResponse) {
                return $result;
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

    public function dashboardLogin(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $result = $this->auth_service->dashboardLogin($credentials);
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            $statusCode = 401;
            if (str_contains($e->getMessage(), 'not authorized')) {
                $statusCode = 403;
            }
            return response()->json([
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }
// frogot pass logic
 public function forgotPassword(Request $request)
{
    try {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            // Remove channel parameter since it's no longer needed
        ]);

        $result = $this->auth_service->forgotPassword($data['email']);
        
        return response()->json([
            'message' => 'OTP has been sent to your email and saved in notifications',
            'data' => $result
        ], 200);
        
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Failed to process request',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Verify OTP for password reset
     */
    public function verifyOtp(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|string|size:6', // Assuming 6-digit OTP
            ]);

            $result = $this->auth_service->verifyOtp($data['email'], $data['otp']);
            
            return response()->json([
                'message' => 'OTP verified successfully',
                'data' => $result
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'OTP verification failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reset password after OTP verification
     */
    public function resetPassword(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|string|size:6',
                'password' => 'required|min:6|confirmed',
                'password_confirmation' => 'required',
            ]);

            $result = $this->auth_service->resetPassword($data);
            
            return response()->json([
                'message' => 'Password reset successfully',
                'data' => $result
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Password reset failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    // admin
    public function adminRegister(Request $request)
{
    try {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|min:6|confirmed',
        ]);

        $result = $this->auth_service->adminRegister($data);

        return response()->json($result, 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Admin registration failed',
            'error'   => $e->getMessage()
        ], 500);
    }
}

public function updateUserRoles(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'roles'   => 'required|array',
                'roles.*' => 'string|exists:roles,name', // كل role لازم يكون متسجل في جدول roles
            ]);

            $user = User::findOrFail($id);

            // استبدال كل الـ roles باللي جاي في الريكوست
            $user->syncRoles($data['roles']);
            $user->load('roles'); // نرجع الأدوار مع اليوزر

            return response()->json([
                'message' => 'User roles updated successfully',
                'user'    => $user
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update user roles',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}


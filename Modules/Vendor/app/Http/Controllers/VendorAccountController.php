<?php

namespace Modules\Vendor\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class VendorAccountController extends Controller
{
    /**
     * Get current vendor's account data
     */
    public function getAccountData(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor account not found'
                ], 404);
            }

            $accountData = [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ],
                'vendor' => [
                    'id' => $user->vendor->id,
                    'corporate_name' => $user->vendor->corporate_name,
                    'tax_number' => $user->vendor->tax_number,
                    'device_type' => $user->vendor->device_type,
                    'with_components' => $user->vendor->with_components,
                    'is_active' => $user->vendor->is_active,
                    'is_verified' => $user->vendor->is_verified,
                    'slug' => $user->vendor->slug,
                    'created_at' => $user->vendor->created_at,
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $accountData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch account data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user account data
     */
    public function updateUserData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor account not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'current_password' => 'nullable|string',
                'new_password' => 'nullable|string|min:8|confirmed',
                'new_password_confirmation' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check current password if changing password
            if ($request->filled('new_password')) {
                if (!$request->filled('current_password')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is required to change password',
                        'errors' => ['current_password' => ['Current password is required']]
                    ], 422);
                }

                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect',
                        'errors' => ['current_password' => ['Current password is incorrect']]
                    ], 422);
                }
            }

            // Update user data
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            
            if ($request->filled('new_password')) {
                $user->password = Hash::make($request->new_password);
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User account updated successfully',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'updated_at' => $user->updated_at,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update vendor data
     */
    public function updateVendorData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor account not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'corporate_name' => 'required|string|max:255',
                'tax_number' => 'nullable|string|max:255',
                'device_type' => 'nullable|string|max:255',
                'with_components' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update vendor data
            $vendor = $user->vendor;
            $vendor->corporate_name = $request->corporate_name;
            $vendor->tax_number = $request->tax_number;
            $vendor->device_type = $request->device_type;
            $vendor->with_components = $request->boolean('with_components', false);
            $vendor->save();

            return response()->json([
                'success' => true,
                'message' => 'Vendor data updated successfully',
                'data' => [
                    'id' => $vendor->id,
                    'corporate_name' => $vendor->corporate_name,
                    'tax_number' => $vendor->tax_number,
                    'device_type' => $vendor->device_type,
                    'with_components' => $vendor->with_components,
                    'is_active' => $vendor->is_active,
                    'is_verified' => $vendor->is_verified,
                    'slug' => $vendor->slug,
                    'updated_at' => $vendor->updated_at,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vendor data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update both user and vendor data
     */
    public function updateAccountData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor account not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                // User data validation
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'current_password' => 'nullable|string',
                'new_password' => 'nullable|string|min:8|confirmed',
                'new_password_confirmation' => 'nullable|string',
                
                // Vendor data validation
                'corporate_name' => 'required|string|max:255',
                'tax_number' => 'nullable|string|max:255',
                'device_type' => 'nullable|string|max:255',
                'with_components' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check current password if changing password
            if ($request->filled('new_password')) {
                if (!$request->filled('current_password')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is required to change password',
                        'errors' => ['current_password' => ['Current password is required']]
                    ], 422);
                }

                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect',
                        'errors' => ['current_password' => ['Current password is incorrect']]
                    ], 422);
                }
            }

            // Update user data
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            
            if ($request->filled('new_password')) {
                $user->password = Hash::make($request->new_password);
            }

            $user->save();

            // Update vendor data
            $vendor = $user->vendor;
            $vendor->corporate_name = $request->corporate_name;
            $vendor->tax_number = $request->tax_number;
            $vendor->device_type = $request->device_type;
            $vendor->with_components = $request->boolean('with_components', false);
            $vendor->save();

            return response()->json([
                'success' => true,
                'message' => 'Account data updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                        'updated_at' => $user->updated_at,
                    ],
                    'vendor' => [
                        'id' => $vendor->id,
                        'corporate_name' => $vendor->corporate_name,
                        'tax_number' => $vendor->tax_number,
                        'device_type' => $vendor->device_type,
                        'with_components' => $vendor->with_components,
                        'is_active' => $vendor->is_active,
                        'is_verified' => $vendor->is_verified,
                        'slug' => $vendor->slug,
                        'updated_at' => $vendor->updated_at,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update account data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

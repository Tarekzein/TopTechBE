<?php

namespace Modules\Vendor\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vendor\Interfaces\VendorServiceInterface;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VendorController extends Controller
{
    protected $vendor_service;

    public function __construct(VendorServiceInterface $vendor_service)
    {
        $this->vendor_service = $vendor_service;
    }

    public function index()
{
    try {
        // Get vendors with their related user info
        $vendors = $this->vendor_service->getAll()->load('user');

        return response()->json($vendors, 200);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Failed to fetch vendors',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function show($id)
    {
        try {
            $vendor = $this->vendor_service->getById($id);
            return response()->json($vendor, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Vendor not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
{
    try {
        $data = $request->validate([
            // بيانات اليوزر
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|string|min:6|confirmed',

            // بيانات الـ vendor
            'corporate_name'  => 'required|string|max:255',
            'tax_number'      => 'required|string',
            'device_type'     => 'required|string',
            'with_components' => 'boolean',
        ]);

        \DB::beginTransaction();

        // 1. إنشاء يوزر جديد
        $user = \App\Models\User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => bcrypt($data['password']),
        ]);

        // إضافة role vendor
        $user->assignRole('vendor');

        // 2. إنشاء Vendor مربوط باليوزر
        $vendorData = [
            'user_id'         => $user->id,
            'corporate_name'  => $data['corporate_name'],
            'tax_number'      => $data['tax_number'],
            'device_type'     => $data['device_type'],
            'with_components' => $data['with_components'] ?? false,
        ];

        $vendor = $this->vendor_service->create($vendorData);

        \DB::commit();

        return response()->json([
            'message' => 'Vendor and user created successfully',
            'user'    => $user,
            'vendor'  => $vendor,
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        \DB::rollBack();
        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \DB::rollBack();
        return response()->json([
            'message' => 'Failed to create vendor',
            'error'   => $e->getMessage()
        ], 500);
    }
}


   public function update(Request $request, $id)
{
    try {
        // نجيب الـ Vendor الأول عشان نقدر نستخدم user_id في الـ validation
        $vendor = $this->vendor_service->getById($id);

        if (!$vendor) {
            return response()->json([
                'message' => 'Vendor not found'
            ], 404);
        }

        // Validation rules
        $data = $request->validate([
            // بيانات اليوزر (اختيارية)
            'first_name'      => 'sometimes|string|max:255',
            'last_name'       => 'sometimes|string|max:255',
            'email'           => 'sometimes|email|unique:users,email,' . $vendor->user_id,
            'password'        => 'sometimes|string|min:6|confirmed',

            // بيانات الـ vendor
            'corporate_name'  => 'sometimes|string|max:255',
            'tax_number'      => 'nullable|string',
            'device_type'     => 'nullable|string',
            'with_components' => 'boolean',
        ]);

        \DB::beginTransaction();

        // تحديث بيانات اليوزر لو في حاجة اتبعت
        $user = $vendor->user;

        $userData = [];
        if ($request->has('first_name')) {
            $userData['first_name'] = $data['first_name'];
        }
        if ($request->has('last_name')) {
            $userData['last_name'] = $data['last_name'];
        }
        if ($request->has('email')) {
            $userData['email'] = $data['email'];
        }
        if ($request->has('password')) {
            $userData['password'] = bcrypt($data['password']);
        }

        if (!empty($userData)) {
            $user->update($userData);
        }

        // تحديث بيانات الـ Vendor
        $vendorData = collect($data)->only([
            'corporate_name',
            'tax_number',
            'device_type',
            'with_components'
        ])->toArray();

        if (!empty($vendorData)) {
            $vendor = $this->vendor_service->update($id, $vendorData);
        }

        \DB::commit();

        return response()->json([
            'message' => 'Vendor and user updated successfully',
            'user'    => $user,
            'vendor'  => $vendor,
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        \DB::rollBack();
        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $e->errors()
        ], 422);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        \DB::rollBack();
        return response()->json([
            'message' => 'Vendor not found'
        ], 404);
    } catch (\Exception $e) {
        \DB::rollBack();
        return response()->json([
            'message' => 'Failed to update vendor',
            'error'   => $e->getMessage()
        ], 500);
    }
}



    public function destroy($id)
    {
        try {
            $this->vendor_service->delete($id);
            return response()->json([
                'message' => 'Vendor deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Vendor not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

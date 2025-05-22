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
            $vendors = $this->vendor_service->getAll();
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
                'user_id' => 'required|exists:users,id',
                'corporate_name' => 'required|string|max:255',
                'tax_number' => 'required|string',
                'device_type' => 'required|string',
                'with_components' => 'boolean',
            ]);

            $vendor = $this->vendor_service->create($data);
            return response()->json($vendor, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'corporate_name' => 'sometimes|string|max:255',
                'tax_number' => 'nullable|string',
                'device_type' => 'nullable|string',
                'with_components' => 'boolean',
            ]);

            $vendor = $this->vendor_service->update($id, $data);
            return response()->json($vendor, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Vendor not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update vendor',
                'error' => $e->getMessage()
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

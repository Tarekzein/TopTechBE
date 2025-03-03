<?php

namespace Modules\Vendor\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vendor\Interfaces\VendorServiceInterface;

class VendorController extends Controller
{
    protected $vendor_service;

    public function __construct(VendorServiceInterface $vendor_service)
    {
        $this->vendor_service = $vendor_service;
    }

    public function index()
    {
        return response()->json($this->vendor_service->getAll(), 200);
    }

    public function show($id)
    {
        return response()->json($this->vendor_service->getById($id), 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'store_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'logo' => 'nullable|string',
            'banner' => 'nullable|string',
        ]);

        return response()->json($this->vendor_service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'store_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'banner' => 'nullable|string',
            'address' => 'nullable|string',
            'is_verified' => 'boolean',
        ]);

        return response()->json($this->vendor_service->update($id, $data), 200);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Vendor deleted successfully'], 200);
    }
}

<?php

namespace Modules\Store\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Store\Services\ProductAttributeService;

class ProductAttributeController extends Controller
{
    protected $attributeService;

    public function __construct(ProductAttributeService $attributeService)
    {
        $this->attributeService = $attributeService;
    }

    /**
     * Get all attributes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $attributes = $this->attributeService->getAllAttributes($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $attributes
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attribute by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $attribute = $this->attributeService->getAttributeById($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $attribute
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new attribute
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $attribute = $this->attributeService->createAttribute($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Attribute created successfully',
                'data' => $attribute
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update attribute
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $attribute = $this->attributeService->updateAttribute($id, $request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Attribute updated successfully',
                'data' => $attribute
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete attribute
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->attributeService->deleteAttribute($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Attribute deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get all values for an attribute
     */
    public function getValues(Request $request, int $attributeId): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $values = $this->attributeService->getAttributeValues($attributeId, $perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $values
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attribute value by ID
     */
    public function getValue(int $id): JsonResponse
    {
        try {
            $value = $this->attributeService->getAttributeValueById($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $value
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new attribute value
     */
    public function storeValue(Request $request): JsonResponse
    {
        try {
            $value = $this->attributeService->createAttributeValue($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Attribute value created successfully',
                'data' => $value
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update attribute value
     */
    public function updateValue(Request $request, int $id): JsonResponse
    {
        try {
            $value = $this->attributeService->updateAttributeValue($id, $request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Attribute value updated successfully',
                'data' => $value
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete attribute value
     */
    public function destroyValue(int $id): JsonResponse
    {
        try {
            $this->attributeService->deleteAttributeValue($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Attribute value deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }
} 
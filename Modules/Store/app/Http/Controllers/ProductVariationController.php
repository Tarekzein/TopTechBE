<?php

namespace Modules\Store\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Store\Services\ProductVariationService;

class ProductVariationController extends Controller
{
    protected $variationService;

    public function __construct(ProductVariationService $variationService)
    {
        $this->variationService = $variationService;
    }

    /**
     * Get all variations for a product
     */
    public function index(Request $request, int $productId): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $variations = $this->variationService->getProductVariations($productId, $perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $variations
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get variation by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $variation = $this->variationService->getVariationById($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $variation
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new variation
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $variation = $this->variationService->createVariation($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Variation created successfully',
                'data' => $variation
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update variation
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $variation = $this->variationService->updateVariation($id, $request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Variation updated successfully',
                'data' => $variation
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete variation
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->variationService->deleteVariation($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Variation deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Add image to variation
     */
    public function addImage(Request $request): JsonResponse
    {
        try {
            $image = $this->variationService->addVariationImage($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Image added successfully',
                'data' => $image
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove image from variation
     */
    public function removeImage(int $id): JsonResponse
    {
        try {
            $this->variationService->removeVariationImage($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Image removed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update variation image order
     */
    public function updateImageOrder(Request $request): JsonResponse
    {
        try {
            $imageIds = $request->input('image_ids');
            
            if (!is_array($imageIds)) {
                throw new Exception('Image IDs must be an array');
            }
            
            $this->variationService->updateVariationImageOrder($imageIds);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Image order updated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
} 
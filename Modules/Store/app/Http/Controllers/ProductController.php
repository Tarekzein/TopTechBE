<?php

namespace Modules\Store\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Store\Services\ProductService;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get all products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $products = $this->productService->getAllProducts($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $product
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new product
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Creating product', $request->all());
            $product = $this->productService->createProduct($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update product
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $product = $this->productService->updateProduct($id, $request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete product
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productService->deleteProduct($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get products by category
     */
    public function getByCategory(Request $request, int $categoryId): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $products = $this->productService->getProductsByCategory($categoryId, $perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by vendor
     */
    public function getByVendor(Request $request, int $vendorId): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $products = $this->productService->getProductsByVendor($vendorId, $perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product by slug
     */
    public function showBySlug(string $slug): JsonResponse
    {
        try {
            $product = $this->productService->getProductBySlug($slug);
            
            return response()->json([
                'status' => 'success',
                'data' => $product
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Search products
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('query');
            $perPage = $request->input('per_page', 10);
            
            if (empty($query)) {
                throw new Exception('Search query is required');
            }
            
            $products = $this->productService->searchProducts($query, $perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
} 
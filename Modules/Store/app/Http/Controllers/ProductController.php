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
     * Get all products with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            
            // Get filter parameters
            $filters = [
                'category_id' => $request->input('category_id'),
                'vendor_id' => $request->input('vendor_id'),
                'price_min' => $request->input('price_min'),
                'price_max' => $request->input('price_max'),
                'search' => $request->input('search'),
                'sort' => $request->input('sort'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ];

            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $products = $this->productService->getAllProducts($perPage, $filters);
            
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
            
            // Prepare data array
            $data = $request->all();
            $data['vendor_id'] = $request->vendor_id ?? $request->user()->vendor->id;
            
            // Handle images array
            if ($request->hasFile('images')) {
                $data['images'] = $request->file('images');
            }
            
            // Handle variation images
            $variationImages = [];
            foreach ($request->all() as $key => $value) {
                if (preg_match('/^variation_images\[(\d+)\]$/', $key, $matches)) {
                    $index = $matches[1];
                    $variationImages[$index] = $value;
                }
            }
            $data['variation_images'] = $variationImages;
            
            // Parse JSON attributes if present
            if ($request->has('attributes') && is_string($request->input('attributes'))) {
                $data['attributes'] = json_decode($request->input('attributes'), true);
            }
            
            // Parse JSON variations if present
            if ($request->has('variations') && is_string($request->input('variations'))) {
                $data['variations'] = json_decode($request->input('variations'), true);
            }
            
            // Set default values for boolean fields and ensure they are boolean
            $data['manage_stock'] = filter_var($request->input('manage_stock', true), FILTER_VALIDATE_BOOLEAN);
            $data['allow_backorders'] = filter_var($request->input('allow_backorders', false), FILTER_VALIDATE_BOOLEAN);
            $data['sold_individually'] = filter_var($request->input('sold_individually', false), FILTER_VALIDATE_BOOLEAN);
            $data['is_active'] = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN);
            
            // Set regular_price to price if not provided
            if (!isset($data['regular_price']) || empty($data['regular_price'])) {
                $data['regular_price'] = $data['price'];
            }
            
            $product = $this->productService->createProduct($data);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating product', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
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
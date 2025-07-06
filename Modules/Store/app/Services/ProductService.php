<?php

namespace Modules\Store\Services;

use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Store\Repositories\ProductRepository;
use Modules\Store\Models\Product;
use Modules\Store\Models\ProductVariation;
use Modules\Store\Models\ProductVariationImage;
use Modules\Common\Services\CloudImageService;
use Modules\Store\Services\ProductVariationService;

class ProductService
{
    protected $productRepository;
    protected $cloudImageService;
    protected $variationService;

    public function __construct(ProductRepository $productRepository, CloudImageService $cloudImageService, ProductVariationService $variationService)
    {
        $this->productRepository = $productRepository;
        $this->cloudImageService = $cloudImageService;
        $this->variationService = $variationService;
    }

    /**
     * Validate product data
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'regular_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start' => 'nullable|date',
            'sale_end' => 'nullable|date|after_or_equal:sale_start',
            'stock' => 'required|integer|min:0',
            'manage_stock' => 'boolean',
            'stock_status' => 'in:instock,outofstock,onbackorder',
            'allow_backorders' => 'boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'sold_individually' => 'boolean',
            'sku' => 'required|string|max:50|unique:products,sku' . ($id ? ",$id" : ''),
            'serial_number' => 'nullable|string|max:50',
            'product_type' => 'required|in:simple,variable',
            'images' => 'nullable|array',
            'images.*' => 'file|mimes:jpeg,png,jpg|max:2048',
            'category_id' => 'required|exists:categories,id',
            'vendor_id' => 'required|exists:vendors,id',
            'is_active' => 'boolean',
            'attributes' => 'nullable|array',
            'variations' => 'nullable|array',
            'search' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Validate variation data
     */
    protected function validateVariation(array $data): array
    {
        $rules = [
            'sku' => 'required|string|max:50',
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start' => 'nullable|date',
            'sale_end' => 'nullable|date|after_or_equal:sale_start',
            'stock' => 'nullable|integer|min:0',
            'manage_stock' => 'boolean',
            'stock_status' => 'in:instock,outofstock,onbackorder',
            'allow_backorders' => 'boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
            'type' => 'nullable|in:manual,automatic',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Get all products with filters
     */
    public function getAllProducts(int $perPage = 10, array $filters = [])
    {
        return $this->productRepository->getAll($perPage, $filters);
    }

    /**
     * Get product by ID
     */
    public function getProductById(int $id)
    {
        return $this->productRepository->findById($id);
    }

    /**
     * Create new product with variations support
     */
    public function createProduct(array $data)
    {
        try {
            DB::beginTransaction();

            // Validate product data
            $validatedData = $this->validate($data);

            // Handle main product images
            $images = [];
            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $image) {
                    if ($image instanceof \Illuminate\Http\UploadedFile) {
                        $uploadedImage = $this->cloudImageService->upload($image);
                        if (isset($uploadedImage['secure_url'])) {
                            $images[] = $uploadedImage['secure_url'];
                        }
                    }
                }
            }
            $validatedData['images'] = $images;

            // For variable products, ensure prices and stock are 0
            if ($validatedData['product_type'] === 'variable') {
                $validatedData['price'] = 0;
                $validatedData['regular_price'] = 0;
                $validatedData['sale_price'] = 0;
                $validatedData['stock'] = 0;
            } else {
                // Set regular_price to price if not provided for simple products
                if (!isset($validatedData['regular_price'])) {
                    $validatedData['regular_price'] = $validatedData['price'];
                }
            }

            // Create the product
            $product = Product::create($validatedData);

            // Handle variations for variable products
            if ($product->product_type === 'variable' && isset($data['variations']) && is_array($data['variations'])) {
                $this->createProductVariations($product, $data['variations'], $data['variation_images'] ?? []);
            }

            DB::commit();

            // Return product with variations loaded
            return $product->load(['variations' => function($query) {
                $query->with(['images']);
            }]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating product: ' . $e->getMessage());
            throw new Exception('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Create product variations
     */
    protected function createProductVariations(Product $product, array $variations, array $variationImages = [])
    {
        foreach ($variations as $index => $variationData) {
            // Prepare variation data for the service
            $variationPayload = [
                'product_id' => $product->id,
                'sku' => $variationData['sku'],
                'regular_price' => $variationData['regular_price'],
                'sale_price' => $variationData['sale_price'] ?? null,
                'sale_start' => $variationData['sale_start'] ?? null,
                'sale_end' => $variationData['sale_end'] ?? null,
                'manage_stock' => $variationData['manage_stock'] ?? true,
                'stock' => $variationData['stock'],
                'stock_status' => $variationData['stock_status'] ?? 'instock',
                'allow_backorders' => $variationData['allow_backorders'] ?? false,
                'low_stock_threshold' => $variationData['low_stock_threshold'] ?? null,
                'attributes' => $this->formatVariationAttributes($variationData['attributes'] ?? []),
                'is_active' => true,
                'type' => $variationData['type'] ?? null,
            ];

            // Attach image if present
            if (isset($variationImages[$index]) && $variationImages[$index] instanceof \Illuminate\Http\UploadedFile) {
                $variationPayload['images'] = [$variationImages[$index]];
            }

            $this->variationService->createVariation($variationPayload);
        }
    }

    /**
     * Format variation attributes to save as attribute_id => value_id
     */
    protected function formatVariationAttributes(array $attributes): array
    {
        if (empty($attributes)) {
            return ['attributes' => []];
        }

        $formattedAttributes = [];
        
        foreach ($attributes as $attribute) {
            if (isset($attribute['attribute_id']) && isset($attribute['value_id'])) {
                // Direct attribute_id => value_id mapping
                $formattedAttributes[$attribute['attribute_id']] = $attribute['value_id'];
            } elseif (isset($attribute['attribute_slug']) && isset($attribute['value_slug'])) {
                // Convert slug to ID mapping (for automatic variations)
                $attributeModel = \Modules\Store\Models\ProductAttribute::where('slug', $attribute['attribute_slug'])->first();
                $valueModel = $attributeModel ? $attributeModel->values()->where('slug', $attribute['value_slug'])->first() : null;
                
                if ($attributeModel && $valueModel) {
                    $formattedAttributes[$attributeModel->id] = $valueModel->id;
                }
            }
        }

        return ['attributes' => $formattedAttributes];
    }

    /**
     * Update product
     */
    public function updateProduct(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $validatedData = $this->validate($data, $id);
            $product = Product::findOrFail($id);

            // Handle main product images
            if (isset($data['images']) && is_array($data['images'])) {
                $images = [];
                foreach ($data['images'] as $image) {
                    if ($image instanceof \Illuminate\Http\UploadedFile) {
                        $uploadedImage = $this->cloudImageService->upload($image);
                        if (isset($uploadedImage['secure_url'])) {
                            $images[] = $uploadedImage['secure_url'];
                        }
                    }
                }
                $validatedData['images'] = $images;
            }

            $product->update($validatedData);

            // Handle variations for variable products
            if ($product->product_type === 'variable' && isset($data['variations'])) {
                // Delete existing variations
                $product->variations()->delete();
                
                // Create new variations
                if (is_array($data['variations'])) {
                    $this->createProductVariations($product, $data['variations'], $data['variation_images'] ?? []);
                }
            }

            DB::commit();

            return $product->load(['variations' => function($query) {
                $query->with(['images']);
            }]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating product: ' . $e->getMessage());
            throw new Exception('Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Delete product
     */
    public function deleteProduct(int $id)
    {
        try {
            DB::beginTransaction();
            
            $product = Product::findOrFail($id);
            
            // Delete all variations and their images first
            if ($product->variations) {
                foreach ($product->variations as $variation) {
                    // Delete variation images
                    $variation->images()->delete();
                    // Delete variation
                    $variation->delete();
                }
            }
            
            // Delete the product
            $product->delete();
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting product: ' . $e->getMessage());
            throw new Exception('Failed to delete product');
        }
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory(int $categoryId, int $perPage = 10)
    {
        return $this->productRepository->getByCategory($categoryId, $perPage);
    }

    /**
     * Get products by vendor
     */
    public function getProductsByVendor(int $vendorId, int $perPage = 10)
    {
        return $this->productRepository->getByVendor($vendorId, $perPage);
    }

    /**
     * Get product by slug
     */
    public function getProductBySlug(string $slug)
    {
        return $this->productRepository->findBySlug($slug);
    }

    /**
     * Search products
     */
    public function searchProducts(string $query, int $perPage = 10)
    {
        return $this->productRepository->search($query, $perPage);
    }
} 
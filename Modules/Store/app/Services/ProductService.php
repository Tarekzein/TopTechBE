<?php

namespace Modules\Store\Services;

use Exception;
use Illuminate\Support\Facades\Validator;
use Modules\Store\Repositories\ProductRepository;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
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
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|max:50|unique:products,sku' . ($id ? ",$id" : ''),
            'images' => 'nullable|array',
            'images.*' => 'string|max:255',
            'is_active' => 'boolean',
            'category_id' => 'required|exists:categories,id',
            'vendor_id' => 'required|exists:vendors,id'
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
     * Create new product
     */
    public function createProduct(array $data)
    {
        $validatedData = $this->validate($data);
        return $this->productRepository->create($validatedData);
    }

    /**
     * Update product
     */
    public function updateProduct(int $id, array $data)
    {
        $validatedData = $this->validate($data, $id);
        return $this->productRepository->update($id, $validatedData);
    }

    /**
     * Delete product
     */
    public function deleteProduct(int $id)
    {
        return $this->productRepository->delete($id);
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
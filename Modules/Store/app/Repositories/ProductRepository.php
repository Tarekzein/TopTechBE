<?php

namespace Modules\Store\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Store\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    /**
     * Get all products with pagination
     */
    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        try {
            return Product::with(['category', 'vendor'])
                ->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            throw new Exception('Failed to fetch products');
        }
    }

    /**
     * Get product by ID
     */
    public function findById(int $id): ?Product
    {
        try {
            return Product::with(['category', 'vendor'])
                ->findOrFail($id);
        } catch (Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            throw new Exception('Product not found');
        }
    }

    /**
     * Create new product
     */
    public function create(array $data): Product
    {
        try {
            DB::beginTransaction();
            
            $product = Product::create($data);
            
            DB::commit();
            return $product;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating product: ' . $e->getMessage());
            throw new Exception('Failed to create product');
        }
    }

    /**
     * Update product
     */
    public function update(int $id, array $data): Product
    {
        try {
            DB::beginTransaction();
            
            $product = Product::findOrFail($id);
            $product->update($data);
            
            DB::commit();
            return $product;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating product: ' . $e->getMessage());
            throw new Exception('Failed to update product');
        }
    }

    /**
     * Delete product
     */
    public function delete(int $id): bool
    {
        try {
            DB::beginTransaction();
            
            $product = Product::findOrFail($id);
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
    public function getByCategory(int $categoryId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return Product::where('category_id', $categoryId)
                ->with(['category', 'vendor'])
                ->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Error fetching products by category: ' . $e->getMessage());
            throw new Exception('Failed to fetch products by category');
        }
    }

    /**
     * Get products by vendor
     */
    public function getByVendor(int $vendorId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return Product::where('vendor_id', $vendorId)
                ->with(['category', 'vendor'])
                ->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Error fetching products by vendor: ' . $e->getMessage());
            throw new Exception('Failed to fetch products by vendor');
        }
    }

    /**
     * Get product by slug
     */
    public function findBySlug(string $slug): ?Product
    {
        try {
            return Product::where('slug', $slug)
                ->with(['category', 'vendor'])
                ->firstOrFail();
        } catch (Exception $e) {
            Log::error('Error fetching product by slug: ' . $e->getMessage());
            throw new Exception('Product not found');
        }
    }

    /**
     * Search products
     */
    public function search(string $query, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return Product::where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->with(['category', 'vendor'])
                ->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Error searching products: ' . $e->getMessage());
            throw new Exception('Failed to search products');
        }
    }
} 
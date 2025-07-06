<?php

namespace Modules\Store\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Store\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Common\Services\CloudImageService;
class ProductRepository
{
    protected $cloudImageService;

    public function __construct(CloudImageService $cloudImageService)
    {
        $this->cloudImageService = $cloudImageService;
    }
    /**
     * Get all products with pagination and filters
     */
    public function getAll(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = Product::with([
                'category', 
                'vendor',
                'variations' => function($query) {
                    $query->with(['images']);
                }
            ]);

            // Apply search filter (search by name or SKU)
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply category filter
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            // Apply vendor filter
            if (!empty($filters['vendor_id'])) {
                $query->where('vendor_id', $filters['vendor_id']);
            }

            // Apply date range filter
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            // Apply price range filter
            if (!empty($filters['price_min'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('price', '>=', $filters['price_min'])
                      ->orWhereHas('variations', function($q) use ($filters) {
                          $q->where('regular_price', '>=', $filters['price_min']);
                      });
                });
            }
            if (!empty($filters['price_max'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('price', '<=', $filters['price_max'])
                      ->orWhereHas('variations', function($q) use ($filters) {
                          $q->where('regular_price', '<=', $filters['price_max']);
                      });
                });
            }

            // Apply sort order
            if (!empty($filters['sort'])) {
                $sortParts = explode('.', $filters['sort']);
                $field = $sortParts[0];
                $direction = isset($sortParts[1]) ? $sortParts[1] : 'asc';
                
                // Map frontend sort fields to database fields
                $sortMap = [
                    'created_at' => 'created_at',
                    'name' => 'name',
                    'price' => 'price',
                    'newest' => 'created_at',
                    'oldest' => 'created_at'
                ];
                
                if (isset($sortMap[$field])) {
                    $dbField = $sortMap[$field];
                    $dbDirection = in_array($direction, ['desc', 'newest']) ? 'desc' : 'asc';
                    $query->orderBy($dbField, $dbDirection);
                }
            } else {
                // Default sort by created_at desc
                $query->orderBy('created_at', 'desc');
            }

            $products = $query->latest()->paginate($perPage);
            
            // Format variations with attributes
            foreach($products as $product) {
                if ($product->variations) {
                    foreach($product->variations as $variation) {
                        $variation->attributes = $variation->getFormattedAttributesAttribute();
                    }
                }
            }

            return $products;
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
            $product = Product::with([
                'category', 
                'vendor',
                'variations' => function($query) {
                    $query->with(['images']);
                }
            ])->findOrFail($id);
            if ($product->variations) {
                foreach($product->variations as $variation) {
                    $variation->attributes=$variation->getFormattedAttributesAttribute();
                }
            }
            return $product;
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
            $images = [];
            if(isset($data['images'])){
                foreach($data['images'] as $image){
                    $image = $this->cloudImageService->upload($image);
                    if(isset($image['secure_url'])){
                        $images[] = $image['secure_url'];
                    }
                }
            }
            $data['images'] = $images;
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
            $products = Product::where('category_id', $categoryId)
                ->with([
                    'category', 
                    'vendor',
                    'variations' => function($query) {
                        $query->with(['images']);
                    }
                ])
                ->paginate($perPage);
            foreach($products as $product) {
                if ($product->variations) {
                    foreach($product->variations as $variation) {
                        $variation->attributes=$variation->getFormattedAttributesAttribute();
                    }
                }
            }
            return $products;
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
            $products = Product::where('vendor_id', $vendorId)
                ->with([
                    'category', 
                    'vendor',
                    'variations' => function($query) {
                        $query->with(['images']);
                    }
                ])
                ->paginate($perPage);
            foreach($products as $product) {
                if ($product->variations) {
                    foreach($product->variations as $variation) {
                        $variation->attributes=$variation->getFormattedAttributesAttribute();
                    }
                }
            }
            return $products;
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
            $product = Product::where('slug', $slug)
                ->with([
                    'category', 
                    'vendor',
                    'variations' => function($query) {
                        $query->with(['images']);
                    }
                ])
                ->firstOrFail();
            if ($product->variations) {
                foreach($product->variations as $variation) {
                    $variation->attributes=$variation->getFormattedAttributesAttribute();
                }
            }
            return $product;
        } catch (Exception $e) {
            Log::info($e);
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
            $products = Product::where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->with([
                    'category', 
                    'vendor',
                    'variations' => function($query) {
                        $query->with(['images']);
                    }
                ])
                ->paginate($perPage);
            foreach($products as $product) {
                if ($product->variations) {
                    foreach($product->variations as $variation) {
                        $variation->attributes=$variation->getFormattedAttributesAttribute();
                    }
                }
            }
            return $products;
        } catch (Exception $e) {
            Log::error('Error searching products: ' . $e->getMessage());
            throw new Exception('Failed to search products');
        }
    }
} 
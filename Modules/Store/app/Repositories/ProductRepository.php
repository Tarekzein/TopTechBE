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

            // Apply category filter
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            // Apply vendor filter
            if (!empty($filters['vendor_id'])) {
                $query->where('vendor_id', $filters['vendor_id']);
            }

            // Apply price range filter
            if (!empty($filters['price_min'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('price', '>=', $filters['price_min'])
                      ->orWhereHas('variations', function($q) use ($filters) {
                          $q->where('price', '>=', $filters['price_min']);
                      });
                });
            }
            if (!empty($filters['price_max'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('price', '<=', $filters['price_max'])
                      ->orWhereHas('variations', function($q) use ($filters) {
                          $q->where('price', '<=', $filters['price_max']);
                      });
                });
            }

            // Apply attribute filters (color, size)
            if (!empty($filters['color']) || !empty($filters['size'])) {
                $query->whereHas('variations', function($q) use ($filters) {
                    if (!empty($filters['color'])) {
                        $q->whereHas('attributes', function($q) use ($filters) {
                            $q->where('type', 'color')
                              ->where('value', $filters['color']);
                        });
                    }
                    if (!empty($filters['size'])) {
                        $q->whereHas('attributes', function($q) use ($filters) {
                            $q->where('type', 'size')
                              ->where('value', $filters['size']);
                        });
                    }
                });
            }

            $products = $query->paginate($perPage);
            
            // Format variations with attributes
            $products->each(function($product) {
                $product->variations->each(function($variation) {
                    $variation->attributes = $variation->getFormattedAttributesAttribute();
                });
            });

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
            $product->variations->each(function($variation) {
                $variation->attributes=$variation->getFormattedAttributesAttribute();
            });
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
            $products->each(function($product) {
                $product->variations->each(function($variation) {
                    $variation->attributes=$variation->getFormattedAttributesAttribute();
                });
            });
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
            $products->each(function($product) {
                $product->variations->each(function($variation) {
                    $variation->attributes=$variation->getFormattedAttributesAttribute();
                });
            });
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
            $product->variations->each(function($variation) {
                $variation->attributes=$variation->getFormattedAttributesAttribute();
            });
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
            $products->each(function($product) {
                $product->variations->each(function($variation) {
                    $variation->attributes=$variation->getFormattedAttributesAttribute();
                });
            });
            return $products;
        } catch (Exception $e) {
            Log::error('Error searching products: ' . $e->getMessage());
            throw new Exception('Failed to search products');
        }
    }
} 
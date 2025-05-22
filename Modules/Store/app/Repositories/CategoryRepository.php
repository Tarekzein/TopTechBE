<?php

namespace Modules\Store\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Store\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    /**
     * Get all categories with pagination
     */
    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        try {
            return Category::with(['parent', 'children'])
                ->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            throw new Exception('Failed to fetch categories');
        }
    }

    /**
     * Get category by ID
     */
    public function findById(int $id): ?Category
    {
        try {
            return Category::with(['parent', 'children', 'products'])
                ->findOrFail($id);
        } catch (Exception $e) {
            Log::error('Error fetching category: ' . $e->getMessage());
            throw new Exception('Category not found');
        }
    }

    /**
     * Create new category
     */
    public function create(array $data): Category
    {
        try {
            DB::beginTransaction();
            
            $category = Category::create($data);
            
            DB::commit();
            return $category;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating category: ' . $e->getMessage());
            throw new Exception('Failed to create category');
        }
    }

    /**
     * Update category
     */
    public function update(int $id, array $data): Category
    {
        try {
            DB::beginTransaction();
            
            $category = Category::findOrFail($id);
            $category->update($data);
            
            DB::commit();
            return $category;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating category: ' . $e->getMessage());
            throw new Exception('Failed to update category');
        }
    }

    /**
     * Delete category
     */
    public function delete(int $id): bool
    {
        try {
            DB::beginTransaction();
            
            $category = Category::findOrFail($id);
            $category->delete();
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting category: ' . $e->getMessage());
            throw new Exception('Failed to delete category');
        }
    }

    /**
     * Get root categories (categories without parent)
     */
    public function getRootCategories(): Collection
    {
        try {
            return Category::whereNull('parent_id')
                ->with('children')
                ->get();
        } catch (Exception $e) {
            Log::error('Error fetching root categories: ' . $e->getMessage());
            throw new Exception('Failed to fetch root categories');
        }
    }

    /**
     * Get category by slug
     */
    public function findBySlug(string $slug): ?Category
    {
        try {
            return Category::where('slug', $slug)
                ->with(['parent', 'children', 'products'])
                ->firstOrFail();
        } catch (Exception $e) {
            Log::error('Error fetching category by slug: ' . $e->getMessage());
            throw new Exception('Category not found');
        }
    }
} 
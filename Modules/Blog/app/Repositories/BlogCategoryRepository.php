<?php

namespace Modules\Blog\App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Modules\Blog\App\Models\BlogCategory;
use Modules\Blog\App\Repositories\Interfaces\BlogCategoryRepositoryInterface;

class BlogCategoryRepository extends BaseRepository implements BlogCategoryRepositoryInterface
{
    public function __construct(BlogCategory $model)
    {
        parent::__construct($model);
    }

    public function getActiveCategories(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    public function getCategoryTree(): Collection
    {
        return $this->query()
            ->with(['children' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('order');
            }])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    public function getCategoryWithChildren(int $categoryId): ?Model
    {
        return $this->query()
            ->with(['children' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('order');
            }])
            ->where('id', $categoryId)
            ->first();
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->query()
            ->where('slug', $slug)
            ->first();
    }

    public function getCategoriesWithPostCount(): Collection
    {
        return $this->query()
            ->withCount(['posts' => function ($query) {
                $query->where('status', 'published')
                    ->where('published_at', '<=', now());
            }])
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    public function getParentCategories(): Collection
    {
        return $this->query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    public function getChildCategories(int $parentId): Collection
    {
        return $this->query()
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }
} 
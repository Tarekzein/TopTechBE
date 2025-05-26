<?php

namespace Modules\Blog\App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

interface BlogCategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveCategories(): Collection;
    public function getCategoryTree(): Collection;
    public function getCategoryWithChildren(int $categoryId): ?Model;
    public function findBySlug(string $slug): ?Model;
    public function getCategoriesWithPostCount(): Collection;
    public function getParentCategories(): Collection;
    public function getChildCategories(int $parentId): Collection;
} 
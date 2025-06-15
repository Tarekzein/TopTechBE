<?php

namespace Modules\Blog\App\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

interface CategoryServiceInterface extends BaseServiceInterface
{
    public function getActiveCategories(): Collection;
    public function getCategoryTree(): Collection;
    public function getCategoryWithChildren(int $categoryId): ?Model;
    public function findBySlug(string $slug): ?Model;
    public function getCategoriesWithPostCount(): Collection;
    public function getParentCategories(): Collection;
    public function getChildCategories(int $parentId): Collection;
    public function toggleActive(int $id): bool;
    public function updateOrder(array $orderData): bool;
    public function moveCategory(int $id, ?int $newParentId): bool;
}

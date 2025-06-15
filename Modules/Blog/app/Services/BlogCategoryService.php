<?php

namespace Modules\Blog\App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Blog\App\Repositories\Interfaces\BlogCategoryRepositoryInterface;
use Modules\Blog\App\Services\Interfaces\CategoryServiceInterface;

class BlogCategoryService implements CategoryServiceInterface
{
    protected BlogCategoryRepositoryInterface $categoryRepository;

    public function __construct(BlogCategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getActiveCategories(): Collection
    {
        return $this->categoryRepository->getActiveCategories();
    }

    public function create(array $data): Model
    {
        return $this->categoryRepository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->categoryRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->categoryRepository->delete($id);
    }

    public function getCategoryTree(): Collection
    {
        return $this->categoryRepository->getCategoryTree();
    }

    public function getCategoryWithChildren(int $categoryId): ?Model
    {
        return $this->categoryRepository->getCategoryWithChildren($categoryId);
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->categoryRepository->findBySlug($slug);
    }

    public function getCategoriesWithPostCount(): Collection
    {
        return $this->categoryRepository->getCategoriesWithPostCount();
    }

    public function getParentCategories(): Collection
    {
        return $this->categoryRepository->getParentCategories();
    }

    public function getChildCategories(int $parentId): Collection
    {
        return $this->categoryRepository->getChildCategories($parentId);
    }

    public function toggleActive(int $id): bool
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return false;
        }
        return $this->categoryRepository->update($id, [
            'is_active' => !$category->is_active
        ]);
    }

    public function updateOrder(array $orderData): bool
    {
        $success = true;
        foreach ($orderData as $item) {
            $updated = $this->categoryRepository->update($item['id'], [
                'order' => $item['order']
            ]);
            if (!$updated) {
                $success = false;
            }
        }
        return $success;
    }

    public function moveCategory(int $id, ?int $newParentId): bool
    {
        return $this->categoryRepository->update($id, [
            'parent_id' => $newParentId
        ]);
    }

    public function getAll(): Collection
    {
        return $this->categoryRepository->getAll();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($perPage);
    }

    public function find(int $id): ?Model
    {
        return $this->categoryRepository->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->categoryRepository->findOrFail($id);
    }

    public function restore(int $id): bool
    {
        return $this->categoryRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->categoryRepository->forceDelete($id);
    }
}

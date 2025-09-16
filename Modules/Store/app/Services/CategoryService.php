<?php

namespace Modules\Store\Services;

use Exception;
use Illuminate\Support\Facades\Validator;
use Modules\Store\Repositories\CategoryRepository;
use Modules\Common\Services\CloudImageService;

class CategoryService
{
    protected $categoryRepository;
    protected $cloudImageService;

    public function __construct(CategoryRepository $categoryRepository, CloudImageService $cloudImageService)
    {
        $this->categoryRepository = $categoryRepository;
        $this->cloudImageService = $cloudImageService;
    }

    /**
     * Validate category data
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable', // هنرفعها مش هنخليها string
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Get all categories
     */
    public function getAllCategories(int $perPage = 10)
    {
        return $this->categoryRepository->getAll($perPage);
    }

    /**
     * Get category by ID
     */
    public function getCategoryById(int $id)
    {
        return $this->categoryRepository->findById($id);
    }

    /**
     * Create new category
     */
    public function createCategory(array $data)
    {
        $validatedData = $this->validate($data);

        // ✅ رفع الصورة لو موجودة
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $uploadResult = $this->cloudImageService->upload($data['image']->getRealPath(), [
                'folder' => 'categories'
            ]);
            $validatedData['image'] = $uploadResult['secure_url'] ?? null;
        }

        return $this->categoryRepository->create($validatedData);
    }

    /**
     * Update category
     */
    public function updateCategory(int $id, array $data)
    {
        $validatedData = $this->validate($data, $id);

        // ✅ لو فيه صورة جديدة ارفعها
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $uploadResult = $this->cloudImageService->upload($data['image']->getRealPath(), [
                'folder' => 'categories'
            ]);
            $validatedData['image'] = $uploadResult['secure_url'] ?? null;
        }

        return $this->categoryRepository->update($id, $validatedData);
    }

    /**
     * Delete category
     */
    public function deleteCategory(int $id)
    {
        return $this->categoryRepository->delete($id);
    }

    /**
     * Get root categories
     */
    public function getRootCategories()
    {
        return $this->categoryRepository->getRootCategories();
    }

    /**
     * Get category by slug
     */
    public function getCategoryBySlug(string $slug)
    {
        return $this->categoryRepository->findBySlug($slug);
    }
}

<?php

namespace Modules\Blog\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Blog\App\Services\Interfaces\CategoryServiceInterface;
use Modules\Blog\App\Http\Requests\BlogCategoryRequest;

class BlogCategoryController extends Controller
{
    protected CategoryServiceInterface $categoryService;

    public function __construct(CategoryServiceInterface $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getActiveCategories();
        return response()->json($categories);
    }

    public function store(BlogCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $category = $this->categoryService->create($data);
        return response()->json($category, 201);
    }

    public function show(string $slug): JsonResponse
    {
        $category = $this->categoryService->findBySlug($slug);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json($category);
    }

    public function update(BlogCategoryRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $updated = $this->categoryService->update($id, $data);
        if (!$updated) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json(['message' => 'Category updated successfully']);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->categoryService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json(['message' => 'Category deleted successfully']);
    }

    public function getTree(): JsonResponse
    {
        $categories = $this->categoryService->getCategoryTree();
        return response()->json($categories);
    }

    public function getWithChildren(int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryWithChildren($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json($category);
    }

    public function getWithPostCount(): JsonResponse
    {
        $categories = $this->categoryService->getCategoriesWithPostCount();
        return response()->json($categories);
    }

    public function getParents(): JsonResponse
    {
        $categories = $this->categoryService->getParentCategories();
        return response()->json($categories);
    }

    public function getChildren(int $parentId): JsonResponse
    {
        $categories = $this->categoryService->getChildCategories($parentId);
        return response()->json($categories);
    }

    public function toggleActive(int $id): JsonResponse
    {
        $toggled = $this->categoryService->toggleActive($id);
        if (!$toggled) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json(['message' => 'Category active status toggled successfully']);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $orderData = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:blog_categories,id',
            'categories.*.order' => 'required|integer|min:0'
        ]);

        $updated = $this->categoryService->updateOrder($orderData['categories']);
        return response()->json(['message' => 'Category order updated successfully']);
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'parent_id' => 'nullable|exists:blog_categories,id'
        ]);

        $moved = $this->categoryService->moveCategory($id, $data['parent_id'] ?? null);
        if (!$moved) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json(['message' => 'Category moved successfully']);
    }
}

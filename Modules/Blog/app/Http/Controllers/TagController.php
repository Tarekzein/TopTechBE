<?php

namespace Modules\Blog\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Blog\App\Services\Interfaces\TagServiceInterface;
use Modules\Blog\App\Http\Requests\TagRequest;

class TagController extends Controller
{
    protected TagServiceInterface $tagService;

    public function __construct(TagServiceInterface $tagService)
    {
        $this->tagService = $tagService;
    }

    public function index(Request $request): JsonResponse
    {
        $tags = $this->tagService->getAllTags();
        return response()->json($tags);
    }

    public function store(TagRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tag = $this->tagService->create($data);
        return response()->json($tag, 201);
    }

    public function show(string $slug): JsonResponse
    {
        $tag = $this->tagService->findBySlug($slug);
        if (!$tag) {
            return response()->json(['message' => 'Tag not found'], 404);
        }
        return response()->json($tag);
    }

    public function update(TagRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $updated = $this->tagService->update($id, $data);
        if (!$updated) {
            return response()->json(['message' => 'Tag not found'], 404);
        }
        return response()->json(['message' => 'Tag updated successfully']);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->tagService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Tag not found'], 404);
        }
        return response()->json(['message' => 'Tag deleted successfully']);
    }

    public function getPopular(int $limit = 10): JsonResponse
    {
        $tags = $this->tagService->getPopularTags($limit);
        return response()->json($tags);
    }

    public function getWithPostCount(): JsonResponse
    {
        $tags = $this->tagService->getTagsWithPostCount();
        return response()->json($tags);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->validate(['query' => 'required|string|min:2'])['query'];
        $tags = $this->tagService->searchTags($query);
        return response()->json($tags);
    }

    public function getPostsByTag(string $slug, Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $posts = $this->tagService->getPostsByTag($slug, $perPage);
        return response()->json($posts);
    }

    public function toggleActive(int $id): JsonResponse
    {
        $toggled = $this->tagService->toggleActive($id);
        if (!$toggled) {
            return response()->json(['message' => 'Tag not found'], 404);
        }
        return response()->json(['message' => 'Tag active status toggled successfully']);
    }
} 
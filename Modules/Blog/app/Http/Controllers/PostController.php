<?php

namespace Modules\Blog\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Blog\App\Services\Interfaces\PostServiceInterface;
use Modules\Blog\App\Http\Requests\PostRequest;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    protected PostServiceInterface $postService;

    public function __construct(PostServiceInterface $postService)
    {
        $this->postService = $postService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $posts = $this->postService->getPublishedPosts($perPage);
        return response()->json($posts);
    }

    public function store(PostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['author_id'] = Auth::id();
        $post = $this->postService->create($data);
        return response()->json($post, 201);
    }

    public function show(string $slug): JsonResponse
    {
        $post = $this->postService->findBySlug($slug);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        $this->postService->incrementViewCount($post->id);
        return response()->json($post);
    }

    public function update(PostRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $updated = $this->postService->update($id, $data);
        if (!$updated) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        return response()->json(['message' => 'Post updated successfully']);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->postService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        return response()->json(['message' => 'Post deleted successfully']);
    }

    public function publish(int $id): JsonResponse
    {
        $published = $this->postService->publishPost($id);
        if (!$published) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        return response()->json(['message' => 'Post published successfully']);
    }

    public function unpublish(int $id): JsonResponse
    {
        $unpublished = $this->postService->unpublishPost($id);
        if (!$unpublished) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        return response()->json(['message' => 'Post unpublished successfully']);
    }

    public function archive(int $id): JsonResponse
    {
        $archived = $this->postService->archivePost($id);
        if (!$archived) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        return response()->json(['message' => 'Post archived successfully']);
    }

    public function toggleFeatured(int $id): JsonResponse
    {
        $toggled = $this->postService->toggleFeatured($id);
        if (!$toggled) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        return response()->json(['message' => 'Post featured status toggled successfully']);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('query');
        $perPage = $request->get('per_page', 15);
        $posts = $this->postService->searchPosts($query, $perPage);
        return response()->json($posts);
    }

    public function getRelated(int $id): JsonResponse
    {
        $posts = $this->postService->getRelatedPosts($id);
        return response()->json($posts);
    }

    public function getPopular(): JsonResponse
    {
        $posts = $this->postService->getPopularPosts();
        return response()->json($posts);
    }

    public function getRecent(): JsonResponse
    {
        $posts = $this->postService->getRecentPosts();
        return response()->json($posts);
    }

    public function getFeatured(): JsonResponse
    {
        $posts = $this->postService->getFeaturedPosts();
        return response()->json($posts);
    }
} 
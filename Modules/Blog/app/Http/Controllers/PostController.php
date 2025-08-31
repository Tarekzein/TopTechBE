<?php

namespace Modules\Blog\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Blog\App\Services\Interfaces\PostServiceInterface;
use Modules\Blog\App\Http\Requests\PostRequest;
use Modules\Common\Services\CloudImageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Cloudinary\Api\Exception\ApiError;

class PostController extends Controller
{
    protected PostServiceInterface $postService;
    protected CloudImageService $cloudImageService;

    public function __construct(PostServiceInterface $postService, CloudImageService $cloudImageService)
    {
        $this->postService = $postService;
        $this->cloudImageService = $cloudImageService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $posts = $this->postService->getPublishedPosts($perPage);
        return response()->json($posts);
    }

    public function store(PostRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['author_id'] = Auth::id();
            
            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                $featuredImageUrl = $this->uploadFeaturedImage($request->file('featured_image'));
                $data['featured_image'] = $featuredImageUrl;
            }
            
            $post = $this->postService->create($data);
            return response()->json($post, 201);
        } catch (ApiError $e) {
            Log::error('Failed to upload featured image', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to upload featured image'], 500);
        } catch (\Exception $e) {
            Log::error('Failed to create post', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create post'], 500);
        }
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
        try {
            $data = $request->validated();
            
            // Handle featured image upload if a new image is provided
            if ($request->hasFile('featured_image')) {
                $featuredImageUrl = $this->uploadFeaturedImage($request->file('featured_image'));
                $data['featured_image'] = $featuredImageUrl;
            }
            
            $updated = $this->postService->update($id, $data);
            if (!$updated) {
                return response()->json(['message' => 'Post not found'], 404);
            }
            return response()->json(['message' => 'Post updated successfully']);
        } catch (ApiError $e) {
            Log::error('Failed to upload featured image', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to upload featured image'], 500);
        } catch (\Exception $e) {
            Log::error('Failed to update post', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update post'], 500);
        }
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

    /**
     * Upload featured image to Cloudinary
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string The Cloudinary URL of the uploaded image
     * @throws ApiError
     */
    private function uploadFeaturedImage($file): string
    {
        $options = [
            'folder' => 'blog/featured-images',
            'public_id' => 'featured_' . time() . '_' . uniqid(),
            'overwrite' => true,
            'transformation' => [
                'width' => 1200,
                'height' => 630,
                'crop' => 'fill',
                'quality' => 'auto'
            ]
        ];

        $result = $this->cloudImageService->upload($file->getRealPath(), $options);
        return $result['secure_url'];
    }



    /**
     * Update featured image for an existing post
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateFeaturedImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'featured_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120'
        ]);

        try {
            $post = $this->postService->find($id);
            if (!$post) {
                return response()->json(['message' => 'Post not found'], 404);
            }

            $featuredImageUrl = $this->uploadFeaturedImage($request->file('featured_image'));
            
            $this->postService->update($id, ['featured_image' => $featuredImageUrl]);

            return response()->json([
                'message' => 'Featured image updated successfully',
                'featured_image_url' => $featuredImageUrl
            ]);

        } catch (ApiError $e) {
            Log::error('Failed to update featured image', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update featured image'], 500);
        } catch (\Exception $e) {
            Log::error('Failed to update featured image', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update featured image'], 500);
        }
    }
}
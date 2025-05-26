<?php

namespace Modules\Blog\App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Blog\App\Models\Post;
use Modules\Blog\App\Repositories\Interfaces\PostRepositoryInterface;

class PostRepository extends BaseRepository implements PostRepositoryInterface
{
    public function __construct(Post $model)
    {
        parent::__construct($model);
    }

    public function getPublishedPosts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getFeaturedPosts(int $limit = 5): Collection
    {
        return $this->query()
            ->where('status', 'published')
            ->where('is_featured', true)
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getPostsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('category_id', $categoryId)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getPostsByTag(string $tagSlug, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->whereHas('tags', function ($query) use ($tagSlug) {
                $query->where('slug', $tagSlug);
            })
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getPostsByAuthor(int $authorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('author_id', $authorId)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getRelatedPosts(int $postId, int $limit = 5): Collection
    {
        $post = $this->find($postId);
        if (!$post) {
            return collect();
        }

        return $this->query()
            ->where('id', '!=', $postId)
            ->where('category_id', $post->category_id)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function searchPosts(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%");
            })
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getPopularPosts(int $limit = 5): Collection
    {
        return $this->query()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecentPosts(int $limit = 5): Collection
    {
        return $this->query()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function incrementViewCount(int $id): bool
    {
        return $this->query()
            ->where('id', $id)
            ->increment('view_count');
    }
} 
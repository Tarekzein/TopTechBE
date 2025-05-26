<?php

namespace Modules\Blog\App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Blog\App\Models\Tag;
use Modules\Blog\App\Repositories\Interfaces\TagRepositoryInterface;

class TagRepository extends BaseRepository implements TagRepositoryInterface
{
    public function __construct(Tag $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->query()
            ->where('slug', $slug)
            ->first();
    }

    public function getPopularTags(int $limit = 10): Collection
    {
        return $this->query()
            ->withCount(['posts' => function ($query) {
                $query->where('status', 'published')
                    ->where('published_at', '<=', now());
            }])
            ->where('is_active', true)
            ->orderByDesc('posts_count')
            ->limit($limit)
            ->get();
    }

    public function getTagsWithPostCount(): Collection
    {
        return $this->query()
            ->withCount(['posts' => function ($query) {
                $query->where('status', 'published')
                    ->where('published_at', '<=', now());
            }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function searchTags(string $query): Collection
    {
        return $this->query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('slug', 'like', "%{$query}%")
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getPostsByTag(string $slug, int $perPage = 15): LengthAwarePaginator
    {
        $tag = $this->findBySlug($slug);
        if (!$tag) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        return $tag->posts()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function syncTags(int $postId, array $tagIds): void
    {
        $post = $this->model->posts()->find($postId);
        if ($post) {
            $post->tags()->sync($tagIds);
        }
    }

    public function getTagsByPost(int $postId): Collection
    {
        $post = $this->model->posts()->find($postId);
        if (!$post) {
            return collect();
        }
        return $post->tags()->where('is_active', true)->get();
    }

    public function createOrUpdate(array $data): Model
    {
        $tag = $this->query()->where('slug', $data['slug'])->first();
        if ($tag) {
            $tag->update($data);
            return $tag;
        }
        return $this->create($data);
    }

    public function mergeTags(int $targetTagId, array $tagIdsToMerge): bool
    {
        $targetTag = $this->find($targetTagId);
        if (!$targetTag) {
            return false;
        }

        $tagsToMerge = $this->query()->whereIn('id', $tagIdsToMerge)->get();
        foreach ($tagsToMerge as $tag) {
            // Move all posts from the tag to be merged to the target tag
            $tag->posts()->update(['tag_id' => $targetTagId]);
            // Delete the merged tag
            $tag->delete();
        }

        return true;
    }

    public function getOrCreateTags(array $tagNames): Collection
    {
        $tags = collect();
        foreach ($tagNames as $name) {
            $slug = \Illuminate\Support\Str::slug($name);
            $tag = $this->query()->where('slug', $slug)->first();
            if (!$tag) {
                $tag = $this->create([
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => true
                ]);
            }
            $tags->push($tag);
        }
        return $tags;
    }
} 
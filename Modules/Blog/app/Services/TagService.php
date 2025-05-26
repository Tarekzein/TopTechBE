<?php

namespace Modules\Blog\App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Blog\App\Repositories\Interfaces\TagRepositoryInterface;
use Modules\Blog\App\Services\Interfaces\TagServiceInterface;

class TagService implements TagServiceInterface
{
    protected TagRepositoryInterface $tagRepository;

    public function __construct(TagRepositoryInterface $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    public function getAllTags(): Collection
    {
        return $this->tagRepository->getAll();
    }

    public function create(array $data): Model
    {
        return $this->tagRepository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->tagRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->tagRepository->delete($id);
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->tagRepository->findBySlug($slug);
    }

    public function getPopularTags(int $limit = 10): Collection
    {
        return $this->tagRepository->getPopularTags($limit);
    }

    public function getTagsWithPostCount(): Collection
    {
        return $this->tagRepository->getTagsWithPostCount();
    }

    public function searchTags(string $query): Collection
    {
        return $this->tagRepository->searchTags($query);
    }

    public function getPostsByTag(string $slug, int $perPage = 15): LengthAwarePaginator
    {
        return $this->tagRepository->getPostsByTag($slug, $perPage);
    }

    public function toggleActive(int $id): bool
    {
        $tag = $this->tagRepository->find($id);
        if (!$tag) {
            return false;
        }
        return $this->tagRepository->update($id, [
            'is_active' => !$tag->is_active
        ]);
    }

    public function syncTags(int $postId, array $tagIds): void
    {
        $this->tagRepository->syncTags($postId, $tagIds);
    }

    public function getTagsByPost(int $postId): Collection
    {
        return $this->tagRepository->getTagsByPost($postId);
    }

    public function getAll(): Collection
    {
        return $this->tagRepository->getAll();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->tagRepository->paginate($perPage);
    }

    public function find(int $id): ?Model
    {
        return $this->tagRepository->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->tagRepository->findOrFail($id);
    }

    public function restore(int $id): bool
    {
        return $this->tagRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->tagRepository->forceDelete($id);
    }

    public function createOrUpdate(array $data): Model
    {
        if (isset($data['id'])) {
            $this->update($data['id'], $data);
            return $this->find($data['id']);
        }
        return $this->create($data);
    }

    public function mergeTags(int $targetTagId, array $tagIdsToMerge): bool
    {
        $targetTag = $this->find($targetTagId);
        if (!$targetTag) {
            return false;
        }

        // Get all posts associated with tags to be merged
        $postsToUpdate = collect();
        foreach ($tagIdsToMerge as $tagId) {
            $tag = $this->find($tagId);
            if ($tag) {
                $postsToUpdate = $postsToUpdate->merge($this->getTagsByPost($tagId));
            }
        }

        // Update posts to use the target tag
        foreach ($postsToUpdate as $post) {
            $currentTags = $this->getTagsByPost($post->id)->pluck('id')->toArray();
            $newTags = array_diff($currentTags, $tagIdsToMerge);
            $newTags[] = $targetTagId;
            $this->syncTags($post->id, array_unique($newTags));
        }

        // Delete the merged tags
        foreach ($tagIdsToMerge as $tagId) {
            $this->delete($tagId);
        }

        return true;
    }

    public function getOrCreateTags(array $tagNames): Collection
    {
        $tags = collect();
        foreach ($tagNames as $name) {
            $slug = Str::slug($name);
            $tag = $this->findBySlug($slug);
            if (!$tag) {
                $tag = $this->create([
                    'name' => $name,
                    'slug' => $slug
                ]);
            }
            $tags->push($tag);
        }
        return $tags;
    }
} 
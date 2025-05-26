<?php

namespace Modules\Blog\App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Blog\App\Repositories\Interfaces\PostRepositoryInterface;
use Modules\Blog\App\Services\Interfaces\PostServiceInterface;

class PostService implements PostServiceInterface
{
    protected PostRepositoryInterface $postRepository;

    public function __construct(PostRepositoryInterface $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function getPublishedPosts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getPublishedPosts($perPage);
    }

    public function create(array $data): Model
    {
        return $this->postRepository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->postRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->postRepository->delete($id);
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->postRepository->findBySlug($slug);
    }

    public function publish(int $id): bool
    {
        return $this->postRepository->update($id, [
            'status' => 'published',
            'published_at' => now()
        ]);
    }

    public function unpublish(int $id): bool
    {
        return $this->postRepository->update($id, [
            'status' => 'draft',
            'published_at' => null
        ]);
    }

    public function archive(int $id): bool
    {
        return $this->postRepository->update($id, [
            'status' => 'archived'
        ]);
    }

    public function toggleFeatured(int $id): bool
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return false;
        }
        return $this->postRepository->update($id, [
            'is_featured' => !$post->is_featured
        ]);
    }

    public function searchPosts(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->searchPosts($query, $perPage);
    }

    public function getRelatedPosts(int $postId, int $limit = 5): Collection
    {
        return $this->postRepository->getRelatedPosts($postId, $limit);
    }

    public function getPopularPosts(int $limit = 5): Collection
    {
        return $this->postRepository->getPopularPosts($limit);
    }

    public function getRecentPosts(int $limit = 5): Collection
    {
        return $this->postRepository->getRecentPosts($limit);
    }

    public function getFeaturedPosts(int $limit = 5): Collection
    {
        return $this->postRepository->getFeaturedPosts($limit);
    }

    public function incrementViewCount(int $id): bool
    {
        return $this->postRepository->incrementViewCount($id);
    }

    public function getAll(): Collection
    {
        return $this->postRepository->getAll();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->paginate($perPage);
    }

    public function find(int $id): ?Model
    {
        return $this->postRepository->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->postRepository->findOrFail($id);
    }

    public function restore(int $id): bool
    {
        return $this->postRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->postRepository->forceDelete($id);
    }

    public function getPostsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getPostsByCategory($categoryId, $perPage);
    }

    public function getPostsByTag(int $tagId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getPostsByTag($tagId, $perPage);
    }

    public function getPostsByAuthor(int $authorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->postRepository->getPostsByAuthor($authorId, $perPage);
    }

    public function publishPost(int $id): bool
    {
        return $this->publish($id);
    }

    public function unpublishPost(int $id): bool
    {
        return $this->unpublish($id);
    }

    public function archivePost(int $id): bool
    {
        return $this->archive($id);
    }
}

<?php

namespace Modules\Blog\App\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostServiceInterface extends BaseServiceInterface
{
    public function getPublishedPosts(int $perPage = 15): LengthAwarePaginator;
    public function getFeaturedPosts(int $limit = 5): Collection;
    public function getPostsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;
    public function getPostsByTag(int $tagId, int $perPage = 15): LengthAwarePaginator;
    public function getPostsByAuthor(int $authorId, int $perPage = 15): LengthAwarePaginator;
    public function getRelatedPosts(int $postId, int $limit = 5): Collection;
    public function searchPosts(string $query, int $perPage = 15): LengthAwarePaginator;
    public function getPopularPosts(int $limit = 5): Collection;
    public function getRecentPosts(int $limit = 5): Collection;
    public function publishPost(int $id): bool;
    public function unpublishPost(int $id): bool;
    public function archivePost(int $id): bool;
    public function incrementViewCount(int $id): bool;
    public function toggleFeatured(int $id): bool;
    public function findBySlug(string $slug): ?Model;
} 
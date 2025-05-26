<?php

namespace Modules\Blog\App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface extends BaseRepositoryInterface
{
    public function getPublishedPosts(int $perPage = 15): LengthAwarePaginator;
    public function findBySlug(string $slug): ?Model;
    public function getPostsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;
    public function getPostsByTag(string $tagSlug, int $perPage = 15): LengthAwarePaginator;
    public function getPostsByAuthor(int $authorId, int $perPage = 15): LengthAwarePaginator;
    public function searchPosts(string $query, int $perPage = 15): LengthAwarePaginator;
    public function getRelatedPosts(int $postId, int $limit = 5): Collection;
    public function getPopularPosts(int $limit = 5): Collection;
    public function getRecentPosts(int $limit = 5): Collection;
    public function getFeaturedPosts(int $limit = 5): Collection;
    public function incrementViewCount(int $id): bool;
} 
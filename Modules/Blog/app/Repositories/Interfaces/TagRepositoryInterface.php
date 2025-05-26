<?php

namespace Modules\Blog\App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TagRepositoryInterface extends BaseRepositoryInterface
{
    public function findBySlug(string $slug): ?Model;
    public function getPopularTags(int $limit = 10): Collection;
    public function getTagsWithPostCount(): Collection;
    public function searchTags(string $query): Collection;
    public function getPostsByTag(string $slug, int $perPage = 15): LengthAwarePaginator;
    public function syncTags(int $postId, array $tagIds): void;
    public function getTagsByPost(int $postId): Collection;
    public function createOrUpdate(array $data): Model;
    public function mergeTags(int $targetTagId, array $tagIdsToMerge): bool;
    public function getOrCreateTags(array $tagNames): Collection;
} 
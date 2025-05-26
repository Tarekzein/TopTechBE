<?php

namespace Modules\Blog\App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CommentRepositoryInterface extends BaseRepositoryInterface
{
    public function getCommentsByPost(int $postId, int $perPage = 15): LengthAwarePaginator;
    public function getApprovedCommentsByPost(int $postId, int $perPage = 15): LengthAwarePaginator;
    public function getPendingComments(int $perPage = 15): LengthAwarePaginator;
    public function getSpamComments(int $perPage = 15): LengthAwarePaginator;
    public function getCommentsByUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    public function approveComment(int $id): bool;
    public function markAsSpam(int $id): bool;
    public function getCommentReplies(int $commentId): Collection;
    public function getCommentCountByPost(int $postId): int;
    public function getRecentComments(int $limit = 5): Collection;
} 
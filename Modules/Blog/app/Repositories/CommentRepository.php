<?php

namespace Modules\Blog\App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Blog\App\Models\Comment;
use Modules\Blog\App\Repositories\Interfaces\CommentRepositoryInterface;

class CommentRepository extends BaseRepository implements CommentRepositoryInterface
{
    public function __construct(Comment $model)
    {
        parent::__construct($model);
    }

    public function getCommentsByPost(int $postId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->with(['replies' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getApprovedCommentsByPost(int $postId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('post_id', $postId)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->with(['replies' => function ($query) {
                $query->where('status', 'approved')
                    ->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getPendingComments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getSpamComments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('status', 'spam')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getCommentsByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function approveComment(int $id): bool
    {
        return $this->update($id, ['status' => 'approved']);
    }

    public function markAsSpam(int $id): bool
    {
        return $this->update($id, ['status' => 'spam']);
    }

    public function getCommentReplies(int $commentId): Collection
    {
        return $this->query()
            ->where('parent_id', $commentId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getCommentCountByPost(int $postId): int
    {
        return $this->query()
            ->where('post_id', $postId)
            ->where('status', 'approved')
            ->count();
    }

    public function getRecentComments(int $limit = 5): Collection
    {
        return $this->query()
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
} 
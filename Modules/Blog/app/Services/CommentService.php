<?php

namespace Modules\Blog\App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Blog\App\Repositories\Interfaces\CommentRepositoryInterface;
use Modules\Blog\App\Services\Interfaces\CommentServiceInterface;

class CommentService implements CommentServiceInterface
{
    protected CommentRepositoryInterface $commentRepository;

    public function __construct(CommentRepositoryInterface $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    public function getCommentsByPost(int $postId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->getCommentsByPost($postId, $perPage);
    }

    public function create(array $data): Model
    {
        return $this->commentRepository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->commentRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->commentRepository->delete($id);
    }

    public function find(int $id): ?Model
    {
        return $this->commentRepository->find($id);
    }

    public function getApprovedCommentsByPost(int $postId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->getApprovedCommentsByPost($postId, $perPage);
    }

    public function getPendingComments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->getPendingComments($perPage);
    }

    public function getSpamComments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->getSpamComments($perPage);
    }

    public function getCommentsByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->getCommentsByUser($userId, $perPage);
    }

    public function approveComment(int $id): bool
    {
        return $this->commentRepository->update($id, [
            'status' => 'approved'
        ]);
    }

    public function markAsSpam(int $id): bool
    {
        return $this->commentRepository->update($id, [
            'status' => 'spam'
        ]);
    }

    public function getCommentReplies(int $commentId): Collection
    {
        return $this->commentRepository->getCommentReplies($commentId);
    }

    public function getCommentCountByPost(int $postId): int
    {
        return $this->commentRepository->getCommentCountByPost($postId);
    }

    public function getRecentComments(int $limit = 5): Collection
    {
        return $this->commentRepository->getRecentComments($limit);
    }

    public function getAll(): Collection
    {
        return $this->commentRepository->getAll();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->paginate($perPage);
    }

    public function findOrFail(int $id): Model
    {
        return $this->commentRepository->findOrFail($id);
    }

    public function restore(int $id): bool
    {
        return $this->commentRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->commentRepository->forceDelete($id);
    }
} 
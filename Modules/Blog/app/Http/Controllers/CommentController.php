<?php

namespace Modules\Blog\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Blog\App\Services\Interfaces\CommentServiceInterface;
use Modules\Blog\App\Http\Requests\CommentRequest;

class CommentController extends Controller
{
    protected CommentServiceInterface $commentService;

    public function __construct(CommentServiceInterface $commentService)
    {
        $this->commentService = $commentService;
    }

    public function index(Request $request, int $postId): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $comments = $this->commentService->getCommentsByPost($postId, $perPage);
        return response()->json($comments);
    }

    public function store(CommentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $comment = $this->commentService->create($data);
        return response()->json($comment, 201);
    }

    public function show(int $id): JsonResponse
    {
        $comment = $this->commentService->find($id);
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        return response()->json($comment);
    }

    public function update(CommentRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $updated = $this->commentService->update($id, $data);
        if (!$updated) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        return response()->json(['message' => 'Comment updated successfully']);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->commentService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        return response()->json(['message' => 'Comment deleted successfully']);
    }

    public function getApprovedComments(Request $request, int $postId): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $comments = $this->commentService->getApprovedCommentsByPost($postId, $perPage);
        return response()->json($comments);
    }

    public function getPendingComments(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $comments = $this->commentService->getPendingComments($perPage);
        return response()->json($comments);
    }

    public function getSpamComments(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $comments = $this->commentService->getSpamComments($perPage);
        return response()->json($comments);
    }

    public function getUserComments(Request $request, int $userId): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $comments = $this->commentService->getCommentsByUser($userId, $perPage);
        return response()->json($comments);
    }

    public function approve(int $id): JsonResponse
    {
        $approved = $this->commentService->approveComment($id);
        if (!$approved) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        return response()->json(['message' => 'Comment approved successfully']);
    }

    public function markAsSpam(int $id): JsonResponse
    {
        $marked = $this->commentService->markAsSpam($id);
        if (!$marked) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        return response()->json(['message' => 'Comment marked as spam successfully']);
    }

    public function getReplies(int $commentId): JsonResponse
    {
        $replies = $this->commentService->getCommentReplies($commentId);
        return response()->json($replies);
    }

    public function getCommentCount(int $postId): JsonResponse
    {
        $count = $this->commentService->getCommentCountByPost($postId);
        return response()->json(['count' => $count]);
    }

    public function getRecentComments(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        $comments = $this->commentService->getRecentComments($limit);
        return response()->json($comments);
    }
} 
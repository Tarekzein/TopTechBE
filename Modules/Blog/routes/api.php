<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\App\Http\Controllers\PostController;
use Modules\Blog\App\Http\Controllers\BlogCategoryController;
use Modules\Blog\App\Http\Controllers\TagController;
use Modules\Blog\App\Http\Controllers\CommentController;

/*
|--------------------------------------------------------------------------
| Blog Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your blog module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::prefix('blog')->group(function () {
    // Posts routes
    // Public routes (no authentication required)
    // FIXED: Put specific routes BEFORE parameterized routes
    Route::get('posts/search', [PostController::class, 'search']);
    Route::get('posts/popular', [PostController::class, 'getPopular']);
    Route::get('posts/recent', [PostController::class, 'getRecent']);
    Route::get('posts/featured', [PostController::class, 'getFeatured']);

    // Parameterized routes should come AFTER specific routes
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{slug}', [PostController::class, 'show']);
    Route::get('posts/{id}/related', [PostController::class, 'getRelated']);

    // Protected routes (authentication required)
    Route::middleware('auth:sanctum')->group(function () {
        // CRUD operations
        Route::post('posts', [PostController::class, 'store']);
        Route::put('posts/{id}', [PostController::class, 'update']);
        Route::delete('posts/{id}', [PostController::class, 'destroy']);
        
        // Post management actions
        Route::post('posts/{id}/publish', [PostController::class, 'publish']);
        Route::post('posts/{id}/unpublish', [PostController::class, 'unpublish']);
        Route::post('posts/{id}/archive', [PostController::class, 'archive']);
        Route::post('posts/{id}/toggle-featured', [PostController::class, 'toggleFeatured']);
    });

    
    // Categories routes
    Route::get('categories', [BlogCategoryController::class, 'index']);
    Route::post('categories', [BlogCategoryController::class, 'store']);

    Route::get('categories/tree', [BlogCategoryController::class, 'getTree']);
    Route::get('categories/with-post-count', [BlogCategoryController::class, 'getWithPostCount']);
    Route::get('categories/parents', [BlogCategoryController::class, 'getParents']);

    Route::get('categories/{id}/with-children', [BlogCategoryController::class, 'getWithChildren']);
    Route::get('categories/{parentId}/children', [BlogCategoryController::class, 'getChildren']);

    Route::put('categories/{id}', [BlogCategoryController::class, 'update']);
    Route::delete('categories/{id}', [BlogCategoryController::class, 'destroy']);
    Route::post('categories/{id}/toggle-active', [BlogCategoryController::class, 'toggleActive']);
    Route::post('categories/update-order', [BlogCategoryController::class, 'updateOrder']);
    Route::post('categories/{id}/move', [BlogCategoryController::class, 'move']);

    // Put slug route LAST to avoid conflicts
    Route::get('categories/{slug}', [BlogCategoryController::class, 'show']);

    // Tags Routes
    // Tags routes - SPECIFIC ROUTES FIRST
    Route::get('tags', [TagController::class, 'index']);
    Route::post('tags', [TagController::class, 'store']);
    Route::get('tags/popular', [TagController::class, 'getPopular']);
    Route::get('tags/with-post-count', [TagController::class, 'getWithPostCount']);
    Route::get('tags/search', [TagController::class, 'search']);

    // Tags routes - PARAMETERIZED ROUTES LAST
    Route::get('tags/{slug}', [TagController::class, 'show']);
    Route::put('tags/{id}', [TagController::class, 'update']);
    Route::delete('tags/{id}', [TagController::class, 'destroy']);
    Route::get('tags/{slug}/posts', [TagController::class, 'getPostsByTag']);
    Route::post('tags/{id}/toggle-active', [TagController::class, 'toggleActive']);

    // Comments routes
   // Comments routes - SPECIFIC ROUTES FIRST
    Route::get('comments/pending', [CommentController::class, 'getPendingComments']);
    Route::get('comments/spam', [CommentController::class, 'getSpamComments']);
    Route::get('comments/recent', [CommentController::class, 'getRecentComments']);
    Route::post('comments', [CommentController::class, 'store']);

    // Comments routes - PARAMETERIZED ROUTES LAST
    Route::get('comments/{id}', [CommentController::class, 'show']);
    Route::put('comments/{id}', [CommentController::class, 'update']);
    Route::delete('comments/{id}', [CommentController::class, 'destroy']);
    Route::post('comments/{id}/approve', [CommentController::class, 'approve']);
    Route::post('comments/{id}/mark-as-spam', [CommentController::class, 'markAsSpam']);
    Route::get('comments/{commentId}/replies', [CommentController::class, 'getReplies']);

    // Post-related comment routes - SPECIFIC FIRST
    Route::get('posts/{postId}/approved-comments', [CommentController::class, 'getApprovedComments']);
    Route::get('posts/{postId}/comment-count', [CommentController::class, 'getCommentCount']);

    // Post-related comment routes - PARAMETERIZED LAST  
    Route::get('posts/{postId}/comments', [CommentController::class, 'index']);

    // User-related comment routes
    Route::get('users/{userId}/comments', [CommentController::class, 'getUserComments']);
});

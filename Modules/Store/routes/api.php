<?php
use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\CategoryController;
use Modules\Store\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Store Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('store')->group(function () {
    // Category Routes
    Route::prefix('categories')->group(function () {
        // Public routes
        Route::get('/', [CategoryController::class, 'index'])->name('store.categories.index');
        Route::get('/root', [CategoryController::class, 'rootCategories'])->name('store.categories.root');
        Route::get('/{slug}', [CategoryController::class, 'showBySlug'])->name('store.categories.show.by.slug');
        Route::get('/id/{id}', [CategoryController::class, 'show'])->name('store.categories.show');

        // Protected routes - Admin only
        Route::middleware(['auth:sanctum', 'role:admin,super-admin'])->group(function () {
            Route::post('/', [CategoryController::class, 'store'])->name('store.categories.store');
            Route::put('/{id}', [CategoryController::class, 'update'])->name('store.categories.update');
            Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('store.categories.destroy');
        });
    });

    // Product Routes
    Route::prefix('products')->group(function () {
        // Public routes
        Route::get('/', [ProductController::class, 'index'])->name('store.products.index');
        Route::get('/search', [ProductController::class, 'search'])->name('store.products.search');
        Route::get('/category/{categoryId}', [ProductController::class, 'getByCategory'])->name('store.products.by.category');
        Route::get('/vendor/{vendorId}', [ProductController::class, 'getByVendor'])->name('store.products.by.vendor');
        Route::get('/{slug}', [ProductController::class, 'showBySlug'])->name('store.products.show.by.slug');
        Route::get('/id/{id}', [ProductController::class, 'show'])->name('store.products.show');

        // Protected routes - Admin and Vendor
        Route::middleware(['auth:sanctum', 'role:admin,super-admin,vendor'])->group(function () {
            Route::post('/', [ProductController::class, 'store'])->name('store.products.store');
            Route::put('/{id}', [ProductController::class, 'update'])->name('store.products.update');
            Route::delete('/{id}', [ProductController::class, 'destroy'])->name('store.products.destroy');
        });
    });
});

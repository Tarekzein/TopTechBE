<?php
use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\CategoryController;
use Modules\Store\Http\Controllers\ProductController;
use Modules\Store\Http\Controllers\CartController;
use Modules\Store\Http\Controllers\WishlistController;
use Modules\Store\Http\Controllers\ProductAttributeController;
use Modules\Store\Http\Controllers\ProductVariationController;

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

    // Product Attributes
    Route::prefix('attributes')->group(function () {
        Route::get('/', [ProductAttributeController::class, 'index']);
        Route::post('/', [ProductAttributeController::class, 'store']);
        Route::get('/{id}', [ProductAttributeController::class, 'show']);
        Route::put('/{id}', [ProductAttributeController::class, 'update']);
        Route::delete('/{id}', [ProductAttributeController::class, 'destroy']);

        // Attribute Values
        Route::get('/{attributeId}/values', [ProductAttributeController::class, 'getValues']);
        Route::post('/values', [ProductAttributeController::class, 'storeValue']);
        Route::get('/values/{id}', [ProductAttributeController::class, 'getValue']);
        Route::put('/values/{id}', [ProductAttributeController::class, 'updateValue']);
        Route::delete('/values/{id}', [ProductAttributeController::class, 'destroyValue']);
    });

    // Product Variations
    Route::prefix('products/{productId}/variations')->group(function () {
        Route::get('/', [ProductVariationController::class, 'index']);
        Route::post('/', [ProductVariationController::class, 'store']);
        Route::get('/{id}', [ProductVariationController::class, 'show']);
        Route::put('/{id}', [ProductVariationController::class, 'update']);
        Route::delete('/{id}', [ProductVariationController::class, 'destroy']);

        // Variation Images
        Route::post('/images', [ProductVariationController::class, 'addImage']);
        Route::delete('/images/{id}', [ProductVariationController::class, 'removeImage']);
        Route::put('/images/order', [ProductVariationController::class, 'updateImageOrder']);
    });

    // Cart Routes
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'getCart'])->name('store.cart.get');
        Route::post('/add', [CartController::class, 'addItem'])->name('store.cart.add');
        Route::put('/update', [CartController::class, 'updateItem'])->name('store.cart.update');
        Route::delete('/remove', [CartController::class, 'removeItem'])->name('store.cart.remove');
        Route::delete('/clear', [CartController::class, 'clearCart'])->name('store.cart.clear');
        Route::post('/merge', [CartController::class, 'mergeOnLogin'])->middleware('auth:sanctum')->name('store.cart.merge');
    });

    // Wishlist Routes
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/items', [WishlistController::class, 'addItem']);
        Route::delete('/items/{productId}', [WishlistController::class, 'removeItem']);
        Route::delete('/', [WishlistController::class, 'clear']);
        Route::post('/merge', [WishlistController::class, 'merge'])->middleware('auth:api');
    });
});

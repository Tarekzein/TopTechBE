<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\CategoryController;
use Modules\Store\Http\Controllers\ProductController;
use Modules\Store\Http\Controllers\CartController;
use Modules\Store\Http\Controllers\WishlistController;
use Modules\Store\Http\Controllers\ProductAttributeController;
use Modules\Store\Http\Controllers\ProductVariationController;
use Modules\Store\Http\Controllers\OrderController;
use Modules\Store\Http\Controllers\AddressController;
use Modules\Store\Http\Controllers\PaymentController;
use Modules\Store\Http\Controllers\SettingController;
use Modules\Store\Http\Controllers\CurrencyController;
use Modules\Store\Http\Controllers\AnalyticsController;
use Modules\Store\Http\Controllers\CustomerController;
/*
|--------------------------------------------------------------------------
| Store Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('store')->group(function () {
    // Currency Routes
    Route::prefix('currencies')->group(function () {
        Route::get('/', [CurrencyController::class, 'index']);
    });

    // Category Routes
    Route::prefix('categories')->group(function () {
        // Public routes
        Route::get('/', [CategoryController::class, 'index'])->name('store.categories.index');
        Route::get('/root', [CategoryController::class, 'rootCategories'])->name('store.categories.root');
        Route::get('/{slug}', [CategoryController::class, 'showBySlug'])->name('store.categories.show.by.slug');
        Route::get('/id/{id}', [CategoryController::class, 'show'])->name('store.categories.show');

        // Protected routes - Admin only
        Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->group(function () {
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
        Route::middleware(['auth:sanctum', 'role:admin|super-admin|vendor'])->group(function () {
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

    // Order routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Customer routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{orderNumber}', [OrderController::class, 'show']);
        });

        // Admin routes
        Route::middleware(['role:admin|super-admin'])->prefix('admin/orders')->group(function () {
            Route::get('/', [OrderController::class, 'adminIndex']);
            Route::patch('/{orderNumber}/status', [OrderController::class, 'updateStatus']);
            Route::patch('/{orderNumber}/payment-status', [OrderController::class, 'updatePaymentStatus']);
            Route::patch('/{orderNumber}/shipping', [OrderController::class, 'updateShippingInfo']);
        });

        // Vendor routes
        Route::middleware(['role:vendor'])->prefix('vendor/orders')->group(function () {
            Route::get('/', [OrderController::class, 'vendorIndex']);
            Route::get('/{orderNumber}', [OrderController::class, 'vendorShow']);
            Route::patch('/{orderNumber}/status', [OrderController::class, 'vendorUpdateStatus']);
            Route::patch('/{orderNumber}/shipping', [OrderController::class, 'vendorUpdateShippingInfo']);
        });

        Route::group(['prefix' => 'addresses'], function () {
            // Address Management Routes
            Route::get('/billing', [AddressController::class, 'getBillingAddresses']);
            Route::get('/billing/default', [AddressController::class, 'getDefaultBillingAddress']);
            Route::post('/billing', [AddressController::class, 'createBillingAddress']);
            Route::put('/billing/{address}', [AddressController::class, 'updateBillingAddress']);
            Route::delete('/billing/{address}', [AddressController::class, 'deleteBillingAddress']);

            // Shipping Addresses
            Route::get('/shipping', [AddressController::class, 'getShippingAddresses']);
            Route::get('/shipping/default', [AddressController::class, 'getDefaultShippingAddress']);
            Route::post('/shipping', [AddressController::class, 'createShippingAddress']);
            Route::put('/shipping/{address}', [AddressController::class, 'updateShippingAddress']);
            Route::delete('/shipping/{address}', [AddressController::class, 'deleteShippingAddress']);
        });


    });

    Route::group(["prefix"=>"settings"],function () {
        // Settings routes
        Route::get('/', [SettingController::class, 'index']);
        Route::get('/groups', [SettingController::class, 'getGroups']);
        Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->group(function () {
            Route::get('/{key}', [SettingController::class, 'show']);
            Route::put('/{key}', [SettingController::class, 'update']);
            Route::put('/bulk-update', [SettingController::class, 'bulkUpdate']);
        });
    });

    // Payment Routes
    Route::prefix('payments')->group(function () {
        Route::get('methods', [PaymentController::class, 'getAvailableMethods']);
        Route::post('orders/{orderId}/process', [PaymentController::class, 'processPayment']);
        Route::post('callback/{method}', [PaymentController::class, 'handleCallback']);

        // Admin routes for payment method configuration
        Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->group(function () {
            Route::get('methods/{method}/config', [PaymentController::class, 'getMethodConfig']);
            Route::put('methods/{method}/config', [PaymentController::class, 'updateMethodConfig']);
        });

        // Geidea session creation
        Route::post('geidea/session', [PaymentController::class, 'createGeideaSession']);
        // Geidea callback
        Route::post('geidea/callback', [PaymentController::class, 'geideaCallback']);
    });

    // PromoCode validation route
    Route::get('promocodes/validate', [\Modules\Store\Http\Controllers\PromoCodeController::class, 'validateCode']);

    // Analytics Routes
    Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('analytics')->group(function () {
        Route::get('/dashboard', [AnalyticsController::class, 'getVendorDashboard']);
        Route::get('/revenue', [AnalyticsController::class, 'getRevenueAnalytics']);
        Route::get('/orders', [AnalyticsController::class, 'getOrdersAnalytics']);
        Route::get('/products', [AnalyticsController::class, 'getProductsAnalytics']);
        Route::get('/customers', [AnalyticsController::class, 'getCustomersAnalytics']);
        Route::get('/summary', [AnalyticsController::class, 'getSummary']);
        Route::post('/export', [AnalyticsController::class, 'exportReport']);
    });

    // Admin Analytics Routes
    Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('admin/analytics')->group(function () {
        Route::get('/vendors', [AnalyticsController::class, 'getAllVendorsAnalytics']);
        Route::get('/vendors/{vendorId}', [AnalyticsController::class, 'getVendorAnalytics']);
    });

    // Customer Routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Admin customer routes
        Route::middleware(['role:admin|super-admin'])->prefix('admin/customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index']);
            Route::get('/{customerId}', [CustomerController::class, 'show']);
            Route::get('/analytics/summary', [CustomerController::class, 'analytics']);
            Route::get('/export', [CustomerController::class, 'export']);
            
            // Admin routes for specific vendor's customers
            Route::get('/vendors/{vendorId}', [CustomerController::class, 'adminVendorIndex']);
            Route::get('/vendors/{vendorId}/customers/{customerId}', [CustomerController::class, 'adminVendorShow']);
            Route::get('/vendors/{vendorId}/analytics', [CustomerController::class, 'adminVendorAnalytics']);
        });

        // Vendor customer routes
        Route::middleware(['role:vendor'])->prefix('vendor/customers')->group(function () {
            Route::get('/', [CustomerController::class, 'vendorIndex']);
            Route::get('/{customerId}', [CustomerController::class, 'vendorShow']);
            Route::get('/analytics/summary', [CustomerController::class, 'vendorAnalytics']);
            Route::get('/export', [CustomerController::class, 'vendorExport']);
        });

        // General customer summary (accessible by both admin and vendor)
        Route::get('/customers/summary', [CustomerController::class, 'summary']);
    });
});

Route::get('/debug-auth', function (Request $request) {
    return response()->json([
        'headers' => $request->headers->all(),
        'authorization_header' => $request->header('Authorization'),
        'bearer_token' => $request->bearerToken(),
        'user' => auth('sanctum')->user(),
        'guards' => array_keys(config('auth.guards')),
        'sanctum_config' => config('sanctum'),
        'app_url' => config('app.url'),
        'session_domain' => config('session.domain'),
    ]);
});

// Test with authentication middleware
Route::middleware('auth:sanctum')->get('/debug-auth-protected', function (Request $request) {
    return response()->json([
        'message' => 'Authentication successful!',
        'user' => $request->user(),
        'user_id' => $request->user()->id,
    ]);
});


<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendor\Http\Controllers\VendorController;
use Modules\Vendor\Http\Controllers\VendorAccountController;
use Modules\Vendor\Http\Controllers\VendorFinancialController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

// Vendor account management routes
Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor/account')->group(function () {
    Route::get('/', [VendorAccountController::class, 'getAccountData']);
    Route::put('/user', [VendorAccountController::class, 'updateUserData']);
    Route::put('/vendor', [VendorAccountController::class, 'updateVendorData']);
    Route::put('/', [VendorAccountController::class, 'updateAccountData']);
});

// Vendor financial management routes
Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor/financial')->group(function () {
    Route::get('/overview', [VendorFinancialController::class, 'getFinancialOverview']);
    Route::get('/analytics', [VendorFinancialController::class, 'getFinancialAnalytics']);
    Route::get('/reports', [VendorFinancialController::class, 'getFinancialReports']);
});

// General vendor CRUD routes - requires authentication and appropriate permissions
Route::middleware(['auth:sanctum'])->group(function () {
    // Get all vendors
    Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index');

    // Get specific vendor
    Route::get('vendors/{id}', [VendorController::class, 'show'])->name('vendors.show');

    // Create new vendor
    Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');

    // Update vendor
    Route::put('vendors/{id}', [VendorController::class, 'update'])->name('vendors.update');

    // Delete vendor
    Route::delete('vendors/{id}', [VendorController::class, 'destroy'])->name('vendors.destroy');
});


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

// General vendor routes (if needed)
//Route::middleware(['auth:sanctum'])->group(function () {
//    Route::apiResource('vendor', VendorController::class)->names('vendor');
//});

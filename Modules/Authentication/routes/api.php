<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\AuthenticationController;

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

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthenticationController::class, 'register']);
    Route::post('/vendor-register', [AuthenticationController::class, 'vendorRegister']);
    Route::post('/login', [AuthenticationController::class, 'login']);
    Route::post('/dashboard-login', [AuthenticationController::class, 'dashboardLogin']);
    Route::post('/logout', [AuthenticationController::class, 'logout'])->middleware('auth:sanctum');
    // Password reset routes
    Route::post('forgot-password', [AuthenticationController::class, 'forgotPassword']);
    Route::post('verify-otp', [AuthenticationController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthenticationController::class, 'resetPassword']);

});

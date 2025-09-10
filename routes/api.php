<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
use Modules\User\Http\Controllers\FcmTokenController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/fcm-tokens', [FcmTokenController::class, 'store']);
});

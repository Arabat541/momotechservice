<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApiClientController;

/*
|--------------------------------------------------------------------------
| API Routes — Momo Tech Service
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'apiLogin']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'apiLogout']);

    // API publique revendeur (v1)
    Route::prefix('v1/client')->group(function () {
        Route::get('/repairs',  [ApiClientController::class, 'repairs']);
        Route::get('/invoices', [ApiClientController::class, 'invoices']);
        Route::get('/credits',  [ApiClientController::class, 'credits']);
    });
});

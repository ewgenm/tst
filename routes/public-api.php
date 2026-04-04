<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes (NO Sanctum middleware)
|--------------------------------------------------------------------------
|
| These routes are accessible by external API clients (curl, mobile apps, etc.)
| They do NOT require CSRF tokens or Sanctum session cookies.
|
*/

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

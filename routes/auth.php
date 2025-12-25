<?php

use App\Domains\Auth\Controllers\LoginController;
use App\Domains\Auth\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle user authentication for the platform.
| Includes login, registration, and logout for all user types.
|
*/

/*
|--------------------------------------------------------------------------
| Guest Routes (Unauthenticated Users)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Unified login
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Role selector (for accounts with multiple roles)
    Route::get('/login/select-role', [LoginController::class, 'showSelectRole'])->name('login.select-role');
    Route::post('/login/select-role', [LoginController::class, 'storeSelectRole']);

    // Client login
    Route::get('/login/client', [LoginController::class, 'showClient'])->name('login.client');
    Route::post('/login/client', [LoginController::class, 'storeClient']);

    // Provider login
    Route::get('/login/provider', [LoginController::class, 'showProvider'])->name('login.provider');
    Route::post('/login/provider', [LoginController::class, 'storeProvider']);

    // Client registration
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // Provider registration
    Route::get('/register/provider', [RegisterController::class, 'showProvider'])->name('register.provider');
    Route::post('/register/provider', [RegisterController::class, 'storeProvider']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

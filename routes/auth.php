<?php

use App\Domains\Auth\Controllers\LoginController;
use App\Domains\Auth\Controllers\RegisterController;
use App\Domains\Auth\Controllers\SocialAccountController;
use App\Domains\Auth\Controllers\TeamInvitationController;
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

    Route::get('/', fn () => redirect()->route('login'));
    
    // Unified login
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Client login
    Route::get('/login/client', [LoginController::class, 'showClient'])->name('login.client');
    Route::post('/login/client', [LoginController::class, 'storeClient']);

    // Provider login
    Route::get('/login/provider', [LoginController::class, 'showProvider'])->name('login.provider');
    Route::post('/login/provider', [LoginController::class, 'storeProvider']);

    // Client registration (blocked during launch mode)
    Route::middleware('launch.mode')->group(function () {
        Route::get('/register', [RegisterController::class, 'show'])->name('register');
        Route::post('/register', [RegisterController::class, 'store']);
    });

    // Provider registration (blocked during launch mode)
    Route::middleware('launch.mode')->group(function () {
        Route::get('/register/provider', [RegisterController::class, 'showProvider'])->name('register.provider');
        Route::post('/register/provider', [RegisterController::class, 'storeProvider']);
    });



    Route::prefix('social')->group(function () {
        Route::get('{provider}/redirect', [LoginController::class, 'redirectToProvider'])->name('social.redirect');
        Route::get('{provider}/callback', [LoginController::class, 'handleProviderCallback'])->name('social.callback');
    });

});

/*
|--------------------------------------------------------------------------
| Team Invitation Routes (accessible by anyone)
|--------------------------------------------------------------------------
*/

Route::get('/team/invite/{token}', [TeamInvitationController::class, 'show'])->name('team.invite.show');
Route::post('/team/invite/{token}', [TeamInvitationController::class, 'accept'])->name('team.invite.accept');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Social account linking/unlinking (authenticated users)
    Route::prefix('social')->group(function () {
        Route::get('{provider}/link', [SocialAccountController::class, 'link'])->name('social.link');
        Route::delete('{provider}/unlink', [SocialAccountController::class, 'unlink'])->name('social.unlink');
    });
});

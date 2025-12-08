<?php

use App\Domains\Auth\Controllers\LoginController;
use App\Domains\Auth\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Guest Routes (Authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Login selector page
    Route::get('/login', [LoginController::class, 'show'])->name('login');

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

/*
|--------------------------------------------------------------------------
| Client Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:client'])->prefix('dashboard')->name('client.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Client/Dashboard');
    })->name('dashboard');

    // Additional client routes will be added in Phase 3
    // Route::get('/bookings', [ClientBookingController::class, 'index'])->name('bookings');
    // Route::get('/payments', [ClientPaymentController::class, 'index'])->name('payments');
    // Route::get('/profile', [ClientProfileController::class, 'edit'])->name('profile');
});

/*
|--------------------------------------------------------------------------
| Provider Console Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:provider'])->prefix('console')->name('provider.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Provider/Dashboard');
    })->name('dashboard');

    // Additional provider routes will be added in Phase 2
    // Route::get('/profile', [ProviderProfileController::class, 'edit'])->name('profile');
    // Route::resource('services', ProviderServiceController::class);
    // Route::resource('portfolios', PortfolioController::class);
    // Route::get('/availability', [AvailabilityController::class, 'edit'])->name('availability');
    // Route::get('/bookings', [ProviderBookingController::class, 'index'])->name('bookings');
    // Route::get('/payments', [ProviderPaymentController::class, 'index'])->name('payments');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Future)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');
});

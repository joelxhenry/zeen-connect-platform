<?php

use App\Domains\Booking\Controllers\ClientBookingController;
use App\Domains\Client\Controllers\DashboardController as ClientDashboardController;
use App\Domains\Client\Controllers\FavoriteController;
use App\Domains\Client\Controllers\ProfileController as ClientProfileController;
use App\Domains\Review\Controllers\ClientReviewController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::domain(config('app.domain'))->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Base/Marketing Routes
    |--------------------------------------------------------------------------
    |
    | These routes are for the main domain (zeen.com).
    | Includes home page and client dashboard.
    |
    | Auth routes are in routes/auth.php.
    | Payment routes are in routes/payment.php.
    |
    */


    Route::get('/', function () {
        return Inertia::render('Home');
    })->name('home');






    /*
    |--------------------------------------------------------------------------
    | Client Dashboard Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth'])->prefix('dashboard')->name('client.')->group(function () {
        Route::get('/', ClientDashboardController::class)->name('dashboard');

        // Profile management
        Route::get('/profile', [ClientProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ClientProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ClientProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/profile/avatar', [ClientProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('/profile', [ClientProfileController::class, 'destroy'])->name('profile.destroy');

        // Booking management
        Route::get('/bookings', [ClientBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{uuid}', [ClientBookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{uuid}/cancel', [ClientBookingController::class, 'cancel'])->name('bookings.cancel');

        // Review management
        Route::get('/reviews', [ClientReviewController::class, 'index'])->name('reviews.index');
        Route::get('/bookings/{uuid}/review', [ClientReviewController::class, 'create'])->name('reviews.create');
        Route::post('/bookings/{uuid}/review', [ClientReviewController::class, 'store'])->name('reviews.store');

        // Favorites management
        Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
        Route::post('/favorites/{slug}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
        Route::delete('/favorites/{slug}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    });
});

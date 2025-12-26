<?php

use App\Domains\Admin\Controllers\Auth\AdminLoginController;
use App\Domains\Admin\Controllers\BookingController;
use App\Domains\Admin\Controllers\CategoryController;
use App\Domains\Admin\Controllers\DashboardController;
use App\Domains\Admin\Controllers\PaymentController;
use App\Domains\Admin\Controllers\PayoutController;
use App\Domains\Admin\Controllers\ProviderController;
use App\Domains\Admin\Controllers\ReviewController;
use App\Domains\Admin\Controllers\SettingsController;
use App\Domains\Admin\Controllers\UserController;
use App\Domains\Admin\Controllers\WaitlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes are for the admin domain (admin.zeen.com).
| Guest routes are available for login, protected routes require
| authentication and admin role.
|
*/

// Guest routes (login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/login', [AdminLoginController::class, 'store']);
});

// Protected routes (require auth + admin role)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');

    Route::get('/', DashboardController::class)->name('admin.dashboard');

    // User management
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{uuid}', [UserController::class, 'show'])->name('show');
        Route::post('/{uuid}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{uuid}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Provider management
    Route::prefix('providers')->name('admin.providers.')->group(function () {
        Route::get('/', [ProviderController::class, 'index'])->name('index');
        Route::get('/{uuid}', [ProviderController::class, 'show'])->name('show');
        Route::put('/{uuid}/status', [ProviderController::class, 'updateStatus'])->name('status');
        Route::post('/{uuid}/toggle-featured', [ProviderController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::put('/{uuid}/commission', [ProviderController::class, 'updateCommission'])->name('commission');
    });

    // Booking management
    Route::prefix('bookings')->name('admin.bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::get('/{uuid}', [BookingController::class, 'show'])->name('show');
        Route::put('/{uuid}/status', [BookingController::class, 'updateStatus'])->name('status');
    });

    // Payment management
    Route::prefix('payments')->name('admin.payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/{uuid}', [PaymentController::class, 'show'])->name('show');
    });

    // Payout management
    Route::prefix('payouts')->name('admin.payouts.')->group(function () {
        Route::get('/', [PayoutController::class, 'index'])->name('index');
        Route::get('/{uuid}', [PayoutController::class, 'show'])->name('show');
        Route::put('/{uuid}/status', [PayoutController::class, 'updateStatus'])->name('status');
    });

    // Review management
    Route::prefix('reviews')->name('admin.reviews.')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('/{uuid}', [ReviewController::class, 'show'])->name('show');
        Route::post('/{uuid}/toggle-visibility', [ReviewController::class, 'toggleVisibility'])->name('toggle-visibility');
        Route::post('/{uuid}/flag', [ReviewController::class, 'flag'])->name('flag');
        Route::post('/{uuid}/unflag', [ReviewController::class, 'unflag'])->name('unflag');
        Route::delete('/{uuid}', [ReviewController::class, 'destroy'])->name('destroy');
    });

    // Category management
    Route::prefix('categories')->name('admin.categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{uuid}', [CategoryController::class, 'update'])->name('update');
        Route::post('/{uuid}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{uuid}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    // System Settings management
    Route::prefix('settings')->name('admin.settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
    });

    // Waitlist management
    Route::prefix('waitlist')->name('admin.waitlist.')->group(function () {
        Route::get('/', [WaitlistController::class, 'index'])->name('index');
        Route::post('/{id}/invite', [WaitlistController::class, 'invite'])->name('invite');
        Route::delete('/{id}', [WaitlistController::class, 'destroy'])->name('destroy');
        Route::get('/export', [WaitlistController::class, 'export'])->name('export');
    });
});

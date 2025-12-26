<?php

use App\Domains\Booking\Controllers\ProviderBookingController;
use App\Domains\Payment\Controllers\ProviderEarningsController;
use App\Domains\Provider\Controllers\AvailabilityController;
use App\Domains\Provider\Controllers\DashboardController;
use App\Domains\Provider\Controllers\ProfileController;
use App\Domains\Provider\Controllers\ServiceController;
use App\Domains\Provider\Controllers\SettingsController;
use App\Domains\Provider\Controllers\TeamMemberController;
use App\Domains\Review\Controllers\ProviderReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Provider Console Routes
|--------------------------------------------------------------------------
|
| These routes are for the provider console domain (console.zeen.com).
| All routes here require authentication and provider role.
| Middleware is applied in bootstrap/app.php.
|
*/

Route::get('/', DashboardController::class)->name('provider.dashboard');

// Profile management
Route::prefix('profile')->name('provider.profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/', [ProfileController::class, 'update'])->name('update');
});

// Service management
Route::resource('services', ServiceController::class)->except(['show'])->names([
    'index' => 'provider.services.index',
    'create' => 'provider.services.create',
    'store' => 'provider.services.store',
    'edit' => 'provider.services.edit',
    'update' => 'provider.services.update',
    'destroy' => 'provider.services.destroy',
]);
Route::post('/services/{service}/toggle-active', [ServiceController::class, 'toggleActive'])->name('provider.services.toggle-active');

// Settings management
Route::prefix('settings')->name('provider.settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'edit'])->name('edit');
    Route::put('/booking', [SettingsController::class, 'updateBookingSettings'])->name('booking');
});

// Availability management
Route::prefix('availability')->name('provider.availability.')->group(function () {
    Route::get('/', [AvailabilityController::class, 'edit'])->name('edit');
    Route::put('/schedule', [AvailabilityController::class, 'updateSchedule'])->name('schedule');
    Route::put('/blocked-dates', [AvailabilityController::class, 'updateBlockedDates'])->name('blocked-dates');
});

// Booking management
Route::prefix('bookings')->name('provider.bookings.')->group(function () {
    Route::get('/', [ProviderBookingController::class, 'index'])->name('index');
    Route::get('/{uuid}', [ProviderBookingController::class, 'show'])->name('show');
    Route::put('/{uuid}/status', [ProviderBookingController::class, 'updateStatus'])->name('status');
    Route::post('/{uuid}/confirm', [ProviderBookingController::class, 'confirm'])->name('confirm');
    Route::post('/{uuid}/complete', [ProviderBookingController::class, 'complete'])->name('complete');
    Route::post('/{uuid}/cancel', [ProviderBookingController::class, 'cancel'])->name('cancel');
    Route::post('/{uuid}/no-show', [ProviderBookingController::class, 'noShow'])->name('no-show');
});

// Earnings/Payment management
Route::prefix('payments')->name('provider.payments.')->group(function () {
    Route::get('/', [ProviderEarningsController::class, 'index'])->name('index');
    Route::get('/history', [ProviderEarningsController::class, 'payments'])->name('history');
    Route::get('/payouts/{uuid}', [ProviderEarningsController::class, 'showPayout'])->name('payout');
});

// Review management
Route::prefix('reviews')->name('provider.reviews.')->group(function () {
    Route::get('/', [ProviderReviewController::class, 'index'])->name('index');
    Route::post('/{uuid}/respond', [ProviderReviewController::class, 'respond'])->name('respond');
});

// Team member management
Route::prefix('team')->name('provider.team.')->group(function () {
    Route::get('/', [TeamMemberController::class, 'index'])->name('index');
    Route::get('/invite', [TeamMemberController::class, 'create'])->name('create');
    Route::post('/', [TeamMemberController::class, 'store'])->name('store');
    Route::get('/{member}/edit', [TeamMemberController::class, 'edit'])->name('edit');
    Route::put('/{member}', [TeamMemberController::class, 'update'])->name('update');
    Route::delete('/{member}', [TeamMemberController::class, 'destroy'])->name('destroy');
    Route::post('/{member}/resend', [TeamMemberController::class, 'resendInvite'])->name('resend');
    Route::post('/{member}/suspend', [TeamMemberController::class, 'suspend'])->name('suspend');
    Route::post('/{member}/reactivate', [TeamMemberController::class, 'reactivate'])->name('reactivate');
});

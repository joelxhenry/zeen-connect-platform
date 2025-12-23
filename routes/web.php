<?php

use App\Domains\Auth\Controllers\LoginController;
use App\Domains\Auth\Controllers\RegisterController;
use App\Domains\Booking\Controllers\ClientBookingController;
use App\Domains\Booking\Controllers\ProviderBookingController;
use App\Domains\Client\Controllers\DashboardController as ClientDashboardController;
use App\Domains\Client\Controllers\FavoriteController;
use App\Domains\Client\Controllers\ProfileController as ClientProfileController;
use App\Domains\Payment\Controllers\PaymentController;
use App\Domains\Payment\Controllers\ProviderEarningsController;
use App\Domains\Payment\Controllers\WebhookController;
use App\Domains\Provider\Controllers\AvailabilityController;
use App\Domains\Provider\Controllers\DashboardController as ProviderDashboardController;
use App\Domains\Provider\Controllers\ProfileController;
use App\Domains\Provider\Controllers\ServiceController;
use App\Domains\Review\Controllers\ClientReviewController;
use App\Domains\Review\Controllers\ProviderReviewController;
use App\Http\Controllers\ProviderListingController;
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

// Provider Discovery
Route::get('/explore', [ProviderListingController::class, 'index'])->name('explore');
Route::get('/providers/{slug}', [ProviderListingController::class, 'show'])->name('provider.public');

// Become a Provider redirect
Route::get('/become-provider', function () {
    return redirect()->route('register.provider');
})->name('become-provider');

/*
|--------------------------------------------------------------------------
| Payment Webhooks (No Auth)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/powertranz', [WebhookController::class, 'handlePowerTranz'])
    ->name('webhooks.powertranz')
    ->withoutMiddleware(['web']);

/*
|--------------------------------------------------------------------------
| Guest Routes (Authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Unified login
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

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

    // Booking creation (accessible to logged-in clients)
    Route::get('/book', [ClientBookingController::class, 'create'])->name('booking.create');
    Route::get('/book/slots', [ClientBookingController::class, 'getSlots'])->name('booking.slots');
    Route::post('/book', [ClientBookingController::class, 'store'])->name('booking.store');

    // Payment routes
    Route::get('/pay/{bookingUuid}', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::post('/pay/{bookingUuid}', [PaymentController::class, 'process'])->name('payment.process');
    Route::get('/payment/{paymentUuid}/callback', [PaymentController::class, 'callback'])->name('payment.callback');
    Route::get('/payment/{paymentUuid}/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/{paymentUuid}/failed', [PaymentController::class, 'failed'])->name('payment.failed');
    Route::get('/payment/{paymentUuid}/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
});

/*
|--------------------------------------------------------------------------
| Client Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:client'])->prefix('dashboard')->name('client.')->group(function () {
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

/*
|--------------------------------------------------------------------------
| Provider Console Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:provider'])->prefix('console')->name('provider.')->group(function () {
    Route::get('/', ProviderDashboardController::class)->name('dashboard');

    // Profile management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Service management
    Route::resource('services', ServiceController::class)->except(['show']);

    // Availability management
    Route::get('/availability', [AvailabilityController::class, 'edit'])->name('availability.edit');
    Route::put('/availability/schedule', [AvailabilityController::class, 'updateSchedule'])->name('availability.schedule');
    Route::put('/availability/blocked-dates', [AvailabilityController::class, 'updateBlockedDates'])->name('availability.blocked-dates');

    // Booking management
    Route::get('/bookings', [ProviderBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{uuid}', [ProviderBookingController::class, 'show'])->name('bookings.show');
    Route::put('/bookings/{uuid}/status', [ProviderBookingController::class, 'updateStatus'])->name('bookings.status');
    Route::post('/bookings/{uuid}/confirm', [ProviderBookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('/bookings/{uuid}/complete', [ProviderBookingController::class, 'complete'])->name('bookings.complete');

    // Earnings/Payment management
    Route::get('/payments', [ProviderEarningsController::class, 'index'])->name('payments.index');
    Route::get('/payments/history', [ProviderEarningsController::class, 'payments'])->name('payments.history');
    Route::get('/payments/payouts/{uuid}', [ProviderEarningsController::class, 'showPayout'])->name('payments.payout');

    // Review management
    Route::get('/reviews', [ProviderReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{uuid}/respond', [ProviderReviewController::class, 'respond'])->name('reviews.respond');

    // Additional provider routes will be added in future phases
    // Route::resource('portfolios', PortfolioController::class);
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', \App\Domains\Admin\Controllers\DashboardController::class)->name('dashboard');

    // User management
    Route::get('/users', [\App\Domains\Admin\Controllers\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{uuid}', [\App\Domains\Admin\Controllers\UserController::class, 'show'])->name('users.show');
    Route::post('/users/{uuid}/toggle-status', [\App\Domains\Admin\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/users/{uuid}', [\App\Domains\Admin\Controllers\UserController::class, 'destroy'])->name('users.destroy');

    // Provider management
    Route::get('/providers', [\App\Domains\Admin\Controllers\ProviderController::class, 'index'])->name('providers.index');
    Route::get('/providers/{uuid}', [\App\Domains\Admin\Controllers\ProviderController::class, 'show'])->name('providers.show');
    Route::put('/providers/{uuid}/status', [\App\Domains\Admin\Controllers\ProviderController::class, 'updateStatus'])->name('providers.status');
    Route::post('/providers/{uuid}/toggle-featured', [\App\Domains\Admin\Controllers\ProviderController::class, 'toggleFeatured'])->name('providers.toggle-featured');
    Route::put('/providers/{uuid}/commission', [\App\Domains\Admin\Controllers\ProviderController::class, 'updateCommission'])->name('providers.commission');

    // Booking management
    Route::get('/bookings', [\App\Domains\Admin\Controllers\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{uuid}', [\App\Domains\Admin\Controllers\BookingController::class, 'show'])->name('bookings.show');
    Route::put('/bookings/{uuid}/status', [\App\Domains\Admin\Controllers\BookingController::class, 'updateStatus'])->name('bookings.status');

    // Payment management
    Route::get('/payments', [\App\Domains\Admin\Controllers\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{uuid}', [\App\Domains\Admin\Controllers\PaymentController::class, 'show'])->name('payments.show');

    // Payout management
    Route::get('/payouts', [\App\Domains\Admin\Controllers\PayoutController::class, 'index'])->name('payouts.index');
    Route::get('/payouts/{uuid}', [\App\Domains\Admin\Controllers\PayoutController::class, 'show'])->name('payouts.show');
    Route::put('/payouts/{uuid}/status', [\App\Domains\Admin\Controllers\PayoutController::class, 'updateStatus'])->name('payouts.status');

    // Review management
    Route::get('/reviews', [\App\Domains\Admin\Controllers\ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/{uuid}', [\App\Domains\Admin\Controllers\ReviewController::class, 'show'])->name('reviews.show');
    Route::post('/reviews/{uuid}/toggle-visibility', [\App\Domains\Admin\Controllers\ReviewController::class, 'toggleVisibility'])->name('reviews.toggle-visibility');
    Route::post('/reviews/{uuid}/flag', [\App\Domains\Admin\Controllers\ReviewController::class, 'flag'])->name('reviews.flag');
    Route::post('/reviews/{uuid}/unflag', [\App\Domains\Admin\Controllers\ReviewController::class, 'unflag'])->name('reviews.unflag');
    Route::delete('/reviews/{uuid}', [\App\Domains\Admin\Controllers\ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Category management
    Route::get('/categories', [\App\Domains\Admin\Controllers\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\App\Domains\Admin\Controllers\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{uuid}', [\App\Domains\Admin\Controllers\CategoryController::class, 'update'])->name('categories.update');
    Route::post('/categories/{uuid}/toggle-status', [\App\Domains\Admin\Controllers\CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
    Route::delete('/categories/{uuid}', [\App\Domains\Admin\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');

    // Location management
    Route::get('/locations', [\App\Domains\Admin\Controllers\LocationController::class, 'index'])->name('locations.index');
    Route::post('/locations', [\App\Domains\Admin\Controllers\LocationController::class, 'store'])->name('locations.store');
    Route::put('/locations/{uuid}', [\App\Domains\Admin\Controllers\LocationController::class, 'update'])->name('locations.update');
    Route::post('/locations/{uuid}/toggle-status', [\App\Domains\Admin\Controllers\LocationController::class, 'toggleStatus'])->name('locations.toggle-status');
    Route::delete('/locations/{uuid}', [\App\Domains\Admin\Controllers\LocationController::class, 'destroy'])->name('locations.destroy');
});

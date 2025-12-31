<?php

use App\Domains\Booking\Controllers\ProviderBookingController;
use App\Domains\Media\Controllers\MediaController;
use App\Domains\Media\Controllers\VideoEmbedController;
use App\Domains\Payment\Controllers\ProviderEarningsController;
use App\Domains\Payment\Controllers\ProviderGatewaySetupController;
use App\Domains\Payment\Controllers\ProviderRefundController;
use App\Domains\Provider\Controllers\AvailabilityController;
use App\Domains\Provider\Controllers\BankingInfoController;
use App\Domains\Provider\Controllers\BrandingController;
use App\Domains\Provider\Controllers\CategoryController;
use App\Domains\Provider\Controllers\DashboardController;
use App\Domains\Provider\Controllers\ProfileController;
use App\Domains\Provider\Controllers\ServiceController;
use App\Domains\Provider\Controllers\SettingsController;
use App\Domains\Provider\Controllers\SiteTemplateController;
use App\Domains\Provider\Controllers\TeamMemberAvailabilityController;
use App\Domains\Provider\Controllers\TeamMemberController;
use App\Domains\Review\Controllers\ProviderReviewController;
use App\Domains\Subscription\Controllers\SubscriptionBillingController;
use App\Domains\Subscription\Controllers\SubscriptionController;
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

// Category management
Route::prefix('categories')->name('provider.categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/list', [CategoryController::class, 'list'])->name('list');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');
});

// Settings management
Route::prefix('settings')->name('provider.settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'edit'])->name('edit');
    Route::put('/booking', [SettingsController::class, 'updateBookingSettings'])->name('booking');
});

// Branding management
Route::prefix('branding')->name('provider.branding.')->group(function () {
    Route::get('/', [BrandingController::class, 'edit'])->name('edit');
    Route::put('/', [BrandingController::class, 'update'])->name('update');
});

// Site template management
Route::prefix('site/template')->name('provider.site.template.')->group(function () {
    Route::get('/', [SiteTemplateController::class, 'edit'])->name('edit');
    Route::put('/', [SiteTemplateController::class, 'update'])->name('update');
});

// Availability management
Route::prefix('availability')->name('provider.availability.')->group(function () {
    Route::get('/', [AvailabilityController::class, 'edit'])->name('edit');
    Route::put('/schedule', [AvailabilityController::class, 'updateSchedule'])->name('schedule');
    Route::put('/blocked-dates', [AvailabilityController::class, 'updateBlockedDates'])->name('blocked-dates');
    Route::put('/breaks', [AvailabilityController::class, 'updateBreaks'])->name('breaks');
    Route::put('/buffer', [AvailabilityController::class, 'updateBuffer'])->name('buffer');
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
    // Earnings dashboard
    Route::get('/', [ProviderEarningsController::class, 'index'])->name('index');
    Route::get('/history', [ProviderEarningsController::class, 'payments'])->name('history');
    Route::get('/payouts/{uuid}', [ProviderEarningsController::class, 'showPayout'])->name('payout');

    // Wallet & Payouts
    Route::get('/wallet', [ProviderEarningsController::class, 'wallet'])->name('wallet');
    Route::post('/payout/request', [ProviderEarningsController::class, 'requestPayout'])->name('payout.request');
    Route::put('/payout/schedule', [ProviderEarningsController::class, 'updatePayoutSchedule'])->name('payout.schedule');
    Route::post('/payout/{uuid}/cancel', [ProviderEarningsController::class, 'cancelPayout'])->name('payout.cancel');

    // Gateway Setup (WiPay)
    Route::prefix('setup')->name('setup.')->group(function () {
        Route::get('/', [ProviderGatewaySetupController::class, 'index'])->name('index');
        Route::get('/{gateway}', [ProviderGatewaySetupController::class, 'create'])->name('create');
        Route::get('/{gateway}/edit', [ProviderGatewaySetupController::class, 'edit'])->name('edit');
        Route::post('/{gateway}', [ProviderGatewaySetupController::class, 'store'])->name('store');
        Route::put('/{gateway}', [ProviderGatewaySetupController::class, 'update'])->name('update');
        Route::delete('/{gateway}', [ProviderGatewaySetupController::class, 'destroy'])->name('destroy');
        Route::post('/{gateway}/verify', [ProviderGatewaySetupController::class, 'verify'])->name('verify');
        Route::post('/{gateway}/primary', [ProviderGatewaySetupController::class, 'makePrimary'])->name('primary');
    });

    // Banking Info (for escrow payouts)
    Route::prefix('banking-info')->name('banking-info.')->group(function () {
        Route::get('/', [BankingInfoController::class, 'edit'])->name('edit');
        Route::put('/', [BankingInfoController::class, 'update'])->name('update');
        Route::delete('/', [BankingInfoController::class, 'destroy'])->name('destroy');
    });

    // Refunds
    Route::get('/refunds', [ProviderRefundController::class, 'index'])->name('refunds');
    Route::post('/{uuid}/refund', [ProviderRefundController::class, 'store'])->name('refund');

    // Payment detail
    Route::get('/show/{uuid}', [ProviderEarningsController::class, 'show'])->name('show');
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

    // Team member availability
    Route::get('/{member}/availability', [TeamMemberAvailabilityController::class, 'edit'])->name('availability.edit');
    Route::put('/{member}/availability/schedule', [TeamMemberAvailabilityController::class, 'updateSchedule'])->name('availability.schedule');
    Route::put('/{member}/availability/breaks', [TeamMemberAvailabilityController::class, 'updateBreaks'])->name('availability.breaks');
    Route::put('/{member}/availability/blocked-dates', [TeamMemberAvailabilityController::class, 'updateBlockedDates'])->name('availability.blocked-dates');
    Route::post('/{member}/availability/reset', [TeamMemberAvailabilityController::class, 'resetToDefaults'])->name('availability.reset');
});

// Subscription management
Route::prefix('subscription')->name('provider.subscription.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');

    // Upgrade flow
    Route::get('/upgrade', [SubscriptionBillingController::class, 'upgrade'])->name('upgrade');
    Route::post('/upgrade', [SubscriptionBillingController::class, 'processUpgrade'])->name('upgrade.process');
    Route::get('/upgrade/callback', [SubscriptionBillingController::class, 'upgradeCallback'])->name('upgrade.callback');

    // Trial
    Route::post('/trial/start', [SubscriptionBillingController::class, 'startTrial'])->name('trial.start');

    // Cancel & Reactivate
    Route::post('/cancel', [SubscriptionBillingController::class, 'cancel'])->name('cancel');
    Route::post('/reactivate', [SubscriptionBillingController::class, 'reactivate'])->name('reactivate');

    // Billing history
    Route::get('/billing', [SubscriptionBillingController::class, 'invoices'])->name('billing');
    Route::get('/billing/{invoice}/download', [SubscriptionBillingController::class, 'downloadInvoice'])->name('billing.download');

    // Payment method
    Route::get('/payment-method', [SubscriptionBillingController::class, 'editPaymentMethod'])->name('payment-method.edit');
    Route::post('/payment-method', [SubscriptionBillingController::class, 'updatePaymentMethod'])->name('payment-method.update');
    Route::get('/payment-method/callback', [SubscriptionBillingController::class, 'paymentMethodCallback'])->name('payment-method.callback');
});

// Media management
Route::prefix('media')->name('provider.media.')->group(function () {
    // Provider media (avatar, cover, gallery)
    Route::post('/upload', [MediaController::class, 'uploadProviderMedia'])->name('upload');
    Route::post('/upload-multiple', [MediaController::class, 'uploadProviderMediaMultiple'])->name('upload-multiple');

    // Service media
    Route::post('/services/{service}', [MediaController::class, 'uploadSingleServiceMedia'])->name('service.upload');
    Route::post('/services/{service}/multiple', [MediaController::class, 'uploadServiceMedia'])->name('service.upload-multiple');

    // Generic operations
    Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
    Route::post('/reorder', [MediaController::class, 'reorder'])->name('reorder');
    Route::get('/remaining-slots', [MediaController::class, 'remainingSlots'])->name('remaining-slots');
});

// Video embed management
Route::prefix('videos')->name('provider.videos.')->group(function () {
    // Provider videos
    Route::post('/provider', [VideoEmbedController::class, 'addProviderVideo'])->name('provider.add');

    // Service videos
    Route::post('/services/{service}', [VideoEmbedController::class, 'addServiceVideo'])->name('service.add');

    // Generic operations
    Route::put('/{video}', [VideoEmbedController::class, 'update'])->name('update');
    Route::delete('/{video}', [VideoEmbedController::class, 'destroy'])->name('destroy');
    Route::post('/reorder', [VideoEmbedController::class, 'reorder'])->name('reorder');
    Route::post('/parse-url', [VideoEmbedController::class, 'parseUrl'])->name('parse-url');
});

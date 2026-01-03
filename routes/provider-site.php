<?php

use App\Http\Controllers\ProviderSite\ProviderSiteController;
use App\Http\Controllers\ProviderSite\ProviderSiteBookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Provider Site Routes
|--------------------------------------------------------------------------
|
| These routes handle provider subdomains ({slug}.zeen.com).
| Each provider gets their own mini-site with profile, services, reviews,
| and booking functionality.
|
*/

// Provider site pages
Route::get('/', [ProviderSiteController::class, 'home'])->name('providersite.home');
Route::get('/services', [ProviderSiteController::class, 'services'])->name('providersite.services');
Route::get('/events', [ProviderSiteController::class, 'events'])->name('providersite.events');
Route::get('/events/{slug}', [ProviderSiteController::class, 'showEvent'])->name('providersite.events.show');
Route::get('/reviews', [ProviderSiteController::class, 'reviews'])->name('providersite.reviews');

// Booking routes (service bookings)
Route::get('/book', [ProviderSiteBookingController::class, 'create'])->name('providersite.book');
Route::get('/book/slots', [ProviderSiteBookingController::class, 'getSlots'])->name('providersite.book.slots');
Route::post('/book', [ProviderSiteBookingController::class, 'store'])->name('providersite.book.store');
Route::get('/book/{uuid}/confirmation', [ProviderSiteBookingController::class, 'confirmation'])->name('providersite.book.confirmation');

// Event booking routes
Route::post('/book/event', [ProviderSiteBookingController::class, 'storeEventBooking'])->name('providersite.book.event.store');
Route::get('/book/event/{uuid}/confirmation', [ProviderSiteBookingController::class, 'eventConfirmation'])->name('providersite.book.event-confirmation');

// Guest booking management
Route::post('/book/{uuid}/cancel', [ProviderSiteBookingController::class, 'cancelGuest'])->name('providersite.book.cancel');

// Checkout
Route::get('/checkout/{uuid}', [ProviderSiteBookingController::class, 'checkout'])->name('providersite.checkout');

// Authenticated user routes
Route::middleware('auth')->group(function () {
    Route::get('/my-bookings', [ProviderSiteBookingController::class, 'myBookings'])->name('providersite.my-bookings');
});

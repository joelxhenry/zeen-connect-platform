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
Route::get('/reviews', [ProviderSiteController::class, 'reviews'])->name('providersite.reviews');

// Booking routes
Route::get('/book', [ProviderSiteBookingController::class, 'create'])->name('providersite.book');
Route::get('/book/slots', [ProviderSiteBookingController::class, 'getSlots'])->name('providersite.book.slots');
Route::post('/book', [ProviderSiteBookingController::class, 'store'])->name('providersite.book.store');
Route::get('/book/{uuid}/confirmation', [ProviderSiteBookingController::class, 'confirmation'])->name('providersite.book.confirmation');

// Guest booking management
Route::post('/book/{uuid}/cancel', [ProviderSiteBookingController::class, 'cancelGuest'])->name('providersite.book.cancel');

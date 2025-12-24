<?php

use App\Domains\Payment\Controllers\PaymentController;
use App\Domains\Payment\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
|
| These routes are for the payments domain (payments.zeen.com).
| They handle payment processing and don't require authentication.
|
*/

// Payment flow routes
Route::get('/{bookingUuid}/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
Route::post('/{bookingUuid}/process', [PaymentController::class, 'process'])->name('payment.process');
Route::get('/{paymentUuid}/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/{paymentUuid}/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/{paymentUuid}/failed', [PaymentController::class, 'failed'])->name('payment.failed');
Route::get('/{paymentUuid}/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');

/*
|--------------------------------------------------------------------------
| Payment Webhooks
|--------------------------------------------------------------------------
|
| Webhook endpoints for payment providers.
| These routes don't use web middleware.
|
*/

Route::post('/webhooks/powertranz', [WebhookController::class, 'handlePowerTranz'])
    ->name('webhooks.powertranz')
    ->withoutMiddleware(['web']);

<?php

namespace App\Domains\Payment\Controllers;

use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Actions\CreatePaymentAction;
use App\Domains\Payment\Actions\ProcessPaymentAction;
use App\Domains\Payment\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Show the checkout page for a booking.
     */
    public function checkout(Request $request, string $bookingUuid): Response
    {
        $booking = Booking::where('uuid', $bookingUuid)
            ->where('client_id', $request->user()->id)
            ->with(['provider:id,business_name,slug', 'service:id,name,duration_minutes,price'])
            ->firstOrFail();

        // Check if booking is in a payable state
        if (! $booking->status->value === 'pending') {
            return redirect()->route('client.bookings.show', $booking->uuid)
                ->with('error', 'This booking cannot be paid for.');
        }

        // Check if payment already exists
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->whereIn('status', ['completed', 'processing'])
            ->first();

        if ($existingPayment) {
            return redirect()->route('client.bookings.show', $booking->uuid)
                ->with('info', 'Payment has already been processed for this booking.');
        }

        // Calculate fees
        $platformFeeRate = 0.15;
        $platformFee = round($booking->total_amount * $platformFeeRate, 2);
        $providerAmount = $booking->total_amount - $platformFee;

        return Inertia::render('Payment/Checkout', [
            'booking' => [
                'uuid' => $booking->uuid,
                'provider' => [
                    'name' => $booking->provider->business_name,
                    'slug' => $booking->provider->slug,
                ],
                'service' => [
                    'name' => $booking->service->name,
                    'duration_minutes' => $booking->service->duration_minutes,
                ],
                'formatted_date' => $booking->formatted_date,
                'formatted_time' => $booking->formatted_time,
                'service_price' => $booking->service_price,
                'platform_fee' => $platformFee,
                'total_amount' => $booking->total_amount,
                'total_display' => $booking->total_display,
            ],
        ]);
    }

    /**
     * Initialize payment processing.
     */
    public function process(
        Request $request,
        string $bookingUuid,
        CreatePaymentAction $createPayment,
        ProcessPaymentAction $processPayment
    ): RedirectResponse {
        $booking = Booking::where('uuid', $bookingUuid)
            ->where('client_id', $request->user()->id)
            ->firstOrFail();

        // Create payment record
        $payment = $createPayment->execute($booking);

        // Initialize payment with gateway
        $returnUrl = route('payment.callback', ['payment' => $payment->uuid]);
        $cancelUrl = route('payment.cancel', ['payment' => $payment->uuid]);

        $result = $processPayment->initialize($payment, $returnUrl, $cancelUrl);

        if ($result['success'] && isset($result['redirect_url'])) {
            // Store SPI token in session for verification
            session(['payment_spi_token_' . $payment->uuid => $result['spi_token']]);

            return redirect()->away($result['redirect_url']);
        }

        return back()->withErrors(['payment' => $result['error'] ?? 'Payment initialization failed']);
    }

    /**
     * Handle payment gateway callback.
     */
    public function callback(Request $request, string $paymentUuid, ProcessPaymentAction $processPayment): RedirectResponse
    {
        $payment = Payment::where('uuid', $paymentUuid)->firstOrFail();

        // Verify the user owns this payment
        if ($payment->client_id !== $request->user()?->id) {
            abort(403);
        }

        // Get SPI token from request or session
        $spiToken = $request->input('SpiToken') ?? session('payment_spi_token_' . $payment->uuid);

        if (! $spiToken) {
            return redirect()->route('payment.failed', ['payment' => $payment->uuid])
                ->with('error', 'Payment verification failed. Missing token.');
        }

        $result = $processPayment->complete($payment, $spiToken);

        // Clear session token
        session()->forget('payment_spi_token_' . $payment->uuid);

        if ($result['success']) {
            return redirect()->route('payment.success', ['payment' => $payment->uuid]);
        }

        return redirect()->route('payment.failed', ['payment' => $payment->uuid])
            ->with('error', $result['error']);
    }

    /**
     * Show payment success page.
     */
    public function success(Request $request, string $paymentUuid): Response
    {
        $payment = Payment::where('uuid', $paymentUuid)
            ->with([
                'booking:id,uuid,booking_date,start_time,end_time',
                'booking.provider:id,business_name,slug',
                'booking.service:id,name',
            ])
            ->firstOrFail();

        // Verify the user owns this payment
        if ($payment->client_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('Payment/Success', [
            'payment' => [
                'uuid' => $payment->uuid,
                'amount_display' => $payment->amount_display,
                'card_display' => $payment->card_display,
                'paid_at' => $payment->paid_at?->format('M j, Y g:i A'),
            ],
            'booking' => [
                'uuid' => $payment->booking->uuid,
                'provider_name' => $payment->booking->provider->business_name,
                'service_name' => $payment->booking->service->name,
                'formatted_date' => $payment->booking->formatted_date,
                'formatted_time' => $payment->booking->formatted_time,
            ],
        ]);
    }

    /**
     * Show payment failed page.
     */
    public function failed(Request $request, string $paymentUuid): Response
    {
        $payment = Payment::where('uuid', $paymentUuid)
            ->with(['booking:id,uuid'])
            ->firstOrFail();

        // Verify the user owns this payment
        if ($payment->client_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('Payment/Failed', [
            'payment' => [
                'uuid' => $payment->uuid,
                'amount_display' => $payment->amount_display,
                'failure_reason' => $payment->failure_reason,
            ],
            'booking_uuid' => $payment->booking->uuid,
            'error' => session('error'),
        ]);
    }

    /**
     * Handle payment cancellation.
     */
    public function cancel(Request $request, string $paymentUuid): RedirectResponse
    {
        $payment = Payment::where('uuid', $paymentUuid)->firstOrFail();

        // Verify the user owns this payment
        if ($payment->client_id !== $request->user()->id) {
            abort(403);
        }

        // Mark as failed if still pending
        if ($payment->isPending()) {
            $payment->markAsFailed('Payment cancelled by user');
        }

        // Clear session token
        session()->forget('payment_spi_token_' . $payment->uuid);

        return redirect()->route('client.bookings.show', $payment->booking->uuid)
            ->with('info', 'Payment was cancelled.');
    }
}

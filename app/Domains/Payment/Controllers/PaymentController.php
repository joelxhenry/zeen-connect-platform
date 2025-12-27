<?php

namespace App\Domains\Payment\Controllers;

use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Actions\CreatePaymentAction;
use App\Domains\Payment\Actions\ProcessPaymentAction;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentManager;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected PaymentManager $paymentManager
    ) {}

    /**
     * Show the checkout page for a booking.
     */
    public function checkout(Request $request, string $bookingUuid): Response|RedirectResponse
    {
        $booking = $this->findBookingForPayment($request, $bookingUuid);

        // Check if booking is in a payable state
        if ($booking->status->value !== 'pending' && $booking->status->value !== 'confirmed') {
            return $this->redirectToBooking($booking, $request)
                ->with('error', 'This booking cannot be paid for.');
        }

        // Check if deposit payment already exists
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->whereIn('status', ['completed', 'processing'])
            ->first();

        if ($existingPayment && $booking->isDepositPaid()) {
            return $this->redirectToBooking($booking, $request)
                ->with('info', 'Deposit has already been paid for this booking.');
        }

        // Determine payment type based on deposit status
        $paymentType = $booking->requiresDeposit() && ! $booking->isDepositPaid()
            ? 'deposit'
            : 'full';

        // Calculate payment amount using SubscriptionService
        $paymentInfo = $this->subscriptionService->calculatePaymentAmount(
            $booking->provider,
            (float) $booking->service_price,
            $paymentType
        );

        // Determine gateway type for provider
        $gatewayType = $this->paymentManager->determineGatewayType($booking->provider);

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
                'service_price' => (float) $booking->service_price,
                'total_amount' => (float) $booking->total_amount,
                'total_display' => $booking->total_display,
                'is_guest' => $booking->isGuestBooking(),
                'client_name' => $booking->getClientName(),
                'client_email' => $booking->client_email,
            ],
            'payment' => [
                'type' => $paymentType,
                'amount' => $paymentInfo['amount'],
                'client_processing_fee' => $paymentInfo['client_processing_fee'],
                'total_to_charge' => $paymentInfo['total_to_charge'],
                'deposit_percentage' => $paymentInfo['deposit_percentage'],
                'platform_fee' => $paymentInfo['platform_fee'],
                'platform_fee_rate' => $paymentInfo['platform_fee_rate'],
                'processing_fee' => $paymentInfo['processing_fee'],
                'processing_fee_payer' => $paymentInfo['processing_fee_payer'],
                'tier' => $paymentInfo['tier'],
                'tier_label' => $paymentInfo['tier_label'],
                'gateway_type' => $gatewayType->value,
            ],
            'isAuthenticated' => (bool) $request->user(),
        ]);
    }

    /**
     * Find booking for payment, supporting both authenticated and guest users.
     */
    protected function findBookingForPayment(Request $request, string $bookingUuid): Booking
    {
        $query = Booking::where('uuid', $bookingUuid)
            ->with([
                'provider:id,business_name,slug,processing_fee_payer',
                'provider.subscription',
                'service:id,name,duration_minutes,price',
            ]);

        // For authenticated users, verify ownership
        if ($request->user()) {
            $query->where('client_id', $request->user()->id);
        }

        return $query->firstOrFail();
    }

    /**
     * Redirect to appropriate booking page based on user type.
     */
    protected function redirectToBooking(Booking $booking, Request $request): RedirectResponse
    {
        if ($booking->isGuestBooking() || ! $request->user()) {
            return redirect()->route('booking.confirmation', $booking->uuid);
        }

        return redirect()->route('client.bookings.show', $booking->uuid);
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
        $request->validate([
            'payment_type' => 'sometimes|in:full,deposit,balance',
        ]);

        $booking = $this->findBookingForPayment($request, $bookingUuid);
        $paymentType = $request->input('payment_type', 'deposit');

        // Calculate payment amount
        $paymentInfo = $this->subscriptionService->calculatePaymentAmount(
            $booking->provider,
            (float) $booking->service_price,
            $paymentType
        );

        // Determine gateway type and provider
        $gatewayType = $this->paymentManager->determineGatewayType($booking->provider);
        $gateway = $this->paymentManager->resolveGateway($booking->provider);

        // Create payment record with tier-based amounts and gateway info
        $payment = $createPayment->execute(
            booking: $booking,
            amount: $paymentInfo['total_to_charge'],
            paymentType: $paymentType,
            platformFee: $paymentInfo['platform_fee'],
            providerAmount: $paymentInfo['provider_payout'],
            processingFee: $paymentInfo['processing_fee'],
            processingFeePayer: $paymentInfo['processing_fee_payer'],
            gatewayType: $gatewayType->value,
            gatewayProvider: $gateway->getProvider()
        );

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
        $payment = Payment::where('uuid', $paymentUuid)
            ->with('booking')
            ->firstOrFail();

        // Verify the user owns this payment (skip for guest bookings)
        if (! $payment->booking->isGuestBooking() && $payment->client_id !== $request->user()?->id) {
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
                'booking:id,uuid,booking_date,start_time,end_time,client_id,guest_name,guest_email',
                'booking.provider:id,business_name,slug',
                'booking.service:id,name',
            ])
            ->firstOrFail();

        // Verify ownership (skip for guest bookings)
        if (! $payment->booking->isGuestBooking() && $payment->client_id !== $request->user()?->id) {
            abort(403);
        }

        return Inertia::render('Payment/Success', [
            'payment' => [
                'uuid' => $payment->uuid,
                'amount_display' => $payment->amount_display,
                'card_display' => $payment->card_display,
                'paid_at' => $payment->paid_at?->format('M j, Y g:i A'),
                'payment_type' => $payment->payment_type ?? 'full',
            ],
            'booking' => [
                'uuid' => $payment->booking->uuid,
                'provider_name' => $payment->booking->provider->business_name,
                'service_name' => $payment->booking->service->name,
                'formatted_date' => $payment->booking->formatted_date,
                'formatted_time' => $payment->booking->formatted_time,
                'is_guest' => $payment->booking->isGuestBooking(),
            ],
            'isAuthenticated' => (bool) $request->user(),
        ]);
    }

    /**
     * Show payment failed page.
     */
    public function failed(Request $request, string $paymentUuid): Response
    {
        $payment = Payment::where('uuid', $paymentUuid)
            ->with(['booking:id,uuid,client_id'])
            ->firstOrFail();

        // Verify ownership (skip for guest bookings)
        if (! $payment->booking->isGuestBooking() && $payment->client_id !== $request->user()?->id) {
            abort(403);
        }

        return Inertia::render('Payment/Failed', [
            'payment' => [
                'uuid' => $payment->uuid,
                'amount_display' => $payment->amount_display,
                'failure_reason' => $payment->failure_reason,
            ],
            'booking_uuid' => $payment->booking->uuid,
            'is_guest' => $payment->booking->isGuestBooking(),
            'error' => session('error'),
            'isAuthenticated' => (bool) $request->user(),
        ]);
    }

    /**
     * Handle payment cancellation.
     */
    public function cancel(Request $request, string $paymentUuid): RedirectResponse
    {
        $payment = Payment::where('uuid', $paymentUuid)
            ->with('booking')
            ->firstOrFail();

        // Verify ownership (skip for guest bookings)
        if (! $payment->booking->isGuestBooking() && $payment->client_id !== $request->user()?->id) {
            abort(403);
        }

        // Mark as failed if still pending
        if ($payment->isPending()) {
            $payment->markAsFailed('Payment cancelled by user');
        }

        // Clear session token
        session()->forget('payment_spi_token_' . $payment->uuid);

        return $this->redirectToBooking($payment->booking, $request)
            ->with('info', 'Payment was cancelled.');
    }
}

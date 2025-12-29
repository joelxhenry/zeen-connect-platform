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
use Illuminate\Support\Arr;
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
                'amount' => Arr::get($paymentInfo, 'amount', 0),
                'zeen_fee' => Arr::get($paymentInfo, 'zeen_fee', 0),
                'gateway_fee' => Arr::get($paymentInfo, 'gateway_fee', 0),
                'total_fees' => Arr::get($paymentInfo, 'total_fees', 0),
                'convenience_fee' => Arr::get($paymentInfo, 'convenience_fee', 0),
                'total_to_charge' => Arr::get($paymentInfo, 'total_to_charge', 0),
                'amount_to_gateway' => Arr::get($paymentInfo, 'amount_to_gateway', 0),
                'deposit_percentage' => Arr::get($paymentInfo, 'deposit_percentage', 0),
                'fee_payer' => Arr::get($paymentInfo, 'fee_payer', 'provider'),
                'tier' => Arr::get($paymentInfo, 'tier', 'starter'),
                'tier_label' => Arr::get($paymentInfo, 'tier_label', 'Starter'),
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
                'provider:id,business_name,slug,fee_payer',
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
    ) {
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

        // Check for existing pending payment to reuse
        $payment = Payment::where('booking_id', $booking->id)
            ->where('payment_type', $paymentType)
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            // Update existing pending payment with current amounts
            // amount_to_gateway excludes processing fee - WiPay handles their own fee
            $payment->update([
                'amount' => Arr::get($paymentInfo, 'amount_to_gateway', 0),
                'platform_fee' => Arr::get($paymentInfo, 'total_fees', 0),
                'provider_amount' => Arr::get($paymentInfo, 'provider_receives', 0),
                'processing_fee' => Arr::get($paymentInfo, 'gateway_fee', 0),
                'processing_fee_payer' => Arr::get($paymentInfo, 'fee_payer', 'provider'),
                'gateway_type' => $gatewayType->value,
                'gateway_provider' => $gateway->getProvider(),
            ]);
        } else {
            // Create new payment record
            // amount_to_gateway excludes processing fee - WiPay handles their own fee
            $payment = $createPayment->execute(
                booking: $booking,
                amount: Arr::get($paymentInfo, 'amount_to_gateway', 0),
                paymentType: $paymentType,
                platformFee: Arr::get($paymentInfo, 'total_fees', 0),
                providerAmount: Arr::get($paymentInfo, 'provider_receives', 0),
                processingFee: Arr::get($paymentInfo, 'gateway_fee', 0),
                processingFeePayer: Arr::get($paymentInfo, 'fee_payer', 'provider'),
                gatewayType: $gatewayType->value,
                gatewayProvider: $gateway->getProvider()
            );
        }

        // Initialize payment with gateway
        $returnUrl = route('payment.callback', ['paymentUuid' => $payment->uuid]);
        $cancelUrl = route('payment.cancel', ['paymentUuid' => $payment->uuid]);

        $result = $processPayment->initialize($payment, $returnUrl, $cancelUrl);

        if ($result['success'] && isset($result['redirect_url'])) {
            // Store SPI token in session for verification
            if (isset($result['spi_token'])) {
                session(['payment_spi_token_' . $payment->uuid => $result['spi_token']]);
            }

            return redirect()->away($result['redirect_url']);
        }

        return back()->withErrors(['payment' => $result['error'] ?? 'Payment initialization failed']);
    }

    /**
     * Handle payment gateway callback.
     * WiPay returns: status, transaction_id, order_id, data (our custom JSON), message (on failure)
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

        // Extract WiPay callback data from query params
        $callbackData = [
            'status' => $request->query('status'),
            'transaction_id' => $request->query('transaction_id'),
            'order_id' => $request->query('order_id'),
            'message' => $request->query('message'),
            'data' => $request->query('data'),
        ];

        // Clear any stored session data
        session()->forget('payment_spi_token_' . $payment->uuid);

        // Check if WiPay returned a failed status directly
        $status = strtolower($callbackData['status'] ?? '');
        if ($status !== 'success') {
            // Mark payment as failed if not already
            if ($payment->isPending()) {
                $payment->markAsFailed($callbackData['message'] ?? 'Payment was not completed');
            }

            return redirect()->route('payment.failed', ['paymentUuid' => $payment->uuid])
                ->with('error', $callbackData['message'] ?? 'Payment failed');
        }

        try {
            $result = $processPayment->complete($payment, $callbackData);

            if ($result['success']) {
                return redirect()->route('payment.success', ['paymentUuid' => $payment->uuid]);
            }

            return redirect()->route('payment.failed', ['paymentUuid' => $payment->uuid])
                ->with('error', $result['error'] ?? 'Payment verification failed');
        } catch (\Exception $e) {
            // Log the error but always redirect to failed page
            \Illuminate\Support\Facades\Log::error('Payment callback error', [
                'payment_uuid' => $paymentUuid,
                'error' => $e->getMessage(),
            ]);

            // Mark as failed if still pending
            if ($payment->isPending()) {
                $payment->markAsFailed('An error occurred processing your payment');
            }

            return redirect()->route('payment.failed', ['paymentUuid' => $payment->uuid])
                ->with('error', 'An error occurred processing your payment');
        }
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
                'provider_slug' => $payment->booking->provider->slug,
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
            ->with([
                'booking:id,uuid,client_id,guest_name,guest_email',
                'booking.provider:id,business_name,slug,domain',
            ])
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
            'booking' => [
                'uuid' => $payment->booking->uuid,
                'provider_slug' => $payment->booking->provider->domain,
                'is_guest' => $payment->booking->isGuestBooking(),
            ],
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

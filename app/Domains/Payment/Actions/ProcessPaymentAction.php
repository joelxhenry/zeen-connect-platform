<?php

namespace App\Domains\Payment\Actions;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PowerTranzGateway;
use App\Mail\BookingConfirmed;
use App\Mail\PaymentReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProcessPaymentAction
{
    public function __construct(
        private PowerTranzGateway $gateway
    ) {}

    /**
     * Initialize a payment for 3DS processing.
     */
    public function initialize(Payment $payment, string $returnUrl, string $cancelUrl): array
    {
        return $this->gateway->initializePayment($payment, $returnUrl, $cancelUrl);
    }

    /**
     * Complete a payment after 3DS verification.
     */
    public function complete(Payment $payment, string $spiToken): array
    {
        $result = $this->gateway->verifyPayment($spiToken);

        if ($result['success']) {
            return DB::transaction(function () use ($payment, $result) {
                // Mark payment as completed
                $payment->markAsCompleted(
                    $result['transaction_id'],
                    $result['raw_response'] ?? null
                );

                // Update card details
                $payment->update([
                    'card_last_four' => $result['card_last_four'],
                    'card_brand' => $result['card_brand'],
                ]);

                // Load booking with relationships
                $payment->load(['booking.service', 'booking.provider.user', 'client']);
                $booking = $payment->booking;

                // Handle deposit payment
                if ($payment->payment_type === 'deposit') {
                    $booking->update(['deposit_paid' => true]);
                }

                // Auto-confirm booking if conditions are met
                $this->handleBookingConfirmation($booking, $payment);

                // Get client email (supports guest bookings)
                $clientEmail = $booking->client_email;

                // Send payment receipt to client
                if ($clientEmail) {
                    Mail::to($clientEmail)->send(new PaymentReceived($payment, 'client'));
                }

                // Notify provider of payment
                Mail::to($booking->provider->user->email)->send(new PaymentReceived($payment, 'provider'));

                // Send booking confirmation if just confirmed
                if ($booking->status === BookingStatus::CONFIRMED && $clientEmail) {
                    Mail::to($clientEmail)->send(new BookingConfirmed($booking));
                }

                return [
                    'success' => true,
                    'payment' => $payment->fresh(),
                ];
            });
        }

        $payment->markAsFailed(
            $result['error'] ?? 'Payment verification failed',
            $result['response_code'] ?? null,
            $result['raw_response'] ?? null
        );

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Payment verification failed',
        ];
    }

    /**
     * Handle booking confirmation based on payment and settings.
     */
    protected function handleBookingConfirmation(Booking $booking, Payment $payment): void
    {
        // Already confirmed, nothing to do
        if ($booking->status === BookingStatus::CONFIRMED) {
            return;
        }

        // Get effective booking settings
        $settings = $booking->service->getEffectiveBookingSettings();
        $requiresApproval = $settings['requires_approval'];

        // If approval is required, booking stays pending
        if ($requiresApproval) {
            return;
        }

        // If no approval required and deposit is paid (or no deposit required), confirm
        if (! $booking->requiresDeposit() || $booking->isDepositPaid()) {
            $booking->update([
                'status' => BookingStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);
        }
    }

    /**
     * Process a refund for a payment.
     */
    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        if (! $payment->canBeRefunded()) {
            return [
                'success' => false,
                'error' => 'This payment cannot be refunded.',
            ];
        }

        $result = $this->gateway->refund($payment, $amount);

        if ($result['success']) {
            // Update payment refund status
            $payment->update([
                'is_refunded' => true,
                'refund_reason' => $reason ?? 'Refunded',
                'refund_transaction_id' => $result['refund_transaction_id'] ?? null,
            ]);

            // Cancel the booking if full refund
            if ($amount === null || $amount >= $payment->amount) {
                $payment->booking->update([
                    'status' => BookingStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason ?? 'Payment refunded',
                ]);
            }
        }

        return $result;
    }
}

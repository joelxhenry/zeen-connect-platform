<?php

namespace App\Domains\Payment\Actions;

use App\Domains\Booking\Enums\BookingStatus;
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

                // Confirm the booking
                $payment->booking->update([
                    'status' => BookingStatus::CONFIRMED,
                    'confirmed_at' => now(),
                ]);

                // Load relationships for emails
                $payment->load(['booking.service', 'client', 'provider']);

                // Send payment receipt to client
                Mail::to($payment->client->email)->send(new PaymentReceived($payment, 'client'));

                // Notify provider of payment
                Mail::to($payment->provider->user->email)->send(new PaymentReceived($payment, 'provider'));

                // Send booking confirmation to client
                Mail::to($payment->client->email)->send(new BookingConfirmed($payment->booking));

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
     * Process a refund for a payment.
     */
    public function refund(Payment $payment, ?float $amount = null): array
    {
        if (! $payment->canBeRefunded()) {
            return [
                'success' => false,
                'error' => 'This payment cannot be refunded.',
            ];
        }

        $result = $this->gateway->refund($payment, $amount);

        if ($result['success']) {
            // Cancel the booking if full refund
            if ($amount === null || $amount >= $payment->amount) {
                $payment->booking->update([
                    'status' => BookingStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Payment refunded',
                ]);
            }
        }

        return $result;
    }
}

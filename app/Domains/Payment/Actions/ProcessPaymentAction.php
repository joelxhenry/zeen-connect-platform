<?php

namespace App\Domains\Payment\Actions;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Contracts\EscrowGatewayInterface;
use App\Domains\Payment\DTOs\PaymentResult;
use App\Domains\Payment\Enums\GatewayType;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\LedgerService;
use App\Domains\Payment\Services\PaymentManager;
use App\Mail\BookingConfirmed;
use App\Mail\PaymentReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProcessPaymentAction
{
    public function __construct(
        private PaymentManager $paymentManager,
        private LedgerService $ledgerService
    ) {}

    /**
     * Initialize a payment for processing.
     */
    public function initialize(Payment $payment, string $returnUrl, string $cancelUrl): array
    {
        $result = $this->paymentManager->initializePayment(
            $payment->booking,
            $payment,
            $returnUrl,
            $cancelUrl
        );

        if ($result->success) {
            return [
                'success' => true,
                'redirect_url' => $result->redirectUrl,
                'spi_token' => $result->spiToken,
                'order_id' => $result->orderId,
            ];
        }

        return [
            'success' => false,
            'error' => $result->error,
        ];
    }

    /**
     * Complete a payment after gateway callback.
     * WiPay callback data includes: status, transaction_id, order_id, message, data
     */
    public function complete(Payment $payment, array $callbackData): array
    {
        $result = $this->paymentManager->completePayment($payment, $callbackData);

        if ($result->success) {
            return DB::transaction(function () use ($payment, $result) {
                // Update card details if available
                if ($result->cardDetails) {
                    $payment->update([
                        'card_last_four' => $result->cardDetails['last_four'] ?? null,
                        'card_brand' => $result->cardDetails['brand'] ?? null,
                    ]);
                }

                // For escrow payments, record to ledger
                if ($payment->gateway_type === GatewayType::ESCROW->value) {
                    $gateway = $this->paymentManager->resolveGateway($payment->booking->provider);
                    if ($gateway instanceof EscrowGatewayInterface) {
                        $ledgerEntry = $gateway->recordToLedger($payment);
                        $payment->update(['ledger_entry_id' => $ledgerEntry->id]);
                    }
                }

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

        return [
            'success' => false,
            'error' => $result->error ?? 'Payment verification failed',
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

        $gateway = $this->paymentManager->resolveGateway($payment->booking->provider);
        $result = $gateway->refund($payment, $amount);

        if ($result->success) {
            // Update payment refund status
            $payment->update([
                'is_refunded' => true,
                'refund_reason' => $reason ?? 'Refunded',
                'refund_transaction_id' => $result->refundId ?? null,
            ]);

            // For escrow payments, debit the ledger
            if ($payment->gateway_type === GatewayType::ESCROW->value && $payment->ledger_entry_id) {
                $refundAmount = $amount ?? $payment->provider_amount;
                $this->ledgerService->debitForRefund($payment, $refundAmount);
            }

            // Cancel the booking if full refund
            if ($amount === null || $amount >= $payment->amount) {
                $payment->booking->update([
                    'status' => BookingStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason ?? 'Payment refunded',
                ]);
            }

            return [
                'success' => true,
                'refund_id' => $result->refundId,
            ];
        }

        return [
            'success' => false,
            'error' => $result->error ?? 'Refund failed',
        ];
    }
}

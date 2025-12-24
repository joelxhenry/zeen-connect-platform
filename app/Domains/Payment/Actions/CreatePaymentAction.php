<?php

namespace App\Domains\Payment\Actions;

use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Models\Payment;

class CreatePaymentAction
{
    /**
     * Create a payment record for a booking with tier-based fees.
     */
    public function execute(
        Booking $booking,
        float $amount,
        string $paymentType = 'full',
        float $platformFee = 0.0,
        float $providerAmount = 0.0,
        float $processingFee = 0.0,
        ?string $processingFeePayer = null
    ): Payment {
        return Payment::create([
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'provider_id' => $booking->provider_id,
            'amount' => $amount,
            'platform_fee' => $platformFee,
            'provider_amount' => $providerAmount ?: ($amount - $platformFee),
            'currency' => 'JMD',
            'payment_type' => $paymentType,
            'processing_fee' => $processingFee,
            'processing_fee_payer' => $processingFeePayer,
        ]);
    }
}

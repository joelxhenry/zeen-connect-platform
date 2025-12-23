<?php

namespace App\Domains\Payment\Actions;

use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Models\Payment;

class CreatePaymentAction
{
    /**
     * Create a payment record for a booking.
     */
    public function execute(Booking $booking): Payment
    {
        // Calculate platform fee (15%)
        $platformFeeRate = 0.15;
        $platformFee = round($booking->total_amount * $platformFeeRate, 2);
        $providerAmount = $booking->total_amount - $platformFee;

        return Payment::create([
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'provider_id' => $booking->provider_id,
            'amount' => $booking->total_amount,
            'platform_fee' => $platformFee,
            'provider_amount' => $providerAmount,
            'currency' => 'JMD',
        ]);
    }
}

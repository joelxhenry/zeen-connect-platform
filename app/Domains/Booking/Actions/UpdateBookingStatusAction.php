<?php

namespace App\Domains\Booking\Actions;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Actions\ProcessPaymentAction;
use App\Mail\BookingConfirmed;
use App\Mail\BookingStatusChanged;
use App\Mail\PaymentRequired;
use App\Mail\ReviewRequest;
use Illuminate\Support\Facades\Mail;

class UpdateBookingStatusAction
{
    public function __construct(
        protected ProcessPaymentAction $paymentAction
    ) {}

    public function execute(
        Booking $booking,
        BookingStatus $newStatus,
        ?string $reason = null,
        ?string $providerNotes = null
    ): Booking {
        // Validate status transition
        if (! $booking->status->canTransitionTo($newStatus)) {
            throw new \Exception("Cannot transition from {$booking->status->label()} to {$newStatus->label()}.");
        }

        $previousStatus = $booking->status->value;

        $data = [
            'status' => $newStatus,
        ];

        // Set timestamps based on new status
        match ($newStatus) {
            BookingStatus::CONFIRMED => $data['confirmed_at'] = now(),
            BookingStatus::COMPLETED => $data['completed_at'] = now(),
            BookingStatus::CANCELLED => [
                $data['cancelled_at'] = now(),
                $data['cancellation_reason'] = $reason,
            ],
            BookingStatus::NO_SHOW => $data['completed_at'] = now(),
            default => null,
        };

        if ($providerNotes) {
            $data['provider_notes'] = $providerNotes;
        }

        $booking->update($data);

        // Handle auto-refund for cancelled bookings with paid deposits
        if ($newStatus === BookingStatus::CANCELLED && $booking->isDepositPaid()) {
            $this->refundDeposit($booking, $reason);
        }

        // Update provider stats
        if ($newStatus === BookingStatus::COMPLETED) {
            $booking->provider->increment('total_bookings');
            if ($booking->client) {
                $booking->client->increment('total_bookings');
            }
        }

        // Load relationships for email
        $booking->load(['client', 'provider.user', 'service', 'payment']);

        // Send notification emails based on status change
        $this->sendStatusNotifications($booking, $previousStatus, $newStatus);

        return $booking->fresh();
    }

    /**
     * Refund the deposit for a cancelled booking.
     */
    protected function refundDeposit(Booking $booking, ?string $reason): void
    {
        $depositPayment = $booking->payments()
            ->where('payment_type', 'deposit')
            ->where('status', 'completed')
            ->first();

        if ($depositPayment) {
            $this->paymentAction->refund(
                $depositPayment,
                null, // Full refund
                $reason ?? 'Booking cancelled by provider'
            );
        }
    }

    protected function sendStatusNotifications(
        Booking $booking,
        string $previousStatus,
        BookingStatus $newStatus
    ): void {
        $clientEmail = $booking->client_email;
        $provider = $booking->provider;

        if (! $clientEmail) {
            return;
        }

        match ($newStatus) {
            BookingStatus::CONFIRMED => $this->sendConfirmationEmail($booking, $clientEmail),

            BookingStatus::COMPLETED => [
                // Notify client that booking is completed
                Mail::to($clientEmail)->send(new BookingStatusChanged($booking, $previousStatus, 'client')),
                // Send review request after a short delay (queued)
                Mail::to($clientEmail)->later(now()->addHours(2), new ReviewRequest($booking)),
            ],

            BookingStatus::CANCELLED => [
                Mail::to($clientEmail)->send(new BookingStatusChanged($booking, $previousStatus, 'client')),
                Mail::to($provider->user->email)->send(new BookingStatusChanged($booking, $previousStatus, 'provider')),
            ],

            BookingStatus::NO_SHOW => [
                Mail::to($clientEmail)->send(new BookingStatusChanged($booking, $previousStatus, 'client')),
            ],

            default => null,
        };
    }

    /**
     * Send confirmation email with payment link if deposit is required.
     */
    protected function sendConfirmationEmail(Booking $booking, string $clientEmail): void
    {
        // If deposit is required but not yet paid, send payment request
        if ($booking->requiresDeposit() && ! $booking->isDepositPaid()) {
            Mail::to($clientEmail)->send(new PaymentRequired($booking));
        } else {
            // Send standard confirmation
            Mail::to($clientEmail)->send(new BookingConfirmed($booking));
        }
    }
}

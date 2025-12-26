<?php

namespace App\Mail;

use App\Domains\Booking\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRequired extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Required - ' . $this->booking->service->name,
        );
    }

    public function content(): Content
    {
        $paymentUrl = $this->booking->isGuestBooking()
            ? route('payment.checkout', $this->booking->uuid)
            : route('payment.checkout', $this->booking->uuid);

        return new Content(
            view: 'emails.bookings.payment-required',
            with: [
                'booking' => $this->booking,
                'clientName' => $this->booking->getClientName(),
                'provider' => $this->booking->provider,
                'service' => $this->booking->service,
                'depositAmount' => $this->booking->deposit_amount,
                'paymentUrl' => $paymentUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

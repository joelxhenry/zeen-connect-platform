<?php

namespace App\Mail;

use App\Domains\Booking\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Confirmed - ' . $this->booking->service->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.confirmed',
            with: [
                'booking' => $this->booking,
                'client' => $this->booking->client,
                'provider' => $this->booking->provider,
                'service' => $this->booking->service,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

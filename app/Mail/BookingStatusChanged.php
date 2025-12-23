<?php

namespace App\Mail;

use App\Domains\Booking\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $previousStatus,
        public string $recipientType = 'client'
    ) {}

    public function envelope(): Envelope
    {
        $statusLabels = [
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'Marked as No Show',
        ];

        $statusLabel = $statusLabels[$this->booking->status->value] ?? ucfirst($this->booking->status->value);

        return new Envelope(
            subject: "Booking {$statusLabel} - {$this->booking->service->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.status-changed',
            with: [
                'booking' => $this->booking,
                'client' => $this->booking->client,
                'provider' => $this->booking->provider,
                'service' => $this->booking->service,
                'previousStatus' => $this->previousStatus,
                'recipientType' => $this->recipientType,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

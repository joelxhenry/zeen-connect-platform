<?php

namespace App\Mail;

use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public string $recipientType = 'client'
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->recipientType === 'client'
            ? 'Payment Confirmation - ' . $this->payment->booking->service->name
            : 'Payment Received - ' . $this->payment->booking->service->name;

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $view = $this->recipientType === 'client'
            ? 'emails.payments.receipt'
            : 'emails.payments.provider-notification';

        return new Content(
            view: $view,
            with: [
                'payment' => $this->payment,
                'booking' => $this->payment->booking,
                'client' => $this->payment->client,
                'provider' => $this->payment->provider,
                'service' => $this->payment->booking->service,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

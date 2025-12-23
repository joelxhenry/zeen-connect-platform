@extends('emails.layout')

@section('title', 'Payment Confirmation')

@section('content')
    <p class="greeting">Hi {{ $client->name }},</p>

    <p class="message">
        Your payment has been processed successfully. Here's your receipt.
    </p>

    <div style="text-align: center; margin: 24px 0;">
        <p style="color: #6b7280; font-size: 14px; margin: 0;">Amount Paid</p>
        <p class="amount" style="margin: 4px 0;">{{ $payment->amount_display }}</p>
    </div>

    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Transaction ID</span>
            <span class="info-value">{{ strtoupper(substr($payment->uuid, 0, 8)) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date</span>
            <span class="info-value">{{ $payment->paid_at->format('F j, Y \a\t g:i A') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Service</span>
            <span class="info-value">{{ $service->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Provider</span>
            <span class="info-value">{{ $provider->business_name }}</span>
        </div>
        @if($payment->card_last_four)
        <div class="info-row">
            <span class="info-label">Payment Method</span>
            <span class="info-value">{{ ucfirst($payment->card_brand ?? 'Card') }} ending in {{ $payment->card_last_four }}</span>
        </div>
        @endif
    </div>

    <div class="highlight-box">
        <strong>Booking Details:</strong>
        <p style="margin: 8px 0 0 0; color: #4b5563;">
            {{ $booking->date->format('l, F j, Y') }} at {{ $booking->start_time->format('g:i A') }}
        </p>
    </div>

    <p style="text-align: center; margin-top: 24px;">
        <a href="{{ route('client.bookings.show', $booking->uuid) }}" class="btn">View Booking</a>
    </p>

    <div class="divider"></div>

    <p style="color: #6b7280; font-size: 14px; text-align: center;">
        Questions about this payment? <a href="mailto:support@zeenconnect.com" style="color: #6366f1;">Contact Support</a>
    </p>
@endsection

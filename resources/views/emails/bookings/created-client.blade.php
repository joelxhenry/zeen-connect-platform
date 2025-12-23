@extends('emails.layout')

@section('title', 'Booking Request Submitted')

@section('content')
    <p class="greeting">Hi {{ $client->name }},</p>

    <p class="message">
        Your booking request has been submitted successfully! The provider will review and confirm your booking shortly.
    </p>

    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Service</span>
            <span class="info-value">{{ $service->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Provider</span>
            <span class="info-value">{{ $provider->business_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date</span>
            <span class="info-value">{{ $booking->date->format('l, F j, Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Time</span>
            <span class="info-value">{{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Duration</span>
            <span class="info-value">{{ $service->duration_display }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total</span>
            <span class="info-value">{{ $booking->total_display }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Status</span>
            <span class="info-value">
                <span class="status-badge status-pending">Pending Confirmation</span>
            </span>
        </div>
    </div>

    <div class="highlight-box">
        <strong>What's next?</strong>
        <p style="margin: 8px 0 0 0; color: #4b5563;">
            The provider will review your request and confirm the booking. You'll receive an email once it's confirmed.
        </p>
    </div>

    <p style="text-align: center; margin-top: 24px;">
        <a href="{{ route('client.bookings.show', $booking->uuid) }}" class="btn">View Booking Details</a>
    </p>

    @if($booking->notes)
        <div class="divider"></div>
        <p style="color: #6b7280; font-size: 14px;">
            <strong>Your notes:</strong><br>
            {{ $booking->notes }}
        </p>
    @endif
@endsection

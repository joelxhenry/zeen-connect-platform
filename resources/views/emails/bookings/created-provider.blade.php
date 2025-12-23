@extends('emails.layout')

@section('title', 'New Booking Request')

@section('content')
    <p class="greeting">Hi {{ $provider->business_name }},</p>

    <p class="message">
        You have a new booking request! Please review the details below and confirm or decline the booking.
    </p>

    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Client</span>
            <span class="info-value">{{ $client->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Service</span>
            <span class="info-value">{{ $service->name }}</span>
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
            <span class="info-label">Total Amount</span>
            <span class="info-value">{{ $booking->total_display }}</span>
        </div>
    </div>

    @if($booking->notes)
        <div class="highlight-box">
            <strong>Client Notes:</strong>
            <p style="margin: 8px 0 0 0; color: #4b5563;">
                {{ $booking->notes }}
            </p>
        </div>
    @endif

    <p style="text-align: center; margin-top: 24px;">
        <a href="{{ route('provider.bookings.show', $booking->uuid) }}" class="btn">Review & Confirm Booking</a>
    </p>

    <div class="divider"></div>

    <p style="color: #6b7280; font-size: 14px; text-align: center;">
        Please respond to this booking request within 24 hours to maintain a good response rate.
    </p>
@endsection

@extends('emails.layout')

@section('title', 'Booking Reminder')

@section('content')
    <p class="greeting">Hi {{ $provider->business_name }},</p>

    <p class="message">
        This is a reminder that you have a booking scheduled for tomorrow.
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
        <a href="{{ route('provider.bookings.show', $booking->uuid) }}" class="btn">View Booking Details</a>
    </p>
@endsection

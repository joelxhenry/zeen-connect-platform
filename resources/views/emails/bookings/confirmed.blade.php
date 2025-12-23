@extends('emails.layout')

@section('title', 'Booking Confirmed')

@section('content')
    <p class="greeting">Hi {{ $client->name }},</p>

    <p class="message">
        Great news! Your booking has been confirmed by {{ $provider->business_name }}. You're all set!
    </p>

    <div style="text-align: center; margin: 24px 0;">
        <span class="status-badge status-confirmed" style="font-size: 14px; padding: 8px 16px;">CONFIRMED</span>
    </div>

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
        @if($provider->location)
        <div class="info-row">
            <span class="info-label">Location</span>
            <span class="info-value">{{ $provider->location }}</span>
        </div>
        @endif
    </div>

    <div class="highlight-box">
        <strong>Remember:</strong>
        <ul style="margin: 8px 0 0 0; padding-left: 20px; color: #4b5563;">
            <li>Arrive on time for your appointment</li>
            <li>Contact the provider if you need to reschedule</li>
            <li>Cancel at least 24 hours in advance if needed</li>
        </ul>
    </div>

    <p style="text-align: center; margin-top: 24px;">
        <a href="{{ route('client.bookings.show', $booking->uuid) }}" class="btn">View Booking Details</a>
    </p>
@endsection

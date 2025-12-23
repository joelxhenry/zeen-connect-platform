@extends('emails.layout')

@section('title', 'Booking Reminder')

@section('content')
    <p class="greeting">Hi {{ $client->name }},</p>

    <p class="message">
        This is a friendly reminder that you have a booking tomorrow with {{ $provider->business_name }}.
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
        @if($provider->location)
        <div class="info-row">
            <span class="info-label">Location</span>
            <span class="info-value">{{ $provider->location }}</span>
        </div>
        @endif
    </div>

    <div class="highlight-box">
        <strong>Prepare for your appointment:</strong>
        <ul style="margin: 8px 0 0 0; padding-left: 20px; color: #4b5563;">
            <li>Plan to arrive a few minutes early</li>
            <li>Bring any required items for your service</li>
            <li>Contact the provider if you have any questions</li>
        </ul>
    </div>

    <p style="text-align: center; margin-top: 24px;">
        <a href="{{ route('client.bookings.show', $booking->uuid) }}" class="btn">View Booking Details</a>
    </p>

    <div class="divider"></div>

    <p style="color: #6b7280; font-size: 14px; text-align: center;">
        Need to cancel? Please do so at least 24 hours in advance.
    </p>
@endsection

@extends('emails.layout')

@section('title', 'Booking Status Update')

@section('content')
    @php
        $recipientName = $recipientType === 'client' ? $client->name : $provider->business_name;
        $statusColors = [
            'confirmed' => 'status-confirmed',
            'completed' => 'status-completed',
            'cancelled' => 'status-cancelled',
            'no_show' => 'status-cancelled',
        ];
        $statusColor = $statusColors[$booking->status->value] ?? 'status-pending';
        $statusLabel = ucfirst($booking->status->value);
    @endphp

    <p class="greeting">Hi {{ $recipientName }},</p>

    @if($booking->status->value === 'confirmed')
        <p class="message">
            @if($recipientType === 'client')
                Great news! Your booking has been confirmed.
            @else
                You have successfully confirmed this booking.
            @endif
        </p>
    @elseif($booking->status->value === 'completed')
        <p class="message">
            @if($recipientType === 'client')
                Your booking has been marked as completed. We hope you had a great experience!
            @else
                You have marked this booking as completed. Thank you for providing your services!
            @endif
        </p>
    @elseif($booking->status->value === 'cancelled')
        <p class="message">
            @if($recipientType === 'client')
                Unfortunately, your booking has been cancelled.
            @else
                This booking has been cancelled.
            @endif
        </p>
    @elseif($booking->status->value === 'no_show')
        <p class="message">
            This booking has been marked as a no-show.
        </p>
    @endif

    <div style="text-align: center; margin: 24px 0;">
        <span class="status-badge {{ $statusColor }}" style="font-size: 14px; padding: 8px 16px;">
            {{ strtoupper($statusLabel) }}
        </span>
    </div>

    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Service</span>
            <span class="info-value">{{ $service->name }}</span>
        </div>
        @if($recipientType === 'client')
        <div class="info-row">
            <span class="info-label">Provider</span>
            <span class="info-value">{{ $provider->business_name }}</span>
        </div>
        @else
        <div class="info-row">
            <span class="info-label">Client</span>
            <span class="info-value">{{ $client->name }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Date</span>
            <span class="info-value">{{ $booking->date->format('l, F j, Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Time</span>
            <span class="info-value">{{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}</span>
        </div>
    </div>

    @if($booking->status->value === 'completed' && $recipientType === 'client')
        <div class="highlight-box">
            <strong>Share your experience!</strong>
            <p style="margin: 8px 0 0 0; color: #4b5563;">
                Help other clients by leaving a review for {{ $provider->business_name }}.
            </p>
        </div>
        <p style="text-align: center; margin-top: 24px;">
            <a href="{{ route('client.reviews.create', $booking->uuid) }}" class="btn">Leave a Review</a>
        </p>
    @else
        <p style="text-align: center; margin-top: 24px;">
            @if($recipientType === 'client')
                <a href="{{ route('client.bookings.show', $booking->uuid) }}" class="btn">View Booking</a>
            @else
                <a href="{{ route('provider.bookings.show', $booking->uuid) }}" class="btn">View Booking</a>
            @endif
        </p>
    @endif
@endsection

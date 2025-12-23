@extends('emails.layout')

@section('title', 'How was your experience?')

@section('content')
    <p class="greeting">Hi {{ $client->name }},</p>

    <p class="message">
        How was your experience with {{ $provider->business_name }}? Your feedback helps other clients make informed decisions and helps providers improve their services.
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
            <span class="info-value">{{ $booking->date->format('F j, Y') }}</span>
        </div>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <p style="font-size: 32px; margin: 0;">
            @for($i = 1; $i <= 5; $i++)
                <span style="color: #fbbf24;">&#9733;</span>
            @endfor
        </p>
        <p style="color: #6b7280; margin: 8px 0 0 0;">Rate your experience</p>
    </div>

    <p style="text-align: center; margin-top: 24px;">
        <a href="{{ route('client.reviews.create', $booking->uuid) }}" class="btn">Leave a Review</a>
    </p>

    <div class="divider"></div>

    <p style="color: #6b7280; font-size: 14px; text-align: center;">
        It only takes a minute and means a lot to the provider and the community!
    </p>
@endsection

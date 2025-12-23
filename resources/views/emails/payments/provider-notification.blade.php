@extends('emails.layout')

@section('title', 'Payment Received')

@section('content')
    <p class="greeting">Hi {{ $provider->business_name }},</p>

    <p class="message">
        Great news! A payment has been received for your service.
    </p>

    <div style="text-align: center; margin: 24px 0;">
        <p style="color: #6b7280; font-size: 14px; margin: 0;">Your Earnings</p>
        <p class="amount" style="margin: 4px 0; color: #059669;">{{ $payment->provider_amount_display }}</p>
        <p style="color: #6b7280; font-size: 12px; margin: 4px 0;">
            (Total payment: {{ $payment->amount_display }} - Platform fee: {{ $payment->platform_fee_display }})
        </p>
    </div>

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
            <span class="info-label">Booking Date</span>
            <span class="info-value">{{ $booking->date->format('l, F j, Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Time</span>
            <span class="info-value">{{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Payment Date</span>
            <span class="info-value">{{ $payment->paid_at->format('F j, Y \a\t g:i A') }}</span>
        </div>
    </div>

    <p style="text-align: center; margin-top: 24px;">
        <a href="{{ route('provider.payments.index') }}" class="btn">View Earnings Dashboard</a>
    </p>

    <div class="divider"></div>

    <p style="color: #6b7280; font-size: 14px; text-align: center;">
        Your earnings will be included in your next scheduled payout.
    </p>
@endsection

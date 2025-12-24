@extends('emails.layout')

@section('title', 'Payment Required')

@section('content')
    <p class="greeting">Hi {{ $clientName }},</p>

    <p class="message">
        Great news! Your booking with {{ $provider->business_name }} has been confirmed.
        To secure your appointment, please complete the deposit payment.
    </p>

    <div style="text-align: center; margin: 24px 0;">
        <span class="status-badge" style="font-size: 14px; padding: 8px 16px; background-color: #fef3c7; color: #92400e;">PAYMENT REQUIRED</span>
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
            <span class="info-value">{{ $booking->booking_date->format('l, F j, Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Time</span>
            <span class="info-value">{{ $booking->formatted_time }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Service Total</span>
            <span class="info-value">{{ $booking->total_display }}</span>
        </div>
        <div class="info-row" style="border-top: 2px solid #e5e7eb; padding-top: 12px; margin-top: 12px;">
            <span class="info-label" style="font-weight: 600;">Deposit Due</span>
            <span class="info-value" style="font-weight: 600; color: #059669;">${{ number_format($depositAmount, 2) }}</span>
        </div>
    </div>

    <div class="highlight-box" style="background-color: #fef3c7; border-left-color: #f59e0b;">
        <strong>Important:</strong>
        <p style="margin: 8px 0 0 0; color: #78350f;">
            Please complete your deposit payment to confirm your booking.
            Your appointment will be held for 24 hours. After that, it may be released to other clients.
        </p>
    </div>

    <p style="text-align: center; margin-top: 24px;">
        <a href="{{ $paymentUrl }}" class="btn" style="background-color: #059669;">Pay Deposit Now</a>
    </p>

    <p style="text-align: center; margin-top: 16px; color: #6b7280; font-size: 14px;">
        The remaining balance of ${{ number_format($booking->total_amount - $depositAmount, 2) }} will be due at the time of service.
    </p>
@endsection

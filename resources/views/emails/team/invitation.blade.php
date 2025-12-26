@extends('emails.layout')

@section('title', 'Team Invitation')

@section('content')
    <p class="greeting">You're Invited!</p>

    <p class="message">
        <strong>{{ $provider->business_name }}</strong> has invited you to join their team on Zeen Connect.
    </p>

    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Business</span>
            <span class="info-value">{{ $provider->business_name }}</span>
        </div>
        @if($teamMember->name)
        <div class="info-row">
            <span class="info-label">Invited as</span>
            <span class="info-value">{{ $teamMember->name }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value">{{ $teamMember->email }}</span>
        </div>
    </div>

    <p class="message">
        As a team member, you'll be able to help manage bookings, services, and more based on the permissions granted to you.
    </p>

    <div class="highlight-box">
        <p style="margin: 0; color: #4b5563;">
            <strong>Important:</strong> This invitation will expire in {{ $expiresIn }}.
        </p>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>
    </div>

    <div class="divider"></div>

    <p style="color: #6b7280; font-size: 14px;">
        If you didn't expect this invitation, you can safely ignore this email.
    </p>

    <p style="color: #6b7280; font-size: 12px; margin-top: 16px;">
        If the button above doesn't work, copy and paste this link into your browser:<br>
        <a href="{{ $acceptUrl }}" style="color: #6366f1; word-break: break-all;">{{ $acceptUrl }}</a>
    </p>
@endsection

<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Enums\PayoutStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\Payout;
use App\Domains\Review\Models\Review;
use App\Domains\Service\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $provider = $user->provider;

        // Get stats
        $stats = [
            'totalEarnings' => Payment::where('provider_id', $provider->id)
                ->where('status', PaymentStatus::COMPLETED)
                ->sum('provider_amount') / 100, // Convert from cents
            'pendingPayout' => Payment::where('provider_id', $provider->id)
                ->where('status', PaymentStatus::COMPLETED)
                ->whereNull('payout_id')
                ->sum('provider_amount') / 100,
            'completedBookings' => Booking::where('provider_id', $provider->id)
                ->where('status', BookingStatus::COMPLETED)
                ->count(),
            'activeServices' => Service::where('provider_id', $provider->id)
                ->where('is_active', true)
                ->count(),
            'pendingBookings' => Booking::where('provider_id', $provider->id)
                ->where('status', BookingStatus::PENDING)
                ->count(),
        ];

        // Get upcoming bookings (next 7 days)
        $upcomingBookings = Booking::with(['client', 'service'])
            ->where('provider_id', $provider->id)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->where('booking_date', '>=', now()->toDateString())
            ->where('booking_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(fn($booking) => [
                'uuid' => $booking->uuid,
                'client' => [
                    'name' => $booking->client?->name ?? $booking->guest_name,
                    'avatar' => $booking->client?->avatar,
                ],
                'service' => [
                    'name' => $booking->service->name,
                ],
                'date' => $booking->booking_date->format('l, M j'),
                'time' => $booking->start_time->format('g:i A'),
                'total_amount' => $booking->total_display,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_color' => $booking->status->color(),
            ]);

        // Get recent payments (last 5 completed)
        $recentPayments = Payment::with(['booking.service'])
            ->where('provider_id', $provider->id)
            ->where('status', PaymentStatus::COMPLETED)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($payment) => [
                'uuid' => $payment->uuid,
                'amount' => number_format($payment->provider_amount / 100, 2),
                'service_name' => $payment->booking->service->name ?? 'Service',
                'date' => $payment->created_at->format('M j, Y'),
            ]);

        // Get recent reviews (last 5)
        $recentReviews = Review::with(['client', 'service'])
            ->where('provider_id', $provider->id)
            ->where('is_visible', true)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($review) => [
                'uuid' => $review->uuid,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'client' => [
                    'name' => $review->client->name,
                ],
                'service_name' => $review->service->name ?? null,
                'date' => $review->created_at->format('M j, Y'),
                'has_response' => $review->provider_response !== null,
            ]);

        // Count unresponded reviews
        $unrespondedReviewsCount = Review::where('provider_id', $provider->id)
            ->where('is_visible', true)
            ->whereNull('provider_response')
            ->count();

        return Inertia::render('Provider/Dashboard', [
            'provider' => [
                'business_name' => $provider->business_name,
                'tagline' => $provider->tagline,
                'rating_avg' => $provider->rating_avg,
                'rating_count' => $provider->rating_count,
            ],
            'stats' => $stats,
            'upcomingBookings' => $upcomingBookings,
            'recentPayments' => $recentPayments,
            'recentReviews' => $recentReviews,
            'unrespondedReviewsCount' => $unrespondedReviewsCount,
        ]);
    }
}

<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Review\Models\Review;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // Get booking stats
        $stats = [
            'upcoming' => Booking::where('client_id', $user->id)
                ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
                ->where('booking_date', '>=', now()->toDateString())
                ->count(),
            'completed' => Booking::where('client_id', $user->id)
                ->where('status', BookingStatus::COMPLETED)
                ->count(),
            'reviews' => Review::where('client_id', $user->id)->count(),
        ];

        // Get upcoming bookings (next 7 days)
        $upcomingBookings = Booking::with(['provider', 'service'])
            ->where('client_id', $user->id)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->where('booking_date', '>=', now()->toDateString())
            ->where('booking_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(fn ($booking) => [
                'uuid' => $booking->uuid,
                'provider_name' => $booking->provider->business_name,
                'provider_slug' => $booking->provider->slug,
                'service_name' => $booking->service->name,
                'date' => $booking->date->format('l, M j'),
                'time' => $booking->start_time->format('g:i A'),
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_color' => $booking->status->color(),
            ]);

        // Get recent bookings (history)
        $recentBookings = Booking::with(['provider', 'service', 'review'])
            ->where('client_id', $user->id)
            ->whereIn('status', [BookingStatus::COMPLETED, BookingStatus::CANCELLED])
            ->orderBy('booking_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($booking) => [
                'uuid' => $booking->uuid,
                'provider_name' => $booking->provider->business_name,
                'provider_slug' => $booking->provider->slug,
                'service_name' => $booking->service->name,
                'date' => $booking->date->format('M j, Y'),
                'total' => $booking->total_display,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_color' => $booking->status->color(),
                'has_review' => $booking->review !== null,
                'can_review' => $booking->canBeReviewed(),
            ]);

        // Get bookings that need review
        $pendingReviews = Booking::with(['provider', 'service'])
            ->where('client_id', $user->id)
            ->where('status', BookingStatus::COMPLETED)
            ->whereDoesntHave('review')
            ->orderBy('completed_at', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($booking) => [
                'uuid' => $booking->uuid,
                'provider_name' => $booking->provider->business_name,
                'service_name' => $booking->service->name,
                'date' => $booking->date->format('M j, Y'),
            ]);

        return Inertia::render('Client/Dashboard', [
            'stats' => $stats,
            'upcomingBookings' => $upcomingBookings,
            'recentBookings' => $recentBookings,
            'pendingReviews' => $pendingReviews,
        ]);
    }
}

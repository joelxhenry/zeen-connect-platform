<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Resources\BookingResource;
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

        // Get next upcoming booking for hero card
        $nextBooking = Booking::with([
            'provider:id,uuid,business_name,slug,address',
            'provider.user:id,avatar',
            'service:id,uuid,name,duration_minutes,price',
        ])
            ->where('client_id', $user->id)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->where('booking_date', '>=', now()->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->first();

        $nextBookingData = $nextBooking
            ? (new BookingResource($nextBooking))
                ->withClient(false)
                ->withProvider()
                ->resolve()
            : null;

        // Get upcoming bookings (next 7 days)
        $upcomingBookings = Booking::with([
            'provider:id,uuid,business_name,slug',
            'provider.user:id,avatar',
            'service:id,uuid,name,duration_minutes,price',
        ])
            ->where('client_id', $user->id)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->where('booking_date', '>=', now()->toDateString())
            ->where('booking_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(fn ($booking) => (new BookingResource($booking))
                ->withClient(false)
                ->withProvider()
                ->resolve()
            );

        // Get recent bookings (history)
        $recentBookings = Booking::with([
            'provider:id,uuid,business_name,slug',
            'provider.user:id,avatar',
            'service:id,uuid,name,duration_minutes,price',
            'review',
        ])
            ->where('client_id', $user->id)
            ->whereIn('status', [BookingStatus::COMPLETED, BookingStatus::CANCELLED])
            ->orderBy('booking_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($booking) => (new BookingResource($booking))
                ->withClient(false)
                ->withProvider()
                ->withReview()
                ->resolve()
            );

        // Get bookings that need review
        $pendingReviews = Booking::with([
            'provider:id,uuid,business_name,slug',
            'service:id,uuid,name',
        ])
            ->where('client_id', $user->id)
            ->where('status', BookingStatus::COMPLETED)
            ->whereDoesntHave('review')
            ->orderBy('completed_at', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($booking) => (new BookingResource($booking))
                ->withClient(false)
                ->withProvider()
                ->resolve()
            );

        return Inertia::render('Client/Dashboard', [
            'userName' => $user->name,
            'stats' => $stats,
            'nextBooking' => $nextBookingData,
            'upcomingBookings' => $upcomingBookings,
            'recentBookings' => $recentBookings,
            'pendingReviews' => $pendingReviews,
        ]);
    }
}

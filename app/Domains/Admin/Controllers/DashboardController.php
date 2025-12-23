<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Provider\Models\Provider;
use App\Domains\Review\Models\Review;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // Key metrics
        $stats = [
            'total_users' => User::count(),
            'total_providers' => Provider::count(),
            'active_providers' => Provider::where('status', 'active')->count(),
            'pending_providers' => Provider::where('status', 'pending')->count(),
            'total_bookings' => Booking::count(),
            'completed_bookings' => Booking::where('status', BookingStatus::COMPLETED)->count(),
            'pending_bookings' => Booking::whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])->count(),
            'total_revenue' => Payment::where('status', PaymentStatus::COMPLETED)->sum('amount'),
            'total_reviews' => Review::count(),
        ];

        // Revenue over last 30 days
        $revenueByDay = Payment::where('status', PaymentStatus::COMPLETED)
            ->where('completed_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($item) => [
                'date' => $item->date,
                'total' => (float) $item->total,
                'count' => $item->count,
            ]);

        // Bookings by status
        $bookingsByStatus = Booking::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status->value => $item->count]);

        // Recent bookings
        $recentBookings = Booking::with(['client:id,name,email', 'provider:id,business_name', 'service:id,name'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($booking) => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'client_name' => $booking->client->name,
                'provider_name' => $booking->provider->business_name,
                'service_name' => $booking->service->name,
                'booking_date' => $booking->booking_date->format('M d, Y'),
                'start_time' => $booking->formatted_start_time,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'total_amount' => $booking->formatted_total,
                'created_at' => $booking->created_at->diffForHumans(),
            ]);

        // Recent payments
        $recentPayments = Payment::with(['client:id,name', 'booking:id,uuid'])
            ->where('status', PaymentStatus::COMPLETED)
            ->latest('completed_at')
            ->take(10)
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'client_name' => $payment->client->name,
                'amount' => $payment->formatted_amount,
                'booking_uuid' => $payment->booking->uuid,
                'completed_at' => $payment->completed_at?->diffForHumans(),
            ]);

        // Pending provider verifications
        $pendingProviders = Provider::with(['user:id,name,email,created_at'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($provider) => [
                'id' => $provider->id,
                'uuid' => $provider->uuid,
                'business_name' => $provider->business_name,
                'user_name' => $provider->user->name,
                'user_email' => $provider->user->email,
                'created_at' => $provider->created_at->diffForHumans(),
            ]);

        // Top providers by bookings
        $topProviders = Provider::with(['user:id,name,avatar'])
            ->where('status', 'active')
            ->orderByDesc('total_bookings')
            ->take(5)
            ->get()
            ->map(fn ($provider) => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'avatar' => $provider->user->avatar,
                'total_bookings' => $provider->total_bookings,
                'rating_avg' => $provider->rating_avg,
                'rating_count' => $provider->rating_count,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'revenueByDay' => $revenueByDay,
            'bookingsByStatus' => $bookingsByStatus,
            'recentBookings' => $recentBookings,
            'recentPayments' => $recentPayments,
            'pendingProviders' => $pendingProviders,
            'topProviders' => $topProviders,
        ]);
    }
}

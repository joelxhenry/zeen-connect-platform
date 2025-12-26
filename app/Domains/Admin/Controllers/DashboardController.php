<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Enums\PayoutStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\Payout;
use App\Domains\Provider\Models\Provider;
use App\Domains\Review\Models\Review;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WaitlistSubscriber;
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
            // New metrics for dashboard redesign
            'mrr' => 0, // Placeholder until subscriptions are implemented
            'pending_payouts_amount' => Payout::where('status', PayoutStatus::PENDING)->sum('amount'),
            'waitlist_count' => WaitlistSubscriber::count(),
        ];

        // Revenue over last 30 days
        $revenueByDay = Payment::where('status', PaymentStatus::COMPLETED)
            ->where('paid_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(paid_at) as date'),
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
                'client_name' =>  $booking->getClientName(),
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
            ->latest('paid_at')
            ->take(10)
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'client_name' => $payment->getClientName(),
                'amount' => $payment->formatted_amount,
                'booking_uuid' => $payment->booking->uuid,
                'paid_at' => $payment->paid_at?->diffForHumans(),
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

        // Financial data for chart (revenue vs payouts over last 30 days)
        $financialData = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayRevenue = $revenueByDay->firstWhere('date', $date);
            $dayPayouts = Payout::where('status', PayoutStatus::COMPLETED)
                ->whereDate('processed_at', $date)
                ->sum('amount');

            $financialData->push([
                'date' => $date,
                'revenue' => $dayRevenue ? (float) $dayRevenue['total'] : 0,
                'payouts' => (float) $dayPayouts,
            ]);
        }

        // Critical alerts for notification bell
        $criticalAlerts = collect();
        $failedPayouts = Payout::where('status', PayoutStatus::FAILED)->count();
        if ($failedPayouts > 0) {
            $criticalAlerts->push([
                'id' => 'failed-payouts',
                'type' => 'payout_failed',
                'title' => 'Failed Payouts',
                'message' => "{$failedPayouts} payout(s) failed and need attention",
                'timestamp' => now()->diffForHumans(),
                'read' => false,
                'link' => '/admin/payouts?status=failed',
            ]);
        }

        $pendingProviderCount = $stats['pending_providers'];
        if ($pendingProviderCount > 0) {
            $criticalAlerts->push([
                'id' => 'pending-providers',
                'type' => 'provider_pending',
                'title' => 'Pending Providers',
                'message' => "{$pendingProviderCount} provider(s) awaiting approval",
                'timestamp' => now()->diffForHumans(),
                'read' => false,
                'link' => '/admin/providers?status=pending',
            ]);
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'revenueByDay' => $revenueByDay,
            'bookingsByStatus' => $bookingsByStatus,
            'recentBookings' => $recentBookings,
            'recentPayments' => $recentPayments,
            'pendingProviders' => $pendingProviders,
            'topProviders' => $topProviders,
            'financialData' => $financialData,
            'criticalAlerts' => $criticalAlerts,
        ]);
    }
}

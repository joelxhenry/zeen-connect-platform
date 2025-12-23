<?php

namespace App\Domains\Payment\Controllers;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Enums\PayoutStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\Payout;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderEarningsController extends Controller
{
    /**
     * Display provider's earnings dashboard.
     */
    public function index(Request $request): Response
    {
        $provider = $request->user()->provider;

        // Get earnings summary
        $totalEarnings = Payment::forProvider($provider->id)
            ->completed()
            ->sum('provider_amount');

        $pendingPayout = Payment::forProvider($provider->id)
            ->unpaidOut()
            ->sum('provider_amount');

        $totalPaidOut = Payout::forProvider($provider->id)
            ->completed()
            ->sum('amount');

        // Get recent payments
        $recentPayments = Payment::forProvider($provider->id)
            ->completed()
            ->with(['booking:id,uuid,booking_date,start_time', 'booking.service:id,name'])
            ->orderByDesc('paid_at')
            ->take(10)
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'booking_uuid' => $payment->booking->uuid,
                'service_name' => $payment->booking->service->name,
                'booking_date' => $payment->booking->formatted_date,
                'amount' => $payment->amount,
                'platform_fee' => $payment->platform_fee,
                'provider_amount' => $payment->provider_amount,
                'provider_amount_display' => $payment->provider_amount_display,
                'paid_at' => $payment->paid_at->format('M j, Y'),
            ]);

        // Get payout history
        $payouts = Payout::forProvider($provider->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(fn ($payout) => [
                'id' => $payout->id,
                'uuid' => $payout->uuid,
                'amount' => $payout->amount,
                'amount_display' => $payout->amount_display,
                'period_display' => $payout->period_display,
                'status' => $payout->status->value,
                'status_label' => $payout->status->label(),
                'status_color' => $payout->status->color(),
                'bank_account_display' => $payout->bank_account_display,
                'reference_number' => $payout->reference_number,
                'processed_at' => $payout->processed_at?->format('M j, Y'),
                'created_at' => $payout->created_at->format('M j, Y'),
            ]);

        // Monthly earnings breakdown (last 6 months)
        // Use database-agnostic approach
        $monthlyPayments = Payment::forProvider($provider->id)
            ->completed()
            ->where('paid_at', '>=', now()->subMonths(6))
            ->get(['paid_at', 'provider_amount']);

        $monthlyEarnings = $monthlyPayments
            ->groupBy(fn ($payment) => $payment->paid_at->format('Y-m'))
            ->map(fn ($payments, $month) => [
                'month' => $month,
                'month_label' => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                'total' => (float) $payments->sum('provider_amount'),
                'total_display' => '$' . number_format($payments->sum('provider_amount'), 2),
                'transaction_count' => $payments->count(),
            ])
            ->sortBy('month')
            ->values();

        return Inertia::render('Provider/Payments/Index', [
            'summary' => [
                'total_earnings' => $totalEarnings,
                'total_earnings_display' => '$' . number_format($totalEarnings, 2),
                'pending_payout' => $pendingPayout,
                'pending_payout_display' => '$' . number_format($pendingPayout, 2),
                'total_paid_out' => $totalPaidOut,
                'total_paid_out_display' => '$' . number_format($totalPaidOut, 2),
            ],
            'recentPayments' => $recentPayments,
            'payouts' => $payouts,
            'monthlyEarnings' => $monthlyEarnings,
        ]);
    }

    /**
     * Show detailed payment history.
     */
    public function payments(Request $request): Response
    {
        $provider = $request->user()->provider;
        $status = $request->get('status', 'all');

        $query = Payment::forProvider($provider->id)
            ->with(['booking:id,uuid,booking_date,start_time', 'booking.service:id,name', 'client:id,name'])
            ->orderByDesc('created_at');

        if ($status !== 'all' && PaymentStatus::tryFrom($status)) {
            $query->status(PaymentStatus::from($status));
        }

        $payments = $query->paginate(20)->withQueryString();

        $payments->getCollection()->transform(fn ($payment) => [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'booking_uuid' => $payment->booking->uuid,
            'client_name' => $payment->client->name,
            'service_name' => $payment->booking->service->name,
            'booking_date' => $payment->booking->formatted_date,
            'amount' => $payment->amount,
            'amount_display' => $payment->amount_display,
            'platform_fee' => $payment->platform_fee,
            'platform_fee_display' => $payment->platform_fee_display,
            'provider_amount' => $payment->provider_amount,
            'provider_amount_display' => $payment->provider_amount_display,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'status_color' => $payment->status->color(),
            'card_display' => $payment->card_display,
            'paid_at' => $payment->paid_at?->format('M j, Y g:i A'),
            'created_at' => $payment->created_at->format('M j, Y g:i A'),
        ]);

        // Get counts
        $counts = [
            'all' => Payment::forProvider($provider->id)->count(),
            'completed' => Payment::forProvider($provider->id)->status(PaymentStatus::COMPLETED)->count(),
            'pending' => Payment::forProvider($provider->id)->status(PaymentStatus::PENDING)->count(),
            'failed' => Payment::forProvider($provider->id)->status(PaymentStatus::FAILED)->count(),
        ];

        return Inertia::render('Provider/Payments/History', [
            'payments' => $payments,
            'currentStatus' => $status,
            'counts' => $counts,
            'statusOptions' => PaymentStatus::options(),
        ]);
    }

    /**
     * Show payout details.
     */
    public function showPayout(Request $request, string $uuid): Response
    {
        $provider = $request->user()->provider;

        $payout = Payout::where('uuid', $uuid)
            ->where('provider_id', $provider->id)
            ->with(['payments', 'processedBy:id,name'])
            ->firstOrFail();

        return Inertia::render('Provider/Payments/PayoutShow', [
            'payout' => [
                'id' => $payout->id,
                'uuid' => $payout->uuid,
                'amount' => $payout->amount,
                'amount_display' => $payout->amount_display,
                'period_start' => $payout->period_start->format('M j, Y'),
                'period_end' => $payout->period_end->format('M j, Y'),
                'period_display' => $payout->period_display,
                'payout_method' => $payout->payout_method,
                'bank_account_display' => $payout->bank_account_display,
                'reference_number' => $payout->reference_number,
                'status' => $payout->status->value,
                'status_label' => $payout->status->label(),
                'status_color' => $payout->status->color(),
                'notes' => $payout->notes,
                'processed_by' => $payout->processedBy?->name,
                'processed_at' => $payout->processed_at?->format('M j, Y g:i A'),
                'created_at' => $payout->created_at->format('M j, Y g:i A'),
                'payments_count' => $payout->payments->count(),
            ],
            'payments' => $payout->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'provider_amount' => $payment->provider_amount,
                'provider_amount_display' => $payment->provider_amount_display,
                'paid_at' => $payment->paid_at?->format('M j, Y'),
            ]),
        ]);
    }
}

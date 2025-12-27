<?php

namespace App\Domains\Payment\Controllers;

use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Enums\PayoutStatus;
use App\Domains\Payment\Enums\ScheduledPayoutStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\Payout;
use App\Domains\Payment\Models\ProviderGatewayConfig;
use App\Domains\Payment\Models\ScheduledPayout;
use App\Domains\Payment\Services\LedgerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProviderEarningsController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService,
    ) {}
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

        // Get this month's earnings
        $thisMonthEarnings = Payment::forProvider($provider->id)
            ->completed()
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('provider_amount');

        // Get wallet balance for escrow mode
        $balanceSummary = $this->ledgerService->getBalanceSummary($provider->id);

        // Check if provider has gateway configured
        $gatewayConfig = ProviderGatewayConfig::forProvider($provider->id)
            ->with('gateway')
            ->primary()
            ->first();

        $hasGateway = $gatewayConfig && $gatewayConfig->isVerified();
        $gatewayMode = $gatewayConfig?->gateway?->supports_split ? 'split' : 'escrow';

        return Inertia::render('Provider/Payments/Index', [
            'summary' => [
                'total_earnings' => $totalEarnings,
                'total_earnings_display' => '$' . number_format($totalEarnings, 2),
                'pending_payout' => $pendingPayout,
                'pending_payout_display' => '$' . number_format($pendingPayout, 2),
                'total_paid_out' => $totalPaidOut,
                'total_paid_out_display' => '$' . number_format($totalPaidOut, 2),
                'this_month' => $thisMonthEarnings,
                'this_month_display' => '$' . number_format($thisMonthEarnings, 2),
                'available_balance' => $balanceSummary['available'],
                'available_balance_display' => '$' . number_format($balanceSummary['available'], 2),
            ],
            'recentPayments' => $recentPayments,
            'payouts' => $payouts,
            'monthlyEarnings' => $monthlyEarnings,
            'hasGatewayConfigured' => $hasGateway,
            'gatewayMode' => $hasGateway ? $gatewayMode : null,
            'gatewayName' => $gatewayConfig?->gateway?->name,
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

    /**
     * Display wallet and ledger information.
     */
    public function wallet(Request $request): Response
    {
        $provider = $request->user()->provider;
        $type = $request->get('type', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get balance summary
        $balanceSummary = $this->ledgerService->getBalanceSummary($provider->id);

        // Get payout settings (from provider or defaults)
        $payoutSettings = $this->getPayoutSettings($provider);

        // Query ledger entries
        $query = LedgerEntry::forProvider($provider->id)
            ->orderByDesc('created_at');

        // Filter by type
        if ($type !== 'all' && LedgerEntryType::tryFrom($type)) {
            $query->ofType(LedgerEntryType::from($type));
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $ledgerEntries = $query->with(['booking:id,uuid', 'payment:id,uuid'])
            ->paginate(20)
            ->withQueryString();

        $ledgerEntries->getCollection()->transform(fn ($entry) => [
            'id' => $entry->id,
            'uuid' => $entry->uuid,
            'type' => $entry->type->value,
            'type_label' => $entry->type->label(),
            'type_icon' => $entry->type->icon(),
            'type_color' => $entry->type->color(),
            'amount' => $entry->amount,
            'amount_display' => $entry->amount_display,
            'balance_after' => $entry->balance_after,
            'balance_after_display' => $entry->balance_after_display,
            'description' => $entry->description,
            'booking_uuid' => $entry->booking?->uuid,
            'payment_uuid' => $entry->payment?->uuid,
            'created_at' => $entry->created_at->format('M j, Y g:i A'),
        ]);

        // Get scheduled payouts
        $scheduledPayouts = ScheduledPayout::forProvider($provider->id)
            ->whereIn('status', [ScheduledPayoutStatus::PENDING, ScheduledPayoutStatus::PROCESSING])
            ->orderBy('scheduled_for')
            ->take(5)
            ->get()
            ->map(fn ($payout) => [
                'id' => $payout->id,
                'uuid' => $payout->uuid,
                'amount' => $payout->amount,
                'amount_display' => $payout->amount_display,
                'status' => $payout->status->value,
                'status_label' => $payout->status->label(),
                'status_color' => $payout->status->color(),
                'scheduled_for' => $payout->scheduled_for->format('M j, Y'),
                'can_cancel' => $payout->canBeCancelled(),
            ]);

        // Check if provider has gateway configured
        $hasGateway = ProviderGatewayConfig::forProvider($provider->id)
            ->verified()
            ->exists();

        return Inertia::render('Provider/Payments/Wallet', [
            'balance' => [
                'total' => $balanceSummary['total'],
                'total_display' => '$' . number_format($balanceSummary['total'], 2),
                'available' => $balanceSummary['available'],
                'available_display' => '$' . number_format($balanceSummary['available'], 2),
                'held' => $balanceSummary['held'],
                'held_display' => '$' . number_format($balanceSummary['held'], 2),
                'pending_payout' => $balanceSummary['pending_payout'],
                'pending_payout_display' => '$' . number_format($balanceSummary['pending_payout'], 2),
            ],
            'payoutSettings' => $payoutSettings,
            'ledgerEntries' => $ledgerEntries,
            'scheduledPayouts' => $scheduledPayouts,
            'hasGateway' => $hasGateway,
            'filters' => [
                'type' => $type,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'typeOptions' => [
                ['value' => 'all', 'label' => 'All Transactions'],
                ['value' => 'credit', 'label' => 'Credits'],
                ['value' => 'debit', 'label' => 'Debits'],
                ['value' => 'hold', 'label' => 'Holds'],
                ['value' => 'release', 'label' => 'Releases'],
            ],
        ]);
    }

    /**
     * Request a manual payout.
     */
    public function requestPayout(Request $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        // Check if provider has verified gateway
        $hasGateway = ProviderGatewayConfig::forProvider($provider->id)
            ->verified()
            ->exists();

        if (!$hasGateway) {
            return back()->withErrors(['payout' => 'Please set up a payment method first.']);
        }

        // Get available balance
        $balanceSummary = $this->ledgerService->getBalanceSummary($provider->id);

        $validated = $request->validate([
            'amount' => [
                'nullable',
                'numeric',
                'min:10', // Minimum payout amount
                'max:' . $balanceSummary['available'],
            ],
        ]);

        $amount = $validated['amount'] ?? $balanceSummary['available'];

        if ($amount <= 0) {
            return back()->withErrors(['payout' => 'No funds available for payout.']);
        }

        // Check for existing pending payout
        $hasPendingPayout = ScheduledPayout::forProvider($provider->id)
            ->pending()
            ->exists();

        if ($hasPendingPayout) {
            return back()->withErrors(['payout' => 'You already have a pending payout request.']);
        }

        // Create scheduled payout
        ScheduledPayout::create([
            'provider_id' => $provider->id,
            'amount' => $amount,
            'currency' => 'USD', // TODO: Get from provider settings
            'scheduled_for' => now()->addBusinessDay(), // Process next business day
            'status' => ScheduledPayoutStatus::PENDING,
            'payout_method' => 'bank_transfer', // TODO: Get from provider settings
        ]);

        return back()->with('success', 'Payout request of $' . number_format($amount, 2) . ' submitted successfully.');
    }

    /**
     * Update payout schedule settings.
     */
    public function updatePayoutSchedule(Request $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        $validated = $request->validate([
            'schedule' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'minimum_amount' => 'required|numeric|min:10|max:10000',
        ]);

        // Update provider payout settings
        // Assuming provider has a settings column or related model
        $provider->update([
            'payout_schedule' => $validated['schedule'],
            'payout_minimum' => $validated['minimum_amount'],
        ]);

        return back()->with('success', 'Payout settings updated successfully.');
    }

    /**
     * Cancel a scheduled payout.
     */
    public function cancelPayout(Request $request, string $uuid): RedirectResponse
    {
        $provider = $request->user()->provider;

        $payout = ScheduledPayout::forProvider($provider->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (!$payout->canBeCancelled()) {
            return back()->withErrors(['payout' => 'This payout cannot be cancelled.']);
        }

        $payout->markAsCancelled('Cancelled by provider');

        return back()->with('success', 'Payout cancelled successfully.');
    }

    /**
     * Get payout settings for the provider.
     */
    private function getPayoutSettings($provider): array
    {
        // Get next scheduled payout date
        $nextPayout = ScheduledPayout::forProvider($provider->id)
            ->pending()
            ->orderBy('scheduled_for')
            ->first();

        return [
            'schedule' => $provider->payout_schedule ?? 'weekly',
            'schedule_label' => $this->getScheduleLabel($provider->payout_schedule ?? 'weekly'),
            'minimum_amount' => $provider->payout_minimum ?? 50,
            'minimum_amount_display' => '$' . number_format($provider->payout_minimum ?? 50, 2),
            'next_payout_date' => $nextPayout?->scheduled_for->format('M j, Y'),
        ];
    }

    /**
     * Get human-readable schedule label.
     */
    private function getScheduleLabel(string $schedule): string
    {
        return match ($schedule) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => 'Weekly',
        };
    }
}

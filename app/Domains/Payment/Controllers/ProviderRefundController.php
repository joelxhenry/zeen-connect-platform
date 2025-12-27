<?php

namespace App\Domains\Payment\Controllers;

use App\Domains\Payment\Actions\ProcessPaymentAction;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderRefundController extends Controller
{
    public function __construct(
        private ProcessPaymentAction $processPaymentAction,
    ) {}

    /**
     * Display list of refunds for the provider.
     */
    public function index(Request $request): Response
    {
        $provider = $request->user()->provider;
        $status = $request->get('status', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Payment::forProvider($provider->id)
            ->whereIn('status', [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED])
            ->with(['booking:id,uuid,booking_date,start_time', 'booking.service:id,name', 'client:id,name'])
            ->orderByDesc('refunded_at');

        // Filter by refund status
        if ($status === 'refunded') {
            $query->where('status', PaymentStatus::REFUNDED);
        } elseif ($status === 'partial') {
            $query->where('status', PaymentStatus::PARTIALLY_REFUNDED);
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('refunded_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('refunded_at', '<=', $dateTo);
        }

        $refunds = $query->paginate(20)->withQueryString();

        $refunds->getCollection()->transform(fn ($payment) => $this->formatRefund($payment));

        // Get stats
        $stats = $this->getRefundStats($provider->id);

        return Inertia::render('Provider/Payments/Refunds', [
            'refunds' => $refunds,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statusOptions' => [
                ['value' => 'all', 'label' => 'All Refunds'],
                ['value' => 'refunded', 'label' => 'Full Refunds'],
                ['value' => 'partial', 'label' => 'Partial Refunds'],
            ],
        ]);
    }

    /**
     * Initiate a refund for a payment.
     */
    public function store(Request $request, string $uuid): RedirectResponse
    {
        $provider = $request->user()->provider;

        $payment = Payment::forProvider($provider->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (!$payment->canBeRefunded()) {
            return back()->withErrors(['refund' => 'This payment cannot be refunded.']);
        }

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01|max:' . $payment->amount,
            'reason' => 'required|string|max:500',
        ]);

        $refundAmount = $validated['amount'] ?? null; // null = full refund

        $result = $this->processPaymentAction->refund(
            $payment,
            $refundAmount,
            $validated['reason']
        );

        if ($result['success']) {
            $amountDisplay = $refundAmount
                ? '$' . number_format($refundAmount, 2)
                : $payment->amount_display;

            return back()->with('success', "Refund of {$amountDisplay} processed successfully.");
        }

        return back()->withErrors(['refund' => $result['error'] ?? 'Failed to process refund.']);
    }

    /**
     * Get refundable payments for the provider.
     */
    public function refundable(Request $request): Response
    {
        $provider = $request->user()->provider;

        $payments = Payment::forProvider($provider->id)
            ->completed()
            ->whereNull('refunded_at')
            ->with(['booking:id,uuid,booking_date,start_time', 'booking.service:id,name', 'client:id,name'])
            ->orderByDesc('paid_at')
            ->paginate(20);

        $payments->getCollection()->transform(fn ($payment) => [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'booking_uuid' => $payment->booking->uuid,
            'client_name' => $payment->client->name,
            'service_name' => $payment->booking->service->name,
            'booking_date' => $payment->booking->formatted_date,
            'amount' => $payment->amount,
            'amount_display' => $payment->amount_display,
            'paid_at' => $payment->paid_at->format('M j, Y g:i A'),
            'can_refund' => $payment->canBeRefunded(),
        ]);

        return Inertia::render('Provider/Payments/RefundablePayments', [
            'payments' => $payments,
        ]);
    }

    /**
     * Format a refund record for the frontend.
     */
    private function formatRefund(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'booking_uuid' => $payment->booking->uuid,
            'client_name' => $payment->client->name,
            'service_name' => $payment->booking->service->name,
            'booking_date' => $payment->booking->formatted_date,
            'original_amount' => $payment->amount,
            'original_amount_display' => $payment->amount_display,
            'provider_amount' => $payment->provider_amount,
            'provider_amount_display' => $payment->provider_amount_display,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'status_color' => $payment->status->color(),
            'refund_reason' => $payment->refund_reason,
            'refund_transaction_id' => $payment->refund_transaction_id,
            'is_partial' => $payment->status === PaymentStatus::PARTIALLY_REFUNDED,
            'paid_at' => $payment->paid_at?->format('M j, Y g:i A'),
            'refunded_at' => $payment->refunded_at?->format('M j, Y g:i A'),
        ];
    }

    /**
     * Get refund statistics for the provider.
     */
    private function getRefundStats(int $providerId): array
    {
        $refundedPayments = Payment::forProvider($providerId)
            ->whereIn('status', [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED])
            ->get();

        $totalPayments = Payment::forProvider($providerId)
            ->whereIn('status', [
                PaymentStatus::COMPLETED,
                PaymentStatus::REFUNDED,
                PaymentStatus::PARTIALLY_REFUNDED,
            ])
            ->count();

        $totalRefundCount = $refundedPayments->count();
        $totalRefundedAmount = $refundedPayments->sum('amount');
        $refundRate = $totalPayments > 0 ? ($totalRefundCount / $totalPayments) * 100 : 0;

        return [
            'total_count' => $totalRefundCount,
            'total_amount' => $totalRefundedAmount,
            'total_amount_display' => '$' . number_format($totalRefundedAmount, 2),
            'refund_rate' => round($refundRate, 1),
            'refund_rate_display' => number_format($refundRate, 1) . '%',
        ];
    }
}

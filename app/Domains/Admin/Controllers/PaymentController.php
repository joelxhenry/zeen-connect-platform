<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): Response
    {
        $query = Payment::query()
            ->with([
                'client:id,name,email',
                'provider:id,business_name',
                'booking:id,uuid',
            ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Search by client name, provider name, or transaction ID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('gateway_transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('provider', function ($q) use ($search) {
                        $q->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $payments = $query->paginate(20)->withQueryString();

        $payments->getCollection()->transform(fn ($payment) => [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'client' => [
                'name' => $payment->client->name,
                'email' => $payment->client->email,
            ],
            'provider' => [
                'business_name' => $payment->provider->business_name,
            ],
            'booking_uuid' => $payment->booking->uuid,
            'amount' => $payment->amount_display,
            'platform_fee' => $payment->platform_fee_display,
            'provider_amount' => $payment->provider_amount_display,
            'gateway' => $payment->gateway,
            'card_display' => $payment->card_display,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'paid_at' => $payment->paid_at?->format('M d, Y H:i'),
            'created_at' => $payment->created_at->format('M d, Y'),
        ]);

        // Calculate totals
        $totals = [
            'total_amount' => Payment::completed()->sum('amount'),
            'total_fees' => Payment::completed()->sum('platform_fee'),
            'total_provider' => Payment::completed()->sum('provider_amount'),
            'pending_count' => Payment::pending()->count(),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'totals' => $totals,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
            'statuses' => PaymentStatus::options(),
        ]);
    }

    /**
     * Display the specified payment.
     */
    public function show(string $uuid): Response
    {
        $payment = Payment::where('uuid', $uuid)
            ->with([
                'client:id,name,email,phone',
                'provider:id,business_name,slug',
                'booking:id,uuid,booking_date,start_time,end_time,status',
                'booking.service:id,name',
            ])
            ->firstOrFail();

        return Inertia::render('Admin/Payments/Show', [
            'payment' => [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'client' => [
                    'id' => $payment->client->id,
                    'name' => $payment->client->name,
                    'email' => $payment->client->email,
                    'phone' => $payment->client->phone,
                ],
                'provider' => [
                    'id' => $payment->provider->id,
                    'business_name' => $payment->provider->business_name,
                    'slug' => $payment->provider->slug,
                ],
                'booking' => [
                    'uuid' => $payment->booking->uuid,
                    'date' => $payment->booking->booking_date->format('M d, Y'),
                    'time' => date('g:i A', strtotime($payment->booking->start_time)),
                    'service' => $payment->booking->service->name,
                    'status' => $payment->booking->status->value,
                ],
                'amount' => $payment->amount_display,
                'platform_fee' => $payment->platform_fee_display,
                'provider_amount' => $payment->provider_amount_display,
                'currency' => $payment->currency,
                'gateway' => $payment->gateway,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
                'gateway_order_id' => $payment->gateway_order_id,
                'gateway_response_code' => $payment->gateway_response_code,
                'card_display' => $payment->card_display,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'failure_reason' => $payment->failure_reason,
                'paid_at' => $payment->paid_at?->format('M d, Y H:i'),
                'refunded_at' => $payment->refunded_at?->format('M d, Y H:i'),
                'created_at' => $payment->created_at->format('M d, Y H:i'),
            ],
        ]);
    }
}

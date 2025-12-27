<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Resources\PaymentResource;
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

        $payments->getCollection()->transform(
            fn ($payment) => (new PaymentResource($payment))
                ->withClient(true)
                ->withProvider(true)
                ->withBooking(true)
                ->resolve()
        );

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
                'provider:id,uuid,business_name,slug',
                'booking:id,uuid,booking_date,start_time,end_time,status',
                'booking.service:id,name',
            ])
            ->firstOrFail();

        return Inertia::render('Admin/Payments/Show', [
            'payment' => (new PaymentResource($payment))
                ->withClient(true)
                ->withProvider(true)
                ->withBooking(true)
                ->withGatewayDetails(true)
                ->resolve(),
        ]);
    }
}

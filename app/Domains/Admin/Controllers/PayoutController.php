<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Payment\Enums\PayoutStatus;
use App\Domains\Payment\Models\Payout;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    /**
     * Display a listing of payouts.
     */
    public function index(Request $request): Response
    {
        $query = Payout::query()
            ->with([
                'provider:id,business_name',
                'processedBy:id,name',
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

        // Search by provider name or reference number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('provider', function ($q) use ($search) {
                        $q->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $payouts = $query->paginate(20)->withQueryString();

        $payouts->getCollection()->transform(fn ($payout) => [
            'id' => $payout->id,
            'uuid' => $payout->uuid,
            'provider' => [
                'business_name' => $payout->provider->business_name,
            ],
            'amount' => $payout->amount_display,
            'period' => $payout->period_display,
            'payout_method' => $payout->payout_method,
            'bank_account' => $payout->bank_account_display,
            'reference_number' => $payout->reference_number,
            'status' => $payout->status->value,
            'status_label' => $payout->status->label(),
            'processed_by' => $payout->processedBy?->name,
            'processed_at' => $payout->processed_at?->format('M d, Y H:i'),
            'created_at' => $payout->created_at->format('M d, Y'),
        ]);

        // Calculate totals
        $totals = [
            'pending_amount' => Payout::pending()->sum('amount'),
            'pending_count' => Payout::pending()->count(),
            'completed_amount' => Payout::completed()->sum('amount'),
            'completed_count' => Payout::completed()->count(),
        ];

        return Inertia::render('Admin/Payouts/Index', [
            'payouts' => $payouts,
            'totals' => $totals,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
            'statuses' => PayoutStatus::options(),
        ]);
    }

    /**
     * Display the specified payout.
     */
    public function show(string $uuid): Response
    {
        $payout = Payout::where('uuid', $uuid)
            ->with([
                'provider:id,business_name,slug',
                'provider.user:id,name,email',
                'processedBy:id,name,email',
                'payments',
            ])
            ->firstOrFail();

        return Inertia::render('Admin/Payouts/Show', [
            'payout' => [
                'id' => $payout->id,
                'uuid' => $payout->uuid,
                'provider' => [
                    'id' => $payout->provider->id,
                    'business_name' => $payout->provider->business_name,
                    'slug' => $payout->provider->slug,
                    'owner_name' => $payout->provider->user->name,
                    'owner_email' => $payout->provider->user->email,
                ],
                'amount' => $payout->amount_display,
                'currency' => $payout->currency,
                'period_start' => $payout->period_start->format('M d, Y'),
                'period_end' => $payout->period_end->format('M d, Y'),
                'period' => $payout->period_display,
                'payout_method' => $payout->payout_method,
                'bank_name' => $payout->bank_name,
                'bank_account' => $payout->bank_account_display,
                'reference_number' => $payout->reference_number,
                'status' => $payout->status->value,
                'status_label' => $payout->status->label(),
                'notes' => $payout->notes,
                'processed_by' => $payout->processedBy ? [
                    'name' => $payout->processedBy->name,
                    'email' => $payout->processedBy->email,
                ] : null,
                'processed_at' => $payout->processed_at?->format('M d, Y H:i'),
                'created_at' => $payout->created_at->format('M d, Y H:i'),
                'payments_count' => $payout->payments->count(),
            ],
            'statuses' => PayoutStatus::options(),
        ]);
    }

    /**
     * Update payout status (process payout).
     */
    public function updateStatus(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:processing,completed,failed',
            'reference_number' => 'required_if:status,completed|nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $payout = Payout::where('uuid', $uuid)->firstOrFail();
        $newStatus = PayoutStatus::from($request->status);

        if ($newStatus === PayoutStatus::PROCESSING) {
            $payout->markAsProcessing(auth()->user());
        } elseif ($newStatus === PayoutStatus::COMPLETED) {
            $payout->markAsCompleted($request->reference_number);
        } elseif ($newStatus === PayoutStatus::FAILED) {
            $payout->markAsFailed($request->notes ?? 'Payout failed');
        }

        return back()->with('success', 'Payout status updated successfully.');
    }
}

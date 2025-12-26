<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * Display a listing of bookings.
     */
    public function index(Request $request): Response
    {
        $query = Booking::query()
            ->with([
                'client:id,name,email',
                'provider:id,business_name',
                'service:id,name',
            ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('booking_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('booking_date', '<=', $request->date_to);
        }

        // Search by client name, provider name, or booking UUID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
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

        $bookings = $query->paginate(20)->withQueryString();

        $bookings->getCollection()->transform(fn($booking) => [
            'id' => $booking->id,
            'uuid' => $booking->uuid,
            'client' => [
                'name' => $booking->getClientName(),
                'email' => $booking->getClientEmail(),
            ],
            'provider' => [
                'business_name' => $booking->provider->business_name,
            ],
            'service' => [
                'name' => $booking->service->name,
            ],
            'booking_date' => $booking->booking_date->format('M d, Y'),
            'start_time' => date('g:i A', strtotime($booking->start_time)),
            'end_time' => date('g:i A', strtotime($booking->end_time)),
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'total_amount' => $booking->total_display,
            'created_at' => $booking->created_at->format('M d, Y'),
        ]);

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
            'statuses' => BookingStatus::options(),
        ]);
    }

    /**
     * Display the specified booking.
     */
    public function show(string $uuid): Response
    {
        $booking = Booking::where('uuid', $uuid)
            ->with([
                'client:id,name,email,phone,avatar',
                'provider:id,user_id,business_name,slug',
                'provider.user:id,name,email',
                'service:id,name,duration_minutes',
                'review',
            ])
            ->firstOrFail();

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'client' => [
                    'id' => $booking->client->id,
                    'name' => $booking->getClientName(),
                    'email' => $booking->getClientEmail(),
                    'phone' => $booking->client?->phone,
                    'avatar' => $booking->client?->avatar,
                ],
                'provider' => [
                    'id' => $booking->provider->id,
                    'business_name' => $booking->provider->business_name,
                    'slug' => $booking->provider->slug,
                    'owner_name' => $booking->provider->user->name,
                    'owner_email' => $booking->provider->user->email,
                ],
                'service' => [
                    'id' => $booking->service->id,
                    'name' => $booking->service->name,
                    'duration' => $booking->service->duration_minutes . ' min',
                ],
                'booking_date' => $booking->booking_date->format('l, M d, Y'),
                'start_time' => date('g:i A', strtotime($booking->start_time)),
                'end_time' => date('g:i A', strtotime($booking->end_time)),
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'service_price' => '$' . number_format($booking->service_price, 2),
                'platform_fee' => '$' . number_format($booking->platform_fee, 2),
                'total_amount' => $booking->total_display,
                'client_notes' => $booking->client_notes,
                'provider_notes' => $booking->provider_notes,
                'cancellation_reason' => $booking->cancellation_reason,
                'confirmed_at' => $booking->confirmed_at?->format('M d, Y H:i'),
                'completed_at' => $booking->completed_at?->format('M d, Y H:i'),
                'cancelled_at' => $booking->cancelled_at?->format('M d, Y H:i'),
                'created_at' => $booking->created_at->format('M d, Y H:i'),
                'has_review' => $booking->review !== null,
                'review' => $booking->review ? [
                    'rating' => $booking->review->rating,
                    'comment' => $booking->review->comment,
                    'created_at' => $booking->review->created_at->format('M d, Y'),
                ] : null,
            ],
            'statuses' => BookingStatus::options(),
        ]);
    }

    /**
     * Update booking status.
     */
    public function updateStatus(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', BookingStatus::values()),
        ]);

        $booking = Booking::where('uuid', $uuid)->firstOrFail();

        $newStatus = BookingStatus::from($request->status);

        $data = ['status' => $newStatus];

        // Set timestamps based on status
        if ($newStatus === BookingStatus::CONFIRMED && ! $booking->confirmed_at) {
            $data['confirmed_at'] = now();
        } elseif ($newStatus === BookingStatus::COMPLETED && ! $booking->completed_at) {
            $data['completed_at'] = now();
        } elseif ($newStatus === BookingStatus::CANCELLED && ! $booking->cancelled_at) {
            $data['cancelled_at'] = now();
            $data['cancellation_reason'] = $request->input('reason', 'Cancelled by admin');
        }

        $booking->update($data);

        return back()->with('success', 'Booking status updated successfully.');
    }
}

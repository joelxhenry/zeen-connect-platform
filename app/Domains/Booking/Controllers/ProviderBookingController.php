<?php

namespace App\Domains\Booking\Controllers;

use App\Domains\Booking\Actions\UpdateBookingStatusAction;
use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Requests\UpdateBookingStatusRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderBookingController extends Controller
{
    /**
     * Display provider's booking list.
     */
    public function index(Request $request): Response
    {
        $provider = $request->user()->provider;
        $status = $request->get('status', 'all');
        $date = $request->get('date');

        $query = Booking::forProvider($provider->id)
            ->with([
                'client:id,name,email,phone,avatar',
                'service:id,name,duration_minutes',
            ]);

        // Filter by status
        if ($status !== 'all' && BookingStatus::tryFrom($status)) {
            $query->status(BookingStatus::from($status));
        }

        // Filter by date
        if ($date) {
            $query->onDate($date);
        }

        // Default ordering
        if ($status === 'pending') {
            $query->orderBy('booking_date')->orderBy('start_time');
        } else {
            $query->orderByDesc('booking_date')->orderByDesc('start_time');
        }

        $bookings = $query->paginate(15)->withQueryString();

        // Transform for frontend
        $bookings->getCollection()->transform(fn ($booking) => [
            'id' => $booking->id,
            'uuid' => $booking->uuid,
            'client' => [
                'name' => $booking->client->name,
                'email' => $booking->client->email,
                'phone' => $booking->client->phone,
                'avatar' => $booking->client->avatar,
            ],
            'service' => [
                'name' => $booking->service->name,
                'duration_minutes' => $booking->service->duration_minutes,
            ],
            'booking_date' => $booking->booking_date->format('Y-m-d'),
            'formatted_date' => $booking->formatted_date,
            'formatted_time' => $booking->formatted_time,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'status_color' => $booking->status->color(),
            'total_display' => $booking->total_display,
            'client_notes' => $booking->client_notes,
            'is_past' => $booking->isPast(),
            'is_today' => $booking->isToday(),
            'can_confirm' => $booking->canBeConfirmed(),
            'can_complete' => $booking->canBeCompleted(),
            'can_cancel' => $booking->canBeCancelled(),
        ]);

        // Get counts for status tabs
        $counts = [
            'all' => Booking::forProvider($provider->id)->count(),
            'pending' => Booking::forProvider($provider->id)->status(BookingStatus::PENDING)->count(),
            'confirmed' => Booking::forProvider($provider->id)->status(BookingStatus::CONFIRMED)->count(),
            'completed' => Booking::forProvider($provider->id)->status(BookingStatus::COMPLETED)->count(),
            'cancelled' => Booking::forProvider($provider->id)->status(BookingStatus::CANCELLED)->count(),
        ];

        return Inertia::render('Provider/Bookings/Index', [
            'bookings' => $bookings,
            'currentStatus' => $status,
            'currentDate' => $date,
            'counts' => $counts,
            'statusOptions' => BookingStatus::options(),
        ]);
    }

    /**
     * Show a specific booking.
     */
    public function show(Request $request, string $uuid): Response
    {
        $provider = $request->user()->provider;

        $booking = Booking::where('uuid', $uuid)
            ->where('provider_id', $provider->id)
            ->with([
                'client:id,name,email,phone,avatar',
                'service:id,name,description,duration_minutes,price',
            ])
            ->firstOrFail();

        return Inertia::render('Provider/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'client' => [
                    'name' => $booking->client->name,
                    'email' => $booking->client->email,
                    'phone' => $booking->client->phone,
                    'avatar' => $booking->client->avatar,
                ],
                'service' => [
                    'name' => $booking->service->name,
                    'description' => $booking->service->description,
                    'duration_minutes' => $booking->service->duration_minutes,
                    'price' => $booking->service->price,
                ],
                'booking_date' => $booking->booking_date->format('Y-m-d'),
                'formatted_date' => $booking->formatted_date,
                'formatted_time' => $booking->formatted_time,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_color' => $booking->status->color(),
                'service_price' => $booking->service_price,
                'platform_fee' => $booking->platform_fee,
                'total_amount' => $booking->total_amount,
                'total_display' => $booking->total_display,
                'client_notes' => $booking->client_notes,
                'provider_notes' => $booking->provider_notes,
                'cancellation_reason' => $booking->cancellation_reason,
                'is_past' => $booking->isPast(),
                'is_today' => $booking->isToday(),
                'can_confirm' => $booking->canBeConfirmed(),
                'can_complete' => $booking->canBeCompleted(),
                'can_cancel' => $booking->canBeCancelled(),
                'confirmed_at' => $booking->confirmed_at?->format('M j, Y g:i A'),
                'completed_at' => $booking->completed_at?->format('M j, Y g:i A'),
                'cancelled_at' => $booking->cancelled_at?->format('M j, Y g:i A'),
                'created_at' => $booking->created_at->format('M j, Y g:i A'),
            ],
        ]);
    }

    /**
     * Update booking status.
     */
    public function updateStatus(
        UpdateBookingStatusRequest $request,
        string $uuid,
        UpdateBookingStatusAction $action
    ): RedirectResponse {
        $provider = $request->user()->provider;

        $booking = Booking::where('uuid', $uuid)
            ->where('provider_id', $provider->id)
            ->firstOrFail();

        try {
            $action->execute(
                $booking,
                BookingStatus::from($request->status),
                $request->reason,
                $request->provider_notes
            );

            $statusLabel = BookingStatus::from($request->status)->label();

            return back()->with('success', "Booking marked as {$statusLabel}.");
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    /**
     * Quick confirm a booking.
     */
    public function confirm(Request $request, string $uuid, UpdateBookingStatusAction $action): RedirectResponse
    {
        $provider = $request->user()->provider;

        $booking = Booking::where('uuid', $uuid)
            ->where('provider_id', $provider->id)
            ->firstOrFail();

        try {
            $action->execute($booking, BookingStatus::CONFIRMED);

            return back()->with('success', 'Booking confirmed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    /**
     * Quick complete a booking.
     */
    public function complete(Request $request, string $uuid, UpdateBookingStatusAction $action): RedirectResponse
    {
        $provider = $request->user()->provider;

        $booking = Booking::where('uuid', $uuid)
            ->where('provider_id', $provider->id)
            ->firstOrFail();

        try {
            $action->execute($booking, BookingStatus::COMPLETED);

            return back()->with('success', 'Booking marked as completed.');
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }
}

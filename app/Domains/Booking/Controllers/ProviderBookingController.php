<?php

namespace App\Domains\Booking\Controllers;

use App\Domains\Booking\Actions\UpdateBookingStatusAction;
use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Requests\UpdateBookingStatusRequest;
use App\Domains\Booking\Resources\BookingResource;
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

        // Transform for frontend using BookingResource
        $bookings->getCollection()->transform(
            fn ($booking) => (new BookingResource($booking))
                ->withClient(true)
                ->withService(true)
                ->withProvider(false)
                ->resolve()
        );

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
            'current_status' => $status,
            'current_date' => $date,
            'counts' => $counts,
            'status_options' => BookingStatus::options(),
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
                'service:id,uuid,name,description,duration_minutes,price',
                'payment',
            ])
            ->firstOrFail();

        return Inertia::render('Provider/Bookings/Show', [
            'booking' => (new BookingResource($booking))
                ->withClient(true)
                ->withService(true)
                ->withProvider(false)
                ->withPayment(true)
                ->resolve(),
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

    /**
     * Cancel a booking (decline).
     */
    public function cancel(Request $request, string $uuid, UpdateBookingStatusAction $action): RedirectResponse
    {
        $provider = $request->user()->provider;

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = Booking::where('uuid', $uuid)
            ->where('provider_id', $provider->id)
            ->firstOrFail();

        try {
            $action->execute($booking, BookingStatus::CANCELLED, $request->reason);

            return back()->with('success', 'Booking cancelled successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    /**
     * Mark a booking as no-show.
     */
    public function noShow(Request $request, string $uuid, UpdateBookingStatusAction $action): RedirectResponse
    {
        $provider = $request->user()->provider;

        $booking = Booking::where('uuid', $uuid)
            ->where('provider_id', $provider->id)
            ->firstOrFail();

        try {
            $action->execute($booking, BookingStatus::NO_SHOW);

            return back()->with('success', 'Booking marked as no-show.');
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }
}

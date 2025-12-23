<?php

namespace App\Domains\Booking\Controllers;

use App\Domains\Booking\Actions\CreateBookingAction;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Requests\StoreBookingRequest;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientBookingController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Display client's booking history.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $status = $request->get('status', 'all');

        $query = Booking::forClient($user->id)
            ->with([
                'provider:id,business_name,slug',
                'provider.user:id,avatar',
                'service:id,name,duration_minutes',
            ]);

        if ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'past') {
            $query->past();
        } else {
            $query->orderByDesc('booking_date')->orderByDesc('start_time');
        }

        $bookings = $query->paginate(10)->withQueryString();

        // Transform for frontend
        $bookings->getCollection()->transform(fn ($booking) => [
            'id' => $booking->id,
            'uuid' => $booking->uuid,
            'provider' => [
                'business_name' => $booking->provider->business_name,
                'slug' => $booking->provider->slug,
                'avatar' => $booking->provider->user?->avatar,
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
            'is_past' => $booking->isPast(),
            'is_today' => $booking->isToday(),
            'can_cancel' => $booking->canBeCancelled(),
        ]);

        return Inertia::render('Client/Bookings/Index', [
            'bookings' => $bookings,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Show the booking creation page.
     */
    public function create(Request $request): Response
    {
        $provider = Provider::where('slug', $request->provider)
            ->active()
            ->with([
                'user:id,name,avatar',
                'primaryLocation.region',
                'services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'services.category:id,name,icon',
            ])
            ->firstOrFail();

        // Get available dates for the next 30 days
        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays(30)->format('Y-m-d');
        $availableDates = $this->availabilityService->getAvailableDates($provider, $startDate, $endDate);

        return Inertia::render('Booking/Create', [
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'slug' => $provider->slug,
                'avatar' => $provider->user?->avatar,
                'location' => $provider->primaryLocation?->display_name,
            ],
            'services' => $provider->services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'duration_display' => $service->duration_display,
                'price' => $service->price,
                'price_display' => $service->price_display,
                'category' => [
                    'id' => $service->category->id,
                    'name' => $service->category->name,
                    'icon' => $service->category->icon,
                ],
            ]),
            'availableDates' => $availableDates,
            'preselectedService' => $request->service ? (int) $request->service : null,
        ]);
    }

    /**
     * Get available time slots for a date and service.
     */
    public function getSlots(Request $request): JsonResponse
    {
        $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $provider = Provider::findOrFail($request->provider_id);
        $service = Service::findOrFail($request->service_id);

        $slots = $this->availabilityService->getAvailableSlots($provider, $service, $request->date);

        return response()->json(['slots' => $slots]);
    }

    /**
     * Store a new booking.
     */
    public function store(StoreBookingRequest $request, CreateBookingAction $action): RedirectResponse
    {
        $provider = Provider::findOrFail($request->provider_id);
        $service = Service::findOrFail($request->service_id);

        try {
            $booking = $action->execute(
                $request->user(),
                $provider,
                $service,
                $request->date,
                $request->start_time,
                $request->notes
            );

            return redirect()
                ->route('client.bookings.show', $booking->uuid)
                ->with('success', 'Booking created successfully! Awaiting provider confirmation.');
        } catch (\Exception $e) {
            return back()->withErrors(['slot' => $e->getMessage()]);
        }
    }

    /**
     * Show a specific booking.
     */
    public function show(string $uuid): Response
    {
        $booking = Booking::where('uuid', $uuid)
            ->where('client_id', auth()->id())
            ->with([
                'provider:id,business_name,slug,address',
                'provider.user:id,name,avatar,email',
                'provider.primaryLocation.region',
                'service:id,name,description,duration_minutes',
            ])
            ->firstOrFail();

        return Inertia::render('Client/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'provider' => [
                    'business_name' => $booking->provider->business_name,
                    'slug' => $booking->provider->slug,
                    'avatar' => $booking->provider->user?->avatar,
                    'location' => $booking->provider->primaryLocation?->display_name,
                    'address' => $booking->provider->address,
                ],
                'service' => [
                    'name' => $booking->service->name,
                    'description' => $booking->service->description,
                    'duration_minutes' => $booking->service->duration_minutes,
                ],
                'booking_date' => $booking->booking_date->format('Y-m-d'),
                'formatted_date' => $booking->formatted_date,
                'formatted_time' => $booking->formatted_time,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_color' => $booking->status->color(),
                'service_price' => $booking->service_price,
                'total_amount' => $booking->total_amount,
                'total_display' => $booking->total_display,
                'client_notes' => $booking->client_notes,
                'provider_notes' => $booking->provider_notes,
                'cancellation_reason' => $booking->cancellation_reason,
                'is_past' => $booking->isPast(),
                'is_today' => $booking->isToday(),
                'can_cancel' => $booking->canBeCancelled(),
                'confirmed_at' => $booking->confirmed_at?->format('M j, Y g:i A'),
                'completed_at' => $booking->completed_at?->format('M j, Y g:i A'),
                'cancelled_at' => $booking->cancelled_at?->format('M j, Y g:i A'),
                'created_at' => $booking->created_at->format('M j, Y g:i A'),
            ],
        ]);
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = Booking::where('uuid', $uuid)
            ->where('client_id', auth()->id())
            ->firstOrFail();

        if (! $booking->canBeCancelled()) {
            return back()->withErrors(['booking' => 'This booking cannot be cancelled.']);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason,
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Booking cancelled successfully.');
    }
}

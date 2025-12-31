<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Event\Enums\EventStatus;
use App\Domains\Event\Enums\OccurrenceStatus;
use App\Domains\Event\Models\Event;
use App\Domains\Event\Models\EventOccurrence;
use App\Domains\Service\Models\Category;
use App\Domains\Service\Models\Service;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $provider = $user->provider;

        // Filter parameters
        $status = $request->get('status');
        $serviceId = $request->get('service_id');
        $categoryId = $request->get('category_id');
        $teamMemberId = $request->get('team_member_id');
        $search = $request->get('search');
        $dateRange = $request->get('date_range', 'upcoming');

        // Build booking query
        $query = Booking::with(['client', 'service.category', 'teamMember'])
            ->where('provider_id', $provider->id);

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Apply date range filter
        $today = now()->toDateString();
        switch ($dateRange) {
            case 'today':
                $query->whereDate('booking_date', $today);
                break;
            case 'week':
                $query->whereBetween('booking_date', [
                    now()->startOfWeek()->toDateString(),
                    now()->endOfWeek()->toDateString()
                ]);
                break;
            case 'month':
                $query->whereBetween('booking_date', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString()
                ]);
                break;
            case 'upcoming':
            default:
                $query->where('booking_date', '>=', $today);
                break;
        }

        // Apply service filter
        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        // Apply category filter
        if ($categoryId) {
            $query->whereHas('service', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        // Apply team member filter
        if ($teamMemberId) {
            $query->where('team_member_id', $teamMemberId);
        }

        // Apply search filter (client name, guest name, service name, or category name)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('guest_name', 'like', "%{$search}%")
                ->orWhereHas('service', function ($serviceQuery) use ($search) {
                    $serviceQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('service.category', function ($categoryQuery) use ($search) {
                    $categoryQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Order by date and time
        $query->orderBy('booking_date')
            ->orderBy('start_time');

        // Paginate results
        $bookings = $query->paginate(20)->through(fn($booking) => [
            'uuid' => $booking->uuid,
            'client' => [
                'name' => $booking->client?->name ?? $booking->guest_name,
                'email' => $booking->client?->email ?? $booking->guest_email,
                'phone' => $booking->client?->phone ?? $booking->guest_phone,
                'avatar' => $booking->client?->avatar,
            ],
            'service' => [
                'id' => $booking->service->id,
                'name' => $booking->service->name,
                'duration' => $booking->service->duration_minutes,
                'category' => $booking->service->category?->name,
                'category_id' => $booking->service->category_id,
            ],
            'team_member' => $booking->teamMember ? [
                'id' => $booking->teamMember->id,
                'name' => $booking->teamMember->name,
            ] : null,
            'booking_date' => $booking->booking_date->toDateString(),
            'date' => $booking->booking_date->format('l, M j'),
            'date_short' => $booking->booking_date->format('M j'),
            'time' => $booking->start_time->format('g:i A'),
            'end_time' => $booking->end_time->format('g:i A'),
            'total_amount' => $booking->total_display,
            'service_price' => $booking->service_price,
            'deposit_amount' => $booking->deposit_amount,
            'deposit_paid' => $booking->deposit_paid,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'status_color' => $booking->status->color(),
            'provider_notes' => $booking->provider_notes,
            'client_notes' => $booking->client_notes,
            'created_at' => $booking->created_at->format('M j, Y g:i A'),
        ]);

        // Group bookings by date for display
        $groupedBookings = collect($bookings->items())->groupBy('booking_date')->map(function ($items, $date) {
            $carbonDate = Carbon::parse($date);
            $isToday = $carbonDate->isToday();
            $isTomorrow = $carbonDate->isTomorrow();

            return [
                'date' => $date,
                'label' => $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $carbonDate->format('l, M j')),
                'is_today' => $isToday,
                'is_tomorrow' => $isTomorrow,
                'bookings' => $items->values(),
            ];
        })->values();

        // Status counts for tabs
        $baseQuery = Booking::where('provider_id', $provider->id)
            ->where('booking_date', '>=', $today);

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', BookingStatus::PENDING)->count(),
            'confirmed' => (clone $baseQuery)->where('status', BookingStatus::CONFIRMED)->count(),
            'completed' => Booking::where('provider_id', $provider->id)
                ->where('status', BookingStatus::COMPLETED)
                ->count(),
            'cancelled' => Booking::where('provider_id', $provider->id)
                ->where('status', BookingStatus::CANCELLED)
                ->count(),
        ];

        // Get services for filter dropdown
        $services = Service::where('provider_id', $provider->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get categories for filter dropdown
        $categories = Category::whereHas('services', function ($query) use ($provider) {
            $query->where('provider_id', $provider->id)->where('is_active', true);
        })->orderBy('name')->get(['id', 'name']);

        // Get team members if provider has team feature
        $teamMembers = $provider->teamMembers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Event filter parameters
        $eventDateRange = $request->get('event_date_range', 'upcoming');

        // Build event occurrences query
        $eventQuery = EventOccurrence::with(['event.media', 'event.categories'])
            ->whereHas('event', function ($q) use ($provider) {
                $q->where('provider_id', $provider->id)
                    ->where('status', EventStatus::PUBLISHED)
                    ->where('is_active', true);
            })
            ->where('status', OccurrenceStatus::SCHEDULED);

        // Apply event date range filter
        switch ($eventDateRange) {
            case 'today':
                $eventQuery->whereDate('start_datetime', $today);
                break;
            case 'week':
                $eventQuery->whereBetween('start_datetime', [
                    now()->startOfWeek(),
                    now()->endOfWeek()->endOfDay(),
                ]);
                break;
            case 'month':
                $eventQuery->whereBetween('start_datetime', [
                    now()->startOfMonth(),
                    now()->endOfMonth()->endOfDay(),
                ]);
                break;
            case 'upcoming':
            default:
                $eventQuery->where('start_datetime', '>=', now());
                break;
        }

        // Order and paginate event occurrences
        $eventOccurrences = $eventQuery
            ->orderBy('start_datetime')
            ->limit(50)
            ->get()
            ->map(function ($occurrence) {
                $event = $occurrence->event;
                $coverMedia = $event->getMedia('cover')->first();

                return [
                    'uuid' => $occurrence->uuid,
                    'event_uuid' => $event->uuid,
                    'event_name' => $event->name,
                    'event_price' => $event->price_display,
                    'event_location' => $event->location_display,
                    'event_location_type' => $event->location_type->value,
                    'event_type' => $event->event_type->value,
                    'cover_image' => $coverMedia?->getUrl('thumb'),
                    'start_datetime' => $occurrence->start_datetime->toIso8601String(),
                    'end_datetime' => $occurrence->end_datetime->toIso8601String(),
                    'date' => $occurrence->start_datetime->format('l, M j'),
                    'date_short' => $occurrence->start_datetime->format('M j'),
                    'time' => $occurrence->start_datetime->format('g:i A'),
                    'end_time' => $occurrence->end_datetime->format('g:i A'),
                    'capacity' => $occurrence->getCapacity(),
                    'spots_remaining' => $occurrence->spots_remaining,
                    'is_full' => $occurrence->isFull(),
                    'status' => $occurrence->status->value,
                    'categories' => $event->categories->map(fn ($c) => $c->name)->toArray(),
                ];
            });

        // Group event occurrences by date
        $groupedEvents = $eventOccurrences->groupBy(function ($occurrence) {
            return Carbon::parse($occurrence['start_datetime'])->toDateString();
        })->map(function ($items, $date) {
            $carbonDate = Carbon::parse($date);
            $isToday = $carbonDate->isToday();
            $isTomorrow = $carbonDate->isTomorrow();

            return [
                'date' => $date,
                'label' => $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $carbonDate->format('l, M j')),
                'is_today' => $isToday,
                'is_tomorrow' => $isTomorrow,
                'occurrences' => $items->values(),
            ];
        })->values();

        // Event stats
        $eventStats = [
            'total_events' => Event::where('provider_id', $provider->id)
                ->where('status', EventStatus::PUBLISHED)
                ->where('is_active', true)
                ->count(),
            'upcoming_occurrences' => EventOccurrence::whereHas('event', function ($q) use ($provider) {
                $q->where('provider_id', $provider->id)
                    ->where('status', EventStatus::PUBLISHED)
                    ->where('is_active', true);
            })->where('status', OccurrenceStatus::SCHEDULED)
                ->where('start_datetime', '>=', now())
                ->count(),
            'today' => EventOccurrence::whereHas('event', function ($q) use ($provider) {
                $q->where('provider_id', $provider->id)
                    ->where('status', EventStatus::PUBLISHED)
                    ->where('is_active', true);
            })->where('status', OccurrenceStatus::SCHEDULED)
                ->whereDate('start_datetime', $today)
                ->count(),
            'this_week' => EventOccurrence::whereHas('event', function ($q) use ($provider) {
                $q->where('provider_id', $provider->id)
                    ->where('status', EventStatus::PUBLISHED)
                    ->where('is_active', true);
            })->where('status', OccurrenceStatus::SCHEDULED)
                ->whereBetween('start_datetime', [
                    now()->startOfWeek(),
                    now()->endOfWeek()->endOfDay(),
                ])
                ->count(),
        ];

        return Inertia::render('Provider/Dashboard', [
            'provider' => [
                'business_name' => $provider->business_name,
                'slug' => $provider->slug,
            ],
            'bookings' => $bookings,
            'grouped_bookings' => $groupedBookings,
            'status_counts' => $statusCounts,
            'filters' => [
                'status' => $status,
                'service_id' => $serviceId,
                'category_id' => $categoryId,
                'team_member_id' => $teamMemberId,
                'search' => $search,
                'date_range' => $dateRange,
            ],
            'services' => $services,
            'categories' => $categories,
            'team_members' => $teamMembers,
            // Event data
            'grouped_events' => $groupedEvents,
            'event_stats' => $eventStats,
            'event_filters' => [
                'date_range' => $eventDateRange,
            ],
        ]);
    }
}

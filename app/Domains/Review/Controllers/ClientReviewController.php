<?php

namespace App\Domains\Review\Controllers;

use App\Domains\Booking\Models\Booking;
use App\Domains\Review\Actions\CreateReviewAction;
use App\Domains\Review\Models\Review;
use App\Domains\Review\Requests\StoreReviewRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientReviewController extends Controller
{
    /**
     * Display list of client's reviews.
     */
    public function index(Request $request): Response
    {
        $reviews = Review::forClient($request->user()->id)
            ->with([
                'provider:id,business_name,slug',
                'service:id,name',
                'booking:id,uuid,booking_date',
            ])
            ->recent()
            ->paginate(10);

        $reviews->getCollection()->transform(fn ($review) => [
            'id' => $review->id,
            'uuid' => $review->uuid,
            'provider' => [
                'name' => $review->provider->business_name,
                'slug' => $review->provider->slug,
            ],
            'service_name' => $review->service->name,
            'booking_uuid' => $review->booking->uuid,
            'booking_date' => $review->booking->booking_date->format('M j, Y'),
            'rating' => $review->rating,
            'rating_stars' => $review->rating_stars,
            'comment' => $review->comment,
            'provider_response' => $review->provider_response,
            'formatted_date' => $review->formatted_date,
            'time_ago' => $review->time_ago,
        ]);

        return Inertia::render('Client/Reviews/Index', [
            'reviews' => $reviews,
        ]);
    }

    /**
     * Show the form to create a review.
     */
    public function create(Request $request, string $bookingUuid): Response
    {
        $booking = Booking::where('uuid', $bookingUuid)
            ->where('client_id', $request->user()->id)
            ->with([
                'provider:id,business_name,slug',
                'service:id,name',
            ])
            ->firstOrFail();

        if (! $booking->canBeReviewed()) {
            return redirect()->route('client.bookings.show', $booking->uuid)
                ->with('error', 'This booking cannot be reviewed.');
        }

        return Inertia::render('Client/Reviews/Create', [
            'booking' => [
                'uuid' => $booking->uuid,
                'provider' => [
                    'name' => $booking->provider->business_name,
                    'slug' => $booking->provider->slug,
                ],
                'service_name' => $booking->service->name,
                'formatted_date' => $booking->formatted_date,
                'formatted_time' => $booking->formatted_time,
            ],
        ]);
    }

    /**
     * Store a new review.
     */
    public function store(
        StoreReviewRequest $request,
        string $bookingUuid,
        CreateReviewAction $action
    ): RedirectResponse {
        $booking = Booking::where('uuid', $bookingUuid)
            ->where('client_id', $request->user()->id)
            ->firstOrFail();

        try {
            $action->execute(
                $booking,
                $request->user(),
                $request->rating,
                $request->comment
            );

            return redirect()->route('client.bookings.show', $booking->uuid)
                ->with('success', 'Thank you for your review!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

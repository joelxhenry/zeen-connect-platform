<?php

namespace App\Domains\Review\Controllers;

use App\Domains\Review\Models\Review;
use App\Domains\Review\Requests\RespondToReviewRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderReviewController extends Controller
{
    /**
     * Display provider's reviews.
     */
    public function index(Request $request): Response
    {
        $provider = $request->user()->provider;
        $filter = $request->get('filter', 'all');

        $query = Review::forProvider($provider->id)
            ->with([
                'client:id,name,avatar',
                'service:id,name',
                'booking:id,uuid,booking_date',
            ])
            ->recent();

        // Apply rating filter
        if ($filter !== 'all' && is_numeric($filter)) {
            $query->withRating((int) $filter);
        }

        $reviews = $query->paginate(10)->withQueryString();

        $reviews->getCollection()->transform(fn ($review) => [
            'id' => $review->id,
            'uuid' => $review->uuid,
            'client' => [
                'name' => $review->client->name,
                'avatar' => $review->client->avatar,
            ],
            'service_name' => $review->service->name,
            'booking_uuid' => $review->booking->uuid,
            'booking_date' => $review->booking->booking_date->format('M j, Y'),
            'rating' => $review->rating,
            'rating_stars' => $review->rating_stars,
            'comment' => $review->comment,
            'provider_response' => $review->provider_response,
            'provider_responded_at' => $review->provider_responded_at?->format('M j, Y'),
            'can_respond' => $review->canBeRespondedTo(),
            'is_visible' => $review->is_visible,
            'formatted_date' => $review->formatted_date,
            'time_ago' => $review->time_ago,
        ]);

        // Get rating distribution
        $ratingDistribution = Review::forProvider($provider->id)
            ->visible()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Fill missing ratings with 0
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = $ratingDistribution[$i] ?? 0;
        }

        // Summary stats
        $stats = [
            'total' => $provider->rating_count,
            'average' => $provider->rating_avg,
            'average_display' => $provider->rating_display,
            'distribution' => $distribution,
        ];

        return Inertia::render('Provider/Reviews/Index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'currentFilter' => $filter,
        ]);
    }

    /**
     * Respond to a review.
     */
    public function respond(RespondToReviewRequest $request, string $uuid): RedirectResponse
    {
        $provider = $request->user()->provider;

        $review = Review::where('uuid', $uuid)
            ->where('provider_id', $provider->id)
            ->firstOrFail();

        if (! $review->canBeRespondedTo()) {
            return back()->withErrors(['response' => 'You have already responded to this review.']);
        }

        $review->addProviderResponse($request->response);

        return back()->with('success', 'Response added successfully.');
    }
}

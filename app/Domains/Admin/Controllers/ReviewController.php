<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Review\Models\Review;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews.
     */
    public function index(Request $request): Response
    {
        $query = Review::query()
            ->with([
                'client:id,name,email,avatar',
                'provider:id,business_name',
                'service:id,name',
            ]);

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by visibility
        if ($request->filled('visibility')) {
            $query->where('is_visible', $request->visibility === 'visible');
        }

        // Filter by flagged status
        if ($request->filled('flagged')) {
            $query->where('is_flagged', $request->flagged === 'yes');
        }

        // Search by client name, provider name, or comment
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
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

        $reviews = $query->paginate(20)->withQueryString();

        $reviews->getCollection()->transform(fn ($review) => [
            'id' => $review->id,
            'uuid' => $review->uuid,
            'client' => [
                'name' => $review->client->name,
                'email' => $review->client->email,
                'avatar' => $review->client->avatar,
            ],
            'provider' => [
                'business_name' => $review->provider->business_name,
            ],
            'service' => [
                'name' => $review->service->name,
            ],
            'rating' => $review->rating,
            'comment' => $review->comment,
            'has_response' => $review->hasProviderResponse(),
            'is_visible' => $review->is_visible,
            'is_flagged' => $review->is_flagged,
            'flag_reason' => $review->flag_reason,
            'created_at' => $review->created_at->format('M d, Y'),
        ]);

        // Calculate stats
        $stats = [
            'total' => Review::count(),
            'average_rating' => round(Review::avg('rating'), 1),
            'flagged_count' => Review::where('is_flagged', true)->count(),
            'hidden_count' => Review::where('is_visible', false)->count(),
        ];

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search,
                'rating' => $request->rating,
                'visibility' => $request->visibility,
                'flagged' => $request->flagged,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
        ]);
    }

    /**
     * Display the specified review.
     */
    public function show(string $uuid): Response
    {
        $review = Review::where('uuid', $uuid)
            ->with([
                'client:id,name,email,phone,avatar',
                'provider:id,business_name,slug',
                'service:id,name',
                'booking:id,uuid,booking_date,start_time',
            ])
            ->firstOrFail();

        return Inertia::render('Admin/Reviews/Show', [
            'review' => [
                'id' => $review->id,
                'uuid' => $review->uuid,
                'client' => [
                    'id' => $review->client->id,
                    'name' => $review->client->name,
                    'email' => $review->client->email,
                    'phone' => $review->client->phone,
                    'avatar' => $review->client->avatar,
                ],
                'provider' => [
                    'id' => $review->provider->id,
                    'business_name' => $review->provider->business_name,
                    'slug' => $review->provider->slug,
                ],
                'service' => [
                    'id' => $review->service->id,
                    'name' => $review->service->name,
                ],
                'booking' => [
                    'uuid' => $review->booking->uuid,
                    'date' => $review->booking->booking_date->format('M d, Y'),
                    'time' => date('g:i A', strtotime($review->booking->start_time)),
                ],
                'rating' => $review->rating,
                'comment' => $review->comment,
                'provider_response' => $review->provider_response,
                'provider_responded_at' => $review->provider_responded_at?->format('M d, Y H:i'),
                'is_visible' => $review->is_visible,
                'is_flagged' => $review->is_flagged,
                'flag_reason' => $review->flag_reason,
                'created_at' => $review->created_at->format('M d, Y H:i'),
            ],
        ]);
    }

    /**
     * Toggle review visibility.
     */
    public function toggleVisibility(string $uuid): RedirectResponse
    {
        $review = Review::where('uuid', $uuid)->firstOrFail();

        if ($review->is_visible) {
            $review->hide();
            $message = 'Review hidden successfully.';
        } else {
            $review->show();
            $message = 'Review is now visible.';
        }

        return back()->with('success', $message);
    }

    /**
     * Flag a review.
     */
    public function flag(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $review = Review::where('uuid', $uuid)->firstOrFail();
        $review->flag($request->reason);

        return back()->with('success', 'Review flagged successfully.');
    }

    /**
     * Unflag a review.
     */
    public function unflag(string $uuid): RedirectResponse
    {
        $review = Review::where('uuid', $uuid)->firstOrFail();

        $review->update([
            'is_flagged' => false,
            'flag_reason' => null,
        ]);

        return back()->with('success', 'Review unflagged successfully.');
    }

    /**
     * Delete a review.
     */
    public function destroy(string $uuid): RedirectResponse
    {
        $review = Review::where('uuid', $uuid)->firstOrFail();
        $review->delete();

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Review deleted successfully.');
    }
}

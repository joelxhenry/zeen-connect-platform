<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Provider\Models\Provider;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    /**
     * Display the list of favorite providers.
     */
    public function index(Request $request): Response
    {
        $favorites = $request->user()
            ->favoriteProviders()
            ->with(['user', 'services' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->map(fn ($provider) => [
                'id' => $provider->id,
                'uuid' => $provider->uuid,
                'slug' => $provider->slug,
                'business_name' => $provider->business_name,
                'tagline' => $provider->tagline,
                'avatar' => $provider->user->avatar,
                'rating_avg' => $provider->rating_avg,
                'rating_count' => $provider->rating_count,
                'services_count' => $provider->services->count(),
                'is_featured' => $provider->is_featured,
            ]);

        return Inertia::render('Client/Favorites/Index', [
            'favorites' => $favorites,
        ]);
    }

    /**
     * Toggle favorite status for a provider.
     */
    public function toggle(Request $request, string $slug): JsonResponse
    {
        $provider = Provider::where('slug', $slug)->firstOrFail();
        $isFavorited = $request->user()->toggleFavorite($provider);

        return response()->json([
            'is_favorited' => $isFavorited,
            'message' => $isFavorited
                ? 'Added to favorites'
                : 'Removed from favorites',
        ]);
    }

    /**
     * Remove a provider from favorites.
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $provider = Provider::where('slug', $slug)->firstOrFail();
        $request->user()->favoriteProviders()->detach($provider->id);

        return response()->json([
            'message' => 'Removed from favorites',
        ]);
    }
}

<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Location\Models\Location;
use App\Domains\Location\Models\Region;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index(Request $request): Response
    {
        $query = Location::query()
            ->with(['region:id,name,country_id', 'region.country:id,name'])
            ->withCount('providers');

        // Filter by region
        if ($request->filled('region')) {
            $query->where('region_id', $request->region);
        }

        // Filter by active status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('region', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'name');
        $sortDir = $request->get('dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $locations = $query->paginate(20)->withQueryString();

        $locations->getCollection()->transform(fn ($location) => [
            'id' => $location->id,
            'uuid' => $location->uuid,
            'name' => $location->name,
            'slug' => $location->slug,
            'region' => [
                'id' => $location->region->id,
                'name' => $location->region->name,
                'country' => $location->region->country->name,
            ],
            'is_active' => $location->is_active,
            'providers_count' => $location->providers_count,
            'created_at' => $location->created_at->format('M d, Y'),
        ]);

        // Get regions for filter dropdown
        $regions = Region::with('country:id,name')
            ->select('id', 'name', 'country_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($region) => [
                'id' => $region->id,
                'name' => $region->name,
                'country' => $region->country->name,
            ]);

        return Inertia::render('Admin/Locations/Index', [
            'locations' => $locations,
            'regions' => $regions,
            'filters' => [
                'search' => $request->search,
                'region' => $request->region,
                'status' => $request->status,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
        ]);
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'region_id' => 'required|exists:regions,id',
            'is_active' => 'boolean',
        ]);

        Location::create([
            'name' => $request->name,
            'region_id' => $request->region_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Location created successfully.');
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, string $uuid): RedirectResponse
    {
        $location = Location::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:100',
            'region_id' => 'required|exists:regions,id',
            'is_active' => 'boolean',
        ]);

        $location->update([
            'name' => $request->name,
            'region_id' => $request->region_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Location updated successfully.');
    }

    /**
     * Toggle location active status.
     */
    public function toggleStatus(string $uuid): RedirectResponse
    {
        $location = Location::where('uuid', $uuid)->firstOrFail();

        $location->update(['is_active' => ! $location->is_active]);

        $status = $location->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Location {$status} successfully.");
    }

    /**
     * Delete the specified location.
     */
    public function destroy(string $uuid): RedirectResponse
    {
        $location = Location::where('uuid', $uuid)->firstOrFail();

        // Check if location has providers
        if ($location->providers()->exists() || $location->primaryProviders()->exists()) {
            return back()->with('error', 'Cannot delete location with existing providers.');
        }

        $location->delete();

        return back()->with('success', 'Location deleted successfully.');
    }
}

<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Industry\Models\Industry;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndustryController extends Controller
{
    /**
     * Display a listing of industries.
     */
    public function index(Request $request): Response
    {
        $industries = Industry::query()
            ->withCount('providers')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($industry) => [
                'id' => $industry->id,
                'uuid' => $industry->uuid,
                'name' => $industry->name,
                'slug' => $industry->slug,
                'icon' => $industry->icon,
                'description' => $industry->description,
                'is_active' => $industry->is_active,
                'sort_order' => $industry->sort_order,
                'providers_count' => $industry->providers_count,
            ]);

        $stats = [
            'total' => $industries->count(),
            'active' => $industries->where('is_active', true)->count(),
            'inactive' => $industries->where('is_active', false)->count(),
        ];

        return Inertia::render('Admin/Industries/Index', [
            'industries' => $industries,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created industry.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:industries,name',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Industry::create([
            'name' => $request->name,
            'icon' => $request->icon,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return back()->with('success', 'Industry created successfully.');
    }

    /**
     * Update the specified industry.
     */
    public function update(Request $request, string $uuid): RedirectResponse
    {
        $industry = Industry::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:100|unique:industries,name,' . $industry->id,
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $industry->update([
            'name' => $request->name,
            'icon' => $request->icon,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return back()->with('success', 'Industry updated successfully.');
    }

    /**
     * Toggle industry active status.
     */
    public function toggleStatus(string $uuid): RedirectResponse
    {
        $industry = Industry::where('uuid', $uuid)->firstOrFail();

        $industry->update(['is_active' => ! $industry->is_active]);

        $status = $industry->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Industry {$status} successfully.");
    }

    /**
     * Delete the specified industry.
     */
    public function destroy(string $uuid): RedirectResponse
    {
        $industry = Industry::where('uuid', $uuid)->firstOrFail();

        // Check if industry has providers
        if ($industry->providers()->exists()) {
            return back()->with('error', 'Cannot delete industry with existing providers.');
        }

        $industry->delete();

        return back()->with('success', 'Industry deleted successfully.');
    }
}

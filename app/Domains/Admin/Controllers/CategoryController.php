<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Service\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): Response
    {
        $query = Category::query()
            ->withCount('services');

        // Filter by active status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Sort
        $sortBy = $request->get('sort', 'sort_order');
        $sortDir = $request->get('dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $categories = $query->paginate(20)->withQueryString();

        $categories->getCollection()->transform(fn ($category) => [
            'id' => $category->id,
            'uuid' => $category->uuid,
            'name' => $category->name,
            'slug' => $category->slug,
            'icon' => $category->icon,
            'description' => $category->description,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
            'services_count' => $category->services_count,
            'created_at' => $category->created_at->format('M d, Y'),
        ]);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Category::create([
            'name' => $request->name,
            'icon' => $request->icon,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return back()->with('success', 'Category created successfully.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, string $uuid): RedirectResponse
    {
        $category = Category::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category->update([
            'name' => $request->name,
            'icon' => $request->icon,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return back()->with('success', 'Category updated successfully.');
    }

    /**
     * Toggle category active status.
     */
    public function toggleStatus(string $uuid): RedirectResponse
    {
        $category = Category::where('uuid', $uuid)->firstOrFail();

        $category->update(['is_active' => ! $category->is_active]);

        $status = $category->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Category {$status} successfully.");
    }

    /**
     * Delete the specified category.
     */
    public function destroy(string $uuid): RedirectResponse
    {
        $category = Category::where('uuid', $uuid)->firstOrFail();

        // Check if category has services
        if ($category->services()->exists()) {
            return back()->with('error', 'Cannot delete category with existing services.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}

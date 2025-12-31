<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Provider\Requests\StoreCategoryRequest;
use App\Domains\Provider\Requests\UpdateCategoryRequest;
use App\Domains\Service\Enums\CategoryType;
use App\Domains\Service\Models\Category;
use App\Domains\Service\Resources\CategoryResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $provider = Auth::user()->provider;
        $type = $request->input('type', 'service');

        $categories = $provider->categories()
            ->when($type === 'service', fn ($q) => $q->forServices())
            ->when($type === 'event', fn ($q) => $q->forEvents())
            ->withCount('services')
            ->ordered()
            ->get();

        return Inertia::render('Provider/Categories/Index', [
            'categories' => $categories->map(fn ($c) => (new CategoryResource($c))->withCounts()->resolve()),
            'type' => $type,
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $provider = Auth::user()->provider;

        $category = $provider->categories()->create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'type' => $request->validated('type', CategoryType::SERVICE->value),
            'is_active' => $request->validated('is_active', true),
            'sort_order' => $request->validated('sort_order', 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'category' => (new CategoryResource($category))->resolve(),
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the category belongs to this provider
        if ($category->provider_id !== $provider->id) {
            abort(403);
        }

        $category->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active' => $request->validated('is_active', $category->is_active),
            'sort_order' => $request->validated('sort_order', $category->sort_order),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'category' => (new CategoryResource($category->fresh()))->resolve(),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the category belongs to this provider
        if ($category->provider_id !== $provider->id) {
            abort(403);
        }

        // Check if category has any attached services or events
        $usageCount = $category->services()->count();

        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete category. It is currently assigned to {$usageCount} service(s).",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $provider = Auth::user()->provider;
        $orderedIds = $request->input('ids', []);

        foreach ($orderedIds as $index => $id) {
            Category::where('id', $id)
                ->where('provider_id', $provider->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully.',
        ]);
    }

    /**
     * Get categories for a specific type (API endpoint for dropdowns).
     */
    public function list(Request $request): JsonResponse
    {
        $provider = Auth::user()->provider;
        $type = $request->input('type', 'service');

        $categories = $provider->categories()
            ->when($type === 'service', fn ($q) => $q->forServices())
            ->when($type === 'event', fn ($q) => $q->forEvents())
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'categories' => $categories->map(fn ($c) => (new CategoryResource($c))->resolve()),
        ]);
    }
}

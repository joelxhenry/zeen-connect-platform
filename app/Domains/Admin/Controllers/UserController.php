<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->with(['provider:id,user_id,business_name,status'])
            ->withCount(['favoriteProviders']);

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $users = $query->paginate(20)->withQueryString();

        $users->getCollection()->transform(fn ($user) => [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'is_active' => $user->is_active,
            'provider' => $user->provider ? [
                'business_name' => $user->provider->business_name,
                'status' => $user->provider->status,
            ] : null,
            'favorites_count' => $user->favorite_providers_count,
            'last_login_at' => $user->last_login_at?->diffForHumans(),
            'created_at' => $user->created_at->format('M d, Y'),
        ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->search,
                'role' => $request->role,
                'status' => $request->status,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
            'roles' => collect(UserRole::cases())->map(fn ($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(string $uuid): Response
    {
        $user = User::where('uuid', $uuid)
            ->with(['provider.primaryLocation', 'provider.services'])
            ->firstOrFail();

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'is_active' => $user->is_active,
                'notification_preferences' => $user->notification_preferences,
                'provider' => $user->provider ? [
                    'id' => $user->provider->id,
                    'uuid' => $user->provider->uuid,
                    'business_name' => $user->provider->business_name,
                    'slug' => $user->provider->slug,
                    'status' => $user->provider->status,
                    'location' => $user->provider->primaryLocation?->display_name,
                    'services_count' => $user->provider->services->count(),
                    'rating_avg' => $user->provider->rating_avg,
                    'total_bookings' => $user->provider->total_bookings,
                ] : null,
                'last_login_at' => $user->last_login_at?->format('M d, Y H:i'),
                'email_verified_at' => $user->email_verified_at?->format('M d, Y'),
                'created_at' => $user->created_at->format('M d, Y H:i'),
            ],
        ]);
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(string $uuid): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();

        // Prevent deactivating self
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$status} successfully.");
    }

    /**
     * Delete a user.
     */
    public function destroy(string $uuid): RedirectResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();

        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Soft delete user
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}

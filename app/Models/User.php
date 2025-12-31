<?php

namespace App\Models;

use App\Domains\User\Enums\UserRole;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuid, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'is_active',
        'preferred_location_id',
        'notification_preferences',
        // Social auth fields
        'google_id',
        'google_linked_at',
        'apple_id',
        'apple_linked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'notification_preferences' => 'array',
            // Social auth timestamps
            'google_linked_at' => 'datetime',
            'apple_linked_at' => 'datetime',
        ];
    }

    /**
     * Get the provider profile associated with the user.
     */
    public function provider(): HasOne
    {
        return $this->hasOne(\App\Domains\Provider\Models\Provider::class);
    }

    /**
     * Get the client profile associated with the user.
     */
    public function client(): HasOne
    {
        return $this->hasOne(\App\Domains\Client\Models\Client::class);
    }

    /**
     * Check if the user is a provider.
     */
    public function isProvider(): bool
    {
        return $this->role === UserRole::Provider;
    }

    /**
     * Check if the user is a client.
     */
    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Update the last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Get the user's favorite providers.
     */
    public function favoriteProviders(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domains\Provider\Models\Provider::class,
            'favorites',
            'user_id',
            'provider_id'
        )->withTimestamps();
    }

    /**
     * Check if the user has favorited a provider.
     */
    public function hasFavorited(\App\Domains\Provider\Models\Provider $provider): bool
    {
        return $this->favoriteProviders()->where('provider_id', $provider->id)->exists();
    }

    /**
     * Toggle favorite status for a provider.
     */
    public function toggleFavorite(\App\Domains\Provider\Models\Provider $provider): bool
    {
        if ($this->hasFavorited($provider)) {
            $this->favoriteProviders()->detach($provider->id);
            return false;
        }

        $this->favoriteProviders()->attach($provider->id);
        return true;
    }

    // =========================================================================
    // Social Authentication Methods
    // =========================================================================

    /**
     * Check if user registered via social login (has no password).
     */
    public function isSocialOnlyUser(): bool
    {
        return is_null($this->password);
    }

    /**
     * Check if user has a social account linked.
     */
    public function hasSocialAccountLinked(string $provider): bool
    {
        return match ($provider) {
            'google' => ! is_null($this->google_id),
            'apple' => ! is_null($this->apple_id),
            default => false,
        };
    }

    /**
     * Get all linked social providers.
     *
     * @return array<array{provider: string, linked_at: \Illuminate\Support\Carbon|null}>
     */
    public function getLinkedSocialProviders(): array
    {
        $providers = [];

        if ($this->google_id) {
            $providers[] = [
                'provider' => 'google',
                'linked_at' => $this->google_linked_at,
            ];
        }

        if ($this->apple_id) {
            $providers[] = [
                'provider' => 'apple',
                'linked_at' => $this->apple_linked_at,
            ];
        }

        return $providers;
    }

    // =========================================================================
    // Team Membership Methods
    // =========================================================================

    /**
     * Get all team memberships for this user.
     */
    public function teamMemberships(): HasMany
    {
        return $this->hasMany(\App\Domains\Provider\Models\TeamMember::class);
    }

    /**
     * Get only active team memberships.
     */
    public function activeTeamMemberships(): HasMany
    {
        return $this->teamMemberships()->active();
    }

    /**
     * Get all providers this user can access (owned or team member).
     *
     * @return \Illuminate\Support\Collection<\App\Domains\Provider\Models\Provider>
     */
    public function getAccessibleProviders(): \Illuminate\Support\Collection
    {
        $providers = collect();

        // Add owned provider
        if ($this->provider) {
            $providers->push($this->provider);
        }

        // Add providers from active team memberships
        $teamProviders = $this->activeTeamMemberships()
            ->with('provider')
            ->get()
            ->pluck('provider')
            ->filter();

        return $providers->merge($teamProviders)->unique('id');
    }

    /**
     * Check if this user can access a specific provider.
     */
    public function canAccessProvider(\App\Domains\Provider\Models\Provider $provider): bool
    {
        return $provider->hasAccess($this);
    }

    /**
     * Get this user's permissions for a specific provider.
     *
     * @return array<string>
     */
    public function getPermissionsForProvider(\App\Domains\Provider\Models\Provider $provider): array
    {
        return $provider->getPermissionsFor($this);
    }

    /**
     * Check if user has a specific permission for a provider.
     */
    public function hasProviderPermission(\App\Domains\Provider\Models\Provider $provider, string $permission): bool
    {
        return $provider->userHasPermission($this, $permission);
    }
}

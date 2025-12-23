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
}

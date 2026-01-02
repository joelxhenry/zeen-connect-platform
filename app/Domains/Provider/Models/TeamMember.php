<?php

namespace App\Domains\Provider\Models;

use App\Domains\Booking\Models\Booking;
use App\Domains\Provider\Enums\TeamMemberStatus;
use App\Domains\Provider\Enums\TeamPermission;
use App\Domains\Service\Models\Service;
use App\Models\User;
use App\Support\Traits\HasUuid;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class TeamMember extends Model
{
    use HasFactory, HasUuid;

    protected $attributes = [
        'permissions' => '[]',
    ];

    protected $fillable = [
        'provider_id',
        'user_id',
        'email',
        'name',
        'title',
        'permissions',
        'status',
        'invitation_token',
        'invited_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'status' => TeamMemberStatus::class,
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TeamMemberFactory
    {
        return TeamMemberFactory::new();
    }

    /**
     * Get the provider this team member belongs to.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the user account linked to this team member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team member's availability schedule.
     */
    public function availability(): HasMany
    {
        return $this->hasMany(TeamMemberAvailability::class);
    }

    /**
     * Get the team member's breaks.
     */
    public function breaks(): MorphMany
    {
        return $this->morphMany(AvailabilityBreak::class, 'scheduleable');
    }

    /**
     * Get the team member's blocked dates.
     */
    public function blockedDates(): HasMany
    {
        return $this->hasMany(TeamMemberBlockedDate::class);
    }

    /**
     * Get the bookings assigned to this team member.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the services this team member is assigned to.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_team_member')
            ->withTimestamps();
    }

    /**
     * Get the effective availability for a specific day of the week.
     * Returns provider defaults if use_provider_defaults is true.
     *
     * @return array{is_available: bool, start_time: ?string, end_time: ?string}|null
     */
    public function getEffectiveAvailabilityForDay(int $dayOfWeek): ?array
    {
        $availability = $this->availability()->forDay($dayOfWeek)->first();

        // If no custom availability record exists, use provider defaults
        if (! $availability) {
            return $this->getProviderAvailabilityForDay($dayOfWeek);
        }

        // If using provider defaults, get from provider
        if ($availability->use_provider_defaults) {
            return $this->getProviderAvailabilityForDay($dayOfWeek);
        }

        return [
            'is_available' => $availability->is_available,
            'start_time' => $availability->start_time,
            'end_time' => $availability->end_time,
        ];
    }

    /**
     * Get provider availability for a specific day.
     *
     * @return array{is_available: bool, start_time: ?string, end_time: ?string}|null
     */
    protected function getProviderAvailabilityForDay(int $dayOfWeek): ?array
    {
        $providerAvailability = $this->provider->availability()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (! $providerAvailability) {
            return null;
        }

        return [
            'is_available' => $providerAvailability->is_available,
            'start_time' => $providerAvailability->start_time,
            'end_time' => $providerAvailability->end_time,
        ];
    }

    /**
     * Check if the team member is available on a specific date.
     */
    public function isAvailableOnDate(\DateTimeInterface $date): bool
    {
        // Check provider blocked dates first
        if ($this->provider->blockedDates()->where('date', $date->format('Y-m-d'))->exists()) {
            return false;
        }

        // Check team member blocked dates
        if ($this->blockedDates()->where('date', $date->format('Y-m-d'))->exists()) {
            return false;
        }

        // Check availability for day of week
        $dayOfWeek = (int) $date->format('w');
        $availability = $this->getEffectiveAvailabilityForDay($dayOfWeek);

        return $availability && $availability['is_available'];
    }

    /**
     * Get breaks for a specific day (combines team member and provider breaks).
     *
     * @return \Illuminate\Support\Collection<AvailabilityBreak>
     */
    public function getBreaksForDay(int $dayOfWeek): \Illuminate\Support\Collection
    {
        // Get team member's own breaks
        $teamBreaks = $this->breaks()->forDay($dayOfWeek)->get();

        // Get provider breaks
        $providerBreaks = $this->provider->breaks()->forDay($dayOfWeek)->get();

        return $teamBreaks->merge($providerBreaks)->sortBy('start_time');
    }

    /**
     * Check if invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === TeamMemberStatus::PENDING;
    }

    /**
     * Check if team member is active.
     */
    public function isActive(): bool
    {
        return $this->status === TeamMemberStatus::ACTIVE;
    }

    /**
     * Check if team member is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === TeamMemberStatus::SUSPENDED;
    }

    /**
     * Check if this team member has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    /**
     * Check if this team member has any of the given permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this team member has all of the given permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Grant a permission to this team member.
     */
    public function grantPermission(string $permission): void
    {
        if (! TeamPermission::isValid($permission)) {
            return;
        }

        $permissions = $this->permissions ?? [];

        if (! in_array($permission, $permissions, true)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
        }
    }

    /**
     * Revoke a permission from this team member.
     */
    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_values(array_filter($permissions, fn ($p) => $p !== $permission));
        $this->permissions = $permissions;
        $this->save();
    }

    /**
     * Set all permissions for this team member.
     *
     * @param  array<string>  $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = TeamPermission::validate($permissions);
        $this->save();
    }

    /**
     * Generate a unique invitation token.
     */
    public function generateInvitationToken(): string
    {
        $token = Str::random(64);
        $this->invitation_token = $token;
        $this->invited_at = now();
        $this->save();

        return $token;
    }

    /**
     * Clear the invitation token after acceptance.
     */
    public function clearInvitationToken(): void
    {
        $this->invitation_token = null;
        $this->save();
    }

    /**
     * Mark the invitation as accepted and link the user.
     */
    public function acceptInvitation(User $user): void
    {
        $this->user_id = $user->id;
        $this->status = TeamMemberStatus::ACTIVE;
        $this->accepted_at = now();
        $this->invitation_token = null;
        $this->save();
    }

    /**
     * Suspend this team member.
     */
    public function suspend(): void
    {
        $this->status = TeamMemberStatus::SUSPENDED;
        $this->save();
    }

    /**
     * Reactivate this team member.
     */
    public function reactivate(): void
    {
        if ($this->user_id) {
            $this->status = TeamMemberStatus::ACTIVE;
        } else {
            $this->status = TeamMemberStatus::PENDING;
        }
        $this->save();
    }

    /**
     * Get the display name for this team member.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->name ?? $this->email;
    }

    /**
     * Get the avatar for this team member.
     */
    public function getAvatarAttribute(): ?string
    {
        return $this->user?->avatar;
    }

    /**
     * Get the permission count.
     */
    public function getPermissionCountAttribute(): int
    {
        return count($this->permissions ?? []);
    }

    /**
     * Get a summary of permissions for display.
     */
    public function getPermissionsSummaryAttribute(): string
    {
        $count = $this->permission_count;
        $total = count(TeamPermission::keys());

        if ($count === $total) {
            return 'Full access';
        }

        if ($count === 0) {
            return 'No permissions';
        }

        return "{$count} of {$total} permissions";
    }

    /**
     * Scope to only active team members.
     */
    public function scopeActive($query)
    {
        return $query->where('status', TeamMemberStatus::ACTIVE);
    }

    /**
     * Scope to only pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', TeamMemberStatus::PENDING);
    }

    /**
     * Scope to find by invitation token.
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('invitation_token', $token);
    }

    /**
     * Scope to find by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Check if the invitation has expired (older than 7 days).
     */
    public function isInvitationExpired(): bool
    {
        if (! $this->isPending() || ! $this->invited_at) {
            return false;
        }

        return $this->invited_at->addDays(7)->isPast();
    }
}

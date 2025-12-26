<?php

namespace App\Domains\Provider\Models;

use App\Domains\Provider\Enums\TeamMemberStatus;
use App\Domains\Provider\Enums\TeamPermission;
use App\Models\User;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeamMember extends Model
{
    use HasUuid;

    protected $fillable = [
        'provider_id',
        'user_id',
        'email',
        'name',
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

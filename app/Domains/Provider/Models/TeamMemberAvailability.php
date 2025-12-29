<?php

namespace App\Domains\Provider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMemberAvailability extends Model
{
    protected $table = 'team_member_availability';

    protected $fillable = [
        'team_member_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'use_provider_defaults',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_available' => 'boolean',
            'use_provider_defaults' => 'boolean',
        ];
    }

    /**
     * Get the team member this availability belongs to.
     */
    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    /**
     * Check if this day uses provider defaults.
     */
    public function usesProviderDefaults(): bool
    {
        return $this->use_provider_defaults;
    }

    /**
     * Get the day name.
     */
    public function getDayNameAttribute(): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return $days[$this->day_of_week] ?? '';
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): ?string
    {
        if (! $this->is_available || ! $this->start_time || ! $this->end_time) {
            return null;
        }

        $start = date('g:i A', strtotime($this->start_time));
        $end = date('g:i A', strtotime($this->end_time));

        return "{$start} - {$end}";
    }

    /**
     * Scope to filter by day of week.
     */
    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope to filter by team member.
     */
    public function scopeForTeamMember($query, int $teamMemberId)
    {
        return $query->where('team_member_id', $teamMemberId);
    }

    /**
     * Scope to get available days.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to get days using provider defaults.
     */
    public function scopeUsingProviderDefaults($query)
    {
        return $query->where('use_provider_defaults', true);
    }

    /**
     * Scope to get days with custom schedule.
     */
    public function scopeWithCustomSchedule($query)
    {
        return $query->where('use_provider_defaults', false);
    }
}

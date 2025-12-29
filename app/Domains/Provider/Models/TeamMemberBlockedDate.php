<?php

namespace App\Domains\Provider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMemberBlockedDate extends Model
{
    protected $fillable = [
        'team_member_id',
        'date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Get the team member this blocked date belongs to.
     */
    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    /**
     * Get formatted date display.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('D, M j, Y');
    }

    /**
     * Check if this date is in the past.
     */
    public function isPast(): bool
    {
        return $this->date->isPast();
    }

    /**
     * Check if this date is today.
     */
    public function isToday(): bool
    {
        return $this->date->isToday();
    }

    /**
     * Check if this date is in the future.
     */
    public function isFuture(): bool
    {
        return $this->date->isFuture();
    }

    /**
     * Scope to filter by team member.
     */
    public function scopeForTeamMember($query, int $teamMemberId)
    {
        return $query->where('team_member_id', $teamMemberId);
    }

    /**
     * Scope to filter by date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope to get future blocked dates.
     */
    public function scopeFuture($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    /**
     * Scope to get blocked dates in a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}

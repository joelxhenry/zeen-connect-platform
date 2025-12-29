<?php

namespace App\Domains\Provider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AvailabilityBreak extends Model
{
    protected $fillable = [
        'scheduleable_type',
        'scheduleable_id',
        'day_of_week',
        'start_time',
        'end_time',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    /**
     * Get the owning scheduleable model (Provider or TeamMember).
     */
    public function scheduleable(): MorphTo
    {
        return $this->morphTo();
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
    public function getTimeRangeAttribute(): string
    {
        $start = date('g:i A', strtotime($this->start_time));
        $end = date('g:i A', strtotime($this->end_time));

        return "{$start} - {$end}";
    }

    /**
     * Get the display label.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?? 'Break';
    }

    /**
     * Get duration in minutes.
     */
    public function getDurationMinutesAttribute(): int
    {
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);

        return ($end - $start) / 60;
    }

    /**
     * Check if a given time falls within this break.
     */
    public function containsTime(string $time): bool
    {
        return $time >= $this->start_time && $time < $this->end_time;
    }

    /**
     * Check if a time range overlaps with this break.
     */
    public function overlapsWithRange(string $startTime, string $endTime): bool
    {
        return $startTime < $this->end_time && $endTime > $this->start_time;
    }

    /**
     * Scope to filter by day of week.
     */
    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope to filter by scheduleable.
     */
    public function scopeForScheduleable($query, Model $scheduleable)
    {
        return $query->where('scheduleable_type', get_class($scheduleable))
            ->where('scheduleable_id', $scheduleable->id);
    }

    /**
     * Scope to filter by provider.
     */
    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('scheduleable_type', Provider::class)
            ->where('scheduleable_id', $providerId);
    }

    /**
     * Scope to filter by team member.
     */
    public function scopeForTeamMember($query, int $teamMemberId)
    {
        return $query->where('scheduleable_type', TeamMember::class)
            ->where('scheduleable_id', $teamMemberId);
    }
}

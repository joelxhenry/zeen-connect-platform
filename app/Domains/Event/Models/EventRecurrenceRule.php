<?php

namespace App\Domains\Event\Models;

use App\Domains\Event\Enums\RecurrenceFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRecurrenceRule extends Model
{
    protected $fillable = [
        'event_id',
        'frequency',
        'interval',
        'days_of_week',
        'time_of_day',
        'starts_at',
        'ends_at',
        'max_occurrences',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => RecurrenceFrequency::class,
            'interval' => 'integer',
            'days_of_week' => 'array',
            'time_of_day' => 'datetime:H:i:s',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'max_occurrences' => 'integer',
        ];
    }

    /**
     * Get the event this rule belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Check if the recurrence has an end date.
     */
    public function hasEndDate(): bool
    {
        return $this->ends_at !== null;
    }

    /**
     * Check if the recurrence has a max occurrences limit.
     */
    public function hasMaxOccurrences(): bool
    {
        return $this->max_occurrences !== null;
    }

    /**
     * Check if the recurrence is infinite.
     */
    public function isInfinite(): bool
    {
        return ! $this->hasEndDate() && ! $this->hasMaxOccurrences();
    }

    /**
     * Get the days of week as day names.
     */
    public function getDayNamesAttribute(): array
    {
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        if (empty($this->days_of_week)) {
            return [];
        }

        return array_map(fn ($day) => $dayNames[$day], $this->days_of_week);
    }

    /**
     * Get a human-readable description of the recurrence pattern.
     */
    public function getDescriptionAttribute(): string
    {
        $dayNames = $this->day_names;

        if (empty($dayNames)) {
            return 'Weekly';
        }

        $interval = $this->interval > 1 ? "every {$this->interval} weeks" : 'weekly';

        return ucfirst($interval).' on '.implode(', ', $dayNames);
    }

    /**
     * Get the time display format.
     */
    public function getTimeDisplayAttribute(): string
    {
        return $this->time_of_day->format('g:i A');
    }
}

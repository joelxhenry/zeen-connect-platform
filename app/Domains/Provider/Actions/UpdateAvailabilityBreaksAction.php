<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\AvailabilityBreak;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UpdateAvailabilityBreaksAction
{
    /**
     * Sync breaks for a scheduleable entity (Provider or TeamMember).
     *
     * @param  Provider|TeamMember  $scheduleable
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string, label?: string}>  $breaks
     */
    public function execute(Model $scheduleable, array $breaks): void
    {
        if (! $scheduleable instanceof Provider && ! $scheduleable instanceof TeamMember) {
            throw new \InvalidArgumentException('Scheduleable must be a Provider or TeamMember');
        }

        DB::transaction(function () use ($scheduleable, $breaks) {
            // Delete existing breaks
            $scheduleable->breaks()->delete();

            // Create new breaks
            foreach ($breaks as $breakData) {
                $this->createBreak($scheduleable, $breakData);
            }
        });
    }

    /**
     * Create a single break.
     */
    protected function createBreak(Model $scheduleable, array $breakData): AvailabilityBreak
    {
        $dayOfWeek = $breakData['day_of_week'];

        // Validate day of week
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new \InvalidArgumentException('Invalid day of week: '.$dayOfWeek);
        }

        // Validate times
        if (! isset($breakData['start_time']) || ! isset($breakData['end_time'])) {
            throw new \InvalidArgumentException('Break must have start_time and end_time');
        }

        if ($breakData['start_time'] >= $breakData['end_time']) {
            throw new \InvalidArgumentException('Break start_time must be before end_time');
        }

        return AvailabilityBreak::create([
            'scheduleable_type' => get_class($scheduleable),
            'scheduleable_id' => $scheduleable->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $breakData['start_time'],
            'end_time' => $breakData['end_time'],
            'label' => $breakData['label'] ?? null,
        ]);
    }

    /**
     * Add a single break to a scheduleable.
     */
    public function addBreak(Model $scheduleable, array $breakData): AvailabilityBreak
    {
        if (! $scheduleable instanceof Provider && ! $scheduleable instanceof TeamMember) {
            throw new \InvalidArgumentException('Scheduleable must be a Provider or TeamMember');
        }

        return $this->createBreak($scheduleable, $breakData);
    }

    /**
     * Remove a break by ID.
     */
    public function removeBreak(int $breakId): bool
    {
        return AvailabilityBreak::where('id', $breakId)->delete() > 0;
    }

    /**
     * Update a single break.
     */
    public function updateBreak(int $breakId, array $breakData): ?AvailabilityBreak
    {
        $break = AvailabilityBreak::find($breakId);

        if (! $break) {
            return null;
        }

        $updateData = [];

        if (isset($breakData['day_of_week'])) {
            if ($breakData['day_of_week'] < 0 || $breakData['day_of_week'] > 6) {
                throw new \InvalidArgumentException('Invalid day of week: '.$breakData['day_of_week']);
            }
            $updateData['day_of_week'] = $breakData['day_of_week'];
        }

        if (isset($breakData['start_time'])) {
            $updateData['start_time'] = $breakData['start_time'];
        }

        if (isset($breakData['end_time'])) {
            $updateData['end_time'] = $breakData['end_time'];
        }

        if (array_key_exists('label', $breakData)) {
            $updateData['label'] = $breakData['label'];
        }

        // Validate times if both are being updated
        $startTime = $updateData['start_time'] ?? $break->start_time;
        $endTime = $updateData['end_time'] ?? $break->end_time;

        if ($startTime >= $endTime) {
            throw new \InvalidArgumentException('Break start_time must be before end_time');
        }

        $break->update($updateData);

        return $break->fresh();
    }

    /**
     * Clear all breaks for a scheduleable.
     */
    public function clearBreaks(Model $scheduleable): int
    {
        if (! $scheduleable instanceof Provider && ! $scheduleable instanceof TeamMember) {
            throw new \InvalidArgumentException('Scheduleable must be a Provider or TeamMember');
        }

        return $scheduleable->breaks()->delete();
    }

    /**
     * Clear breaks for a specific day.
     */
    public function clearBreaksForDay(Model $scheduleable, int $dayOfWeek): int
    {
        if (! $scheduleable instanceof Provider && ! $scheduleable instanceof TeamMember) {
            throw new \InvalidArgumentException('Scheduleable must be a Provider or TeamMember');
        }

        return $scheduleable->breaks()->forDay($dayOfWeek)->delete();
    }
}

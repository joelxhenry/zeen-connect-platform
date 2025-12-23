<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\BlockedDate;
use App\Domains\Provider\Models\Provider;

class UpdateBlockedDatesAction
{
    /**
     * Update the provider's blocked dates.
     * Syncs the provided dates - adds new ones and removes ones not in the list.
     */
    public function execute(Provider $provider, array $blockedDates): void
    {
        // Get the dates being submitted
        $submittedDates = collect($blockedDates)->pluck('date')->toArray();

        // Delete blocked dates that are not in the submitted list (future only)
        $provider->blockedDates()
            ->future()
            ->whereNotIn('date', $submittedDates)
            ->delete();

        // Add or update blocked dates
        foreach ($blockedDates as $blocked) {
            BlockedDate::updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'date' => $blocked['date'],
                ],
                [
                    'reason' => $blocked['reason'] ?? null,
                ]
            );
        }
    }
}

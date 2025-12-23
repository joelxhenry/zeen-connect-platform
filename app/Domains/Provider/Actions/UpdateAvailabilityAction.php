<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\ProviderAvailability;

class UpdateAvailabilityAction
{
    /**
     * Update the provider's weekly availability schedule.
     */
    public function execute(Provider $provider, array $schedule): void
    {
        foreach ($schedule as $daySchedule) {
            ProviderAvailability::updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'day_of_week' => $daySchedule['day_of_week'],
                ],
                [
                    'start_time' => $daySchedule['start_time'],
                    'end_time' => $daySchedule['end_time'],
                    'is_available' => $daySchedule['is_available'],
                ]
            );
        }
    }
}

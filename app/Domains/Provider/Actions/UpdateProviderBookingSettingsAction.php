<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\Provider;

class UpdateProviderBookingSettingsAction
{
    public function execute(Provider $provider, array $data): Provider
    {
        $provider->update([
            'requires_approval' => $data['requires_approval'],
            'deposit_type' => $data['deposit_type'],
            'deposit_amount' => $data['deposit_type'] !== 'none' ? $data['deposit_amount'] : null,
            'cancellation_policy' => $data['cancellation_policy'],
            'advance_booking_days' => $data['advance_booking_days'],
            'min_booking_notice_hours' => $data['min_booking_notice_hours'],
        ]);

        return $provider->fresh();
    }
}

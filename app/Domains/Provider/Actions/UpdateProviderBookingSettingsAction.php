<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\Provider;

class UpdateProviderBookingSettingsAction
{
    public function execute(Provider $provider, array $data): Provider
    {
        $depositType = $data['deposit_type'];

        $updateData = [
            'requires_approval' => $data['requires_approval'],
            'deposit_type' => $depositType,
            // Only store deposit_amount for percentage type (no fixed deposits supported)
            'deposit_amount' => $depositType === 'percentage' ? $data['deposit_amount'] : null,
            'cancellation_policy' => $data['cancellation_policy'],
            'advance_booking_days' => $data['advance_booking_days'],
            'min_booking_notice_hours' => $data['min_booking_notice_hours'],
        ];

        // Only update fee_payer if provided
        if (isset($data['fee_payer'])) {
            $updateData['fee_payer'] = $data['fee_payer'];
        }

        $provider->update($updateData);

        return $provider->fresh();
    }
}

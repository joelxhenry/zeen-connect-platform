<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;

class CreateServiceAction
{
    public function execute(Provider $provider, array $data): Service
    {
        $useDefaults = $data['use_provider_defaults'] ?? true;

        $service = $provider->services()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'price' => $data['price'],
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
            // Booking settings
            'use_provider_defaults' => $useDefaults,
            'requires_approval' => $useDefaults ? null : ($data['requires_approval'] ?? null),
            'deposit_type' => $useDefaults ? null : ($data['deposit_type'] ?? null),
            'deposit_amount' => $useDefaults ? null : ($data['deposit_amount'] ?? null),
            'cancellation_policy' => $useDefaults ? null : ($data['cancellation_policy'] ?? null),
            'advance_booking_days' => $useDefaults ? null : ($data['advance_booking_days'] ?? null),
            'min_booking_notice_hours' => $useDefaults ? null : ($data['min_booking_notice_hours'] ?? null),
        ]);

        // Sync categories if provided (multiple via polymorphic relationship)
        if (isset($data['category_ids'])) {
            $service->syncCategories($data['category_ids']);
        }

        // Sync team members if provided
        if (isset($data['team_member_ids'])) {
            $service->teamMembers()->sync($data['team_member_ids']);
        }

        return $service;
    }
}

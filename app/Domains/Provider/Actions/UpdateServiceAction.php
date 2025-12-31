<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Service\Models\Service;

class UpdateServiceAction
{
    public function execute(Service $service, array $data): Service
    {
        $useDefaults = $data['use_provider_defaults'] ?? true;

        // Build settings JSON
        $settings = [
            'use_provider_defaults' => $useDefaults,
            'requires_approval' => $useDefaults ? null : ($data['requires_approval'] ?? null),
            'deposit_type' => $useDefaults ? null : ($data['deposit_type'] ?? null),
            'deposit_amount' => $useDefaults ? null : ($data['deposit_amount'] ?? null),
            'cancellation_policy' => $useDefaults ? null : ($data['cancellation_policy'] ?? null),
            'advance_booking_days' => $useDefaults ? null : ($data['advance_booking_days'] ?? null),
            'min_booking_notice_hours' => $useDefaults ? null : ($data['min_booking_notice_hours'] ?? null),
            'buffer_minutes' => $data['buffer_minutes'] ?? null,
        ];

        $service->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'price' => $data['price'],
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? $service->sort_order,
            'settings' => $settings,
        ]);

        // Sync categories if provided (multiple via polymorphic relationship)
        if (array_key_exists('category_ids', $data)) {
            $service->syncCategories($data['category_ids'] ?? []);
        }

        // Sync team members if provided
        if (array_key_exists('team_member_ids', $data)) {
            $service->teamMembers()->sync($data['team_member_ids'] ?? []);
        }

        return $service->fresh('categories');
    }
}

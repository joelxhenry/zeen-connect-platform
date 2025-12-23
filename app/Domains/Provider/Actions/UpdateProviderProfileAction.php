<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateProviderProfileAction
{
    /**
     * Update the provider profile with the given data.
     */
    public function execute(Provider $provider, array $data): Provider
    {
        return DB::transaction(function () use ($provider, $data) {
            // Update user phone if provided
            if (isset($data['phone'])) {
                $provider->user->update(['phone' => $data['phone']]);
            }

            // Prepare provider data
            $providerData = [
                'business_name' => $data['business_name'],
                'tagline' => $data['tagline'] ?? null,
                'bio' => $data['bio'] ?? null,
                'address' => $data['address'] ?? null,
                'website' => $data['website'] ?? null,
                'social_links' => $this->filterSocialLinks($data['social_links'] ?? []),
            ];

            // Update provider
            $provider->update($providerData);

            // Sync locations if provided
            if (isset($data['location_ids']) && ! empty($data['location_ids'])) {
                $primaryLocationId = $data['primary_location_id'] ?? $data['location_ids'][0];
                $provider->syncLocations($data['location_ids'], $primaryLocationId);
            } elseif (isset($data['primary_location_id'])) {
                // Just primary location, no multiple locations
                $provider->syncLocations([$data['primary_location_id']], $data['primary_location_id']);
            }

            return $provider->fresh(['primaryLocation.region.country', 'locations']);
        });
    }

    /**
     * Filter out empty social links.
     */
    private function filterSocialLinks(array $links): ?array
    {
        $filtered = array_filter($links, fn ($value) => ! empty($value));

        return empty($filtered) ? null : $filtered;
    }
}

<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\Provider;
use Illuminate\Support\Facades\DB;

class UpdateProviderProfileAction
{
    /**
     * Update the provider profile with the given data.
     */
    public function execute(Provider $provider, array $data): Provider
    {
        return DB::transaction(function () use ($provider, $data) {
            // Prepare provider data (only profile-specific fields)
            // Note: bio, tagline, address, and domain are now managed via Branding
            $providerData = [
                'business_name' => $data['business_name'],
                'social_links' => $this->filterSocialLinks($data['social_links'] ?? []),
            ];

            // Update provider
            $provider->update($providerData);

            return $provider->fresh();
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

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

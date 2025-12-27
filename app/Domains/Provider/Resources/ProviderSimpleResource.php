<?php

namespace App\Domains\Provider\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Simple provider resource for list views and minimal displays.
 * For full provider details, use ProviderResource with method chaining.
 */
class ProviderSimpleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'business_name' => $this->business_name,
            'tagline' => $this->tagline,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'rating_avg' => (float) $this->rating_avg,
            'rating_count' => (int) $this->rating_count,
            'rating_display' => $this->rating_display,
            'total_bookings' => (int) $this->total_bookings,
            'avatar' => $this->avatar_url ?? $this->user?->avatar,
            'user' => $this->whenLoaded('user', fn () => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar' => $this->user->avatar,
            ]),
            'services_count' => $this->when(
                isset($this->services_count),
                $this->services_count
            ),
            'reviews_count' => $this->when(
                isset($this->reviews_count),
                $this->reviews_count
            ),
            'commission_rate' => $this->when(
                $this->commission_rate !== null,
                (float) $this->commission_rate
            ),
            'verified_at' => $this->verified_at?->format('M d, Y'),
            'created_at' => $this->created_at->format('M d, Y'),
        ];
    }
}

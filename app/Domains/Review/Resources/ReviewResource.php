<?php

namespace App\Domains\Review\Resources;

use App\Domains\Shared\Resources\Concerns\HasDisplayValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    use HasDisplayValues;

    /**
     * Context flags for conditional inclusion.
     */
    protected bool $includeClient = true;

    protected bool $includeService = true;

    protected bool $includeProvider = false;

    protected bool $includeMedia = false;

    /**
     * Include client information.
     */
    public function withClient(bool $include = true): self
    {
        $this->includeClient = $include;

        return $this;
    }

    /**
     * Include service information.
     */
    public function withService(bool $include = true): self
    {
        $this->includeService = $include;

        return $this;
    }

    /**
     * Include provider information.
     */
    public function withProvider(bool $include = true): self
    {
        $this->includeProvider = $include;

        return $this;
    }

    /**
     * Include media (photos).
     */
    public function withMedia(bool $include = true): self
    {
        $this->includeMedia = $include;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,

            // Rating
            'rating' => $this->rating,
            'rating_stars' => $this->rating_stars,

            // Content
            'comment' => $this->comment,
            'provider_response' => $this->provider_response,

            // Pre-computed display values
            'formatted_date' => $this->formatted_date,
            'time_ago' => $this->time_ago,

            // State
            'has_response' => $this->hasProviderResponse(),
            'can_respond' => $this->canBeRespondedTo(),
            'is_visible' => $this->is_visible,
            'is_flagged' => $this->is_flagged,

            // Timestamps
            'provider_responded_at' => $this->formatDateTime($this->provider_responded_at),
            'created_at' => $this->formatDate($this->created_at),
        ];

        // Conditional nested resources
        if ($this->includeClient) {
            $data['client'] = $this->formatClient();
        }

        if ($this->includeService) {
            $data['service_name'] = $this->service?->name;
        }

        if ($this->includeProvider) {
            $data['provider'] = $this->formatProvider();
        }

        if ($this->includeMedia && $this->relationLoaded('media')) {
            $data['media'] = $this->getMedia('photos')->map(fn ($media) => $media->toMediaArray())->toArray();
        }

        return $data;
    }

    /**
     * Format client information.
     */
    protected function formatClient(): array
    {
        return [
            'name' => $this->client?->name,
            'avatar' => $this->client?->avatar,
        ];
    }

    /**
     * Format provider information.
     */
    protected function formatProvider(): array
    {
        return [
            'id' => $this->provider->id,
            'uuid' => $this->provider->uuid,
            'business_name' => $this->provider->business_name,
            'slug' => $this->provider->slug,
        ];
    }
}

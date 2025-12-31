<?php

namespace App\Domains\Service\Resources;

use App\Domains\Media\Resources\MediaResource;
use App\Domains\Media\Resources\VideoEmbedResource;
use App\Domains\Payment\Services\FeeCalculator;
use App\Domains\Shared\Resources\Concerns\HasDisplayValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    use HasDisplayValues;

    protected bool $includeCategory = false;
    protected bool $includeProvider = false;
    protected bool $includeMedia = false;
    protected bool $includeFees = false;
    protected bool $includeBookingSettings = false;
    protected bool $includeCounts = false;
    protected bool $includeTeamMembers = false;

    /**
     * Include category information.
     */
    public function withCategory(bool $include = true): self
    {
        $this->includeCategory = $include;

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
     * Include media (cover, gallery, videos).
     */
    public function withMedia(bool $include = true): self
    {
        $this->includeMedia = $include;

        return $this;
    }

    /**
     * Include fee calculations.
     */
    public function withFees(bool $include = true): self
    {
        $this->includeFees = $include;

        return $this;
    }

    /**
     * Include booking settings (approval, deposit, cancellation).
     */
    public function withBookingSettings(bool $include = true): self
    {
        $this->includeBookingSettings = $include;

        return $this;
    }

    /**
     * Include relationship counts.
     */
    public function withCounts(bool $include = true): self
    {
        $this->includeCounts = $include;

        return $this;
    }

    /**
     * Include team members assigned to this service.
     */
    public function withTeamMembers(bool $include = true): self
    {
        $this->includeTeamMembers = $include;

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
            'name' => $this->name,
            'description' => $this->description,
            'duration_minutes' => (int) $this->duration_minutes,
            'duration_display' => $this->duration_display,
            'price' => (float) $this->price,
            'price_display' => $this->price_display,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
        ];

        if ($this->includeCategory) {
            $data['category'] = $this->formatCategory();
        }

        if ($this->includeProvider) {
            $data['provider'] = $this->formatProvider();
        }

        if ($this->includeMedia) {
            $data = array_merge($data, $this->formatMedia());
        }

        if ($this->includeFees) {
            $data['fees'] = $this->formatFees();
        }

        if ($this->includeBookingSettings) {
            $data['booking_settings'] = $this->formatBookingSettings();
        }

        if ($this->includeCounts) {
            $data = array_merge($data, $this->formatCounts());
        }

        if ($this->includeTeamMembers) {
            $data['team_member_ids'] = $this->formatTeamMemberIds();
        }

        return $data;
    }

    /**
     * Format category data.
     */
    protected function formatCategory(): ?array
    {
        if (! $this->relationLoaded('category') || ! $this->category) {
            return null;
        }

        return [
            'id' => $this->category->id,
            'uuid' => $this->category->uuid,
            'name' => $this->category->name,
            'slug' => $this->category->slug,
            'icon' => $this->category->icon,
        ];
    }

    /**
     * Format provider data.
     */
    protected function formatProvider(): ?array
    {
        if (! $this->relationLoaded('provider') || ! $this->provider) {
            return null;
        }

        return [
            'id' => $this->provider->id,
            'uuid' => $this->provider->uuid,
            'business_name' => $this->provider->business_name,
            'slug' => $this->provider->slug,
            'avatar' => $this->provider->avatar_url ?? $this->provider->user?->avatar,
        ];
    }

    /**
     * Format media data (cover, gallery, videos).
     */
    protected function formatMedia(): array
    {
        $cover = null;
        $gallery = [];
        $videos = [];

        if ($this->relationLoaded('media')) {
            $coverMedia = $this->getFirstMedia('cover');
            if ($coverMedia) {
                $cover = (new MediaResource($coverMedia))->resolve();
            }

            $gallery = $this->getMedia('gallery')
                ->map(fn ($media) => (new MediaResource($media))->resolve())
                ->toArray();
        }

        if ($this->relationLoaded('videoEmbeds')) {
            $videos = $this->videoEmbeds
                ->map(fn ($video) => (new VideoEmbedResource($video))->resolve())
                ->toArray();
        }

        return [
            'cover' => $cover,
            'cover_url' => $this->cover_url,
            'cover_thumbnail' => $this->cover_thumbnail,
            'gallery' => $gallery,
            'videos' => $videos,
        ];
    }

    /**
     * Format fee calculations.
     * Uses the service's effective deposit settings.
     */
    protected function formatFees(): ?array
    {
        if (! $this->relationLoaded('provider') || ! $this->provider) {
            return null;
        }

        $feeCalculator = app(FeeCalculator::class);

        // Calculate fees using this service's deposit settings
        return $feeCalculator->calculateFees($this->provider, (float) $this->price, $this->resource)->toArray();
    }

    /**
     * Format booking settings.
     * Returns the service's own stored values (not effective/computed values).
     */
    protected function formatBookingSettings(): array
    {
        return [
            'use_provider_defaults' => (bool) $this->use_provider_defaults,
            'requires_approval' => $this->requires_approval !== null ? (bool) $this->requires_approval : null,
            'deposit_type' => $this->deposit_type,
            'deposit_amount' => $this->deposit_amount !== null ? (float) $this->deposit_amount : null,
            'cancellation_policy' => $this->cancellation_policy,
            'advance_booking_days' => $this->advance_booking_days !== null ? (int) $this->advance_booking_days : null,
            'min_booking_notice_hours' => $this->min_booking_notice_hours !== null ? (int) $this->min_booking_notice_hours : null,
        ];
    }

    /**
     * Format relationship counts.
     */
    protected function formatCounts(): array
    {
        return [
            'total_bookings' => $this->total_bookings ?? $this->bookings()->count(),
        ];
    }

    /**
     * Format team member IDs.
     */
    protected function formatTeamMemberIds(): array
    {
        if (! $this->relationLoaded('teamMembers')) {
            return [];
        }

        return $this->teamMembers->pluck('id')->toArray();
    }
}

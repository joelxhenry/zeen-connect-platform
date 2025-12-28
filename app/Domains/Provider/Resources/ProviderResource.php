<?php

namespace App\Domains\Provider\Resources;

use App\Domains\Media\Resources\MediaResource;
use App\Domains\Media\Resources\VideoEmbedResource;
use App\Domains\Shared\Resources\Concerns\HasDisplayValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    use HasDisplayValues;

    protected bool $includeUser = false;
    protected bool $includeMedia = false;
    protected bool $includeServices = false;
    protected bool $includeAvailability = false;
    protected bool $includeAdminDetails = false;
    protected bool $includeCounts = false;

    /**
     * Include user information.
     */
    public function withUser(bool $include = true): self
    {
        $this->includeUser = $include;

        return $this;
    }

    /**
     * Include media (avatar, cover, gallery, videos).
     */
    public function withMedia(bool $include = true): self
    {
        $this->includeMedia = $include;

        return $this;
    }

    /**
     * Include services list.
     */
    public function withServices(bool $include = true): self
    {
        $this->includeServices = $include;

        return $this;
    }

    /**
     * Include availability slots.
     */
    public function withAvailability(bool $include = true): self
    {
        $this->includeAvailability = $include;

        return $this;
    }

    /**
     * Include admin-specific details (commission, verification).
     */
    public function withAdminDetails(bool $include = true): self
    {
        $this->includeAdminDetails = $include;

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
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'business_name' => $this->business_name,
            'tagline' => $this->tagline,
            'bio' => $this->bio,
            'address' => $this->address,
            'website' => $this->website,
            'social_links' => $this->social_links,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'is_founding_member' => $this->isFoundingMember(),
            'founding_tier' => $this->getFoundingSubscriptionTier()?->value,
            'rating_avg' => (float) $this->rating_avg,
            'rating_count' => (int) $this->rating_count,
            'rating_display' => $this->rating_display,
            'total_bookings' => (int) $this->total_bookings,
            'verified_at' => $this->verified_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];

        if ($this->includeUser) {
            $data['user'] = $this->formatUser();
        }

        if ($this->includeMedia) {
            $data = array_merge($data, $this->formatMedia());
        }

        if ($this->includeServices) {
            $data['services'] = $this->formatServices();
        }

        if ($this->includeAvailability) {
            $data['availability'] = $this->formatAvailability();
        }

        if ($this->includeAdminDetails) {
            $data = array_merge($data, $this->formatAdminDetails());
        }

        if ($this->includeCounts) {
            $data = array_merge($data, $this->formatCounts());
        }

        return $data;
    }

    /**
     * Format user data.
     */
    protected function formatUser(): ?array
    {
        if (! $this->relationLoaded('user') || ! $this->user) {
            return null;
        }

        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'avatar' => $this->user->avatar,
            'joined' => $this->user->created_at?->format('M d, Y'),
        ];
    }

    /**
     * Format media data (avatar, cover, gallery, videos).
     */
    protected function formatMedia(): array
    {
        $avatar = $this->avatar_url ?? $this->user?->avatar;
        $cover = $this->cover_photo_url;

        $gallery = [];
        if ($this->relationLoaded('media')) {
            $gallery = $this->getMedia('gallery')
                ->map(fn ($media) => (new MediaResource($media))->resolve())
                ->toArray();
        }

        $videos = [];
        if ($this->relationLoaded('videoEmbeds')) {
            $videos = $this->videoEmbeds
                ->map(fn ($video) => (new VideoEmbedResource($video))->resolve())
                ->toArray();
        }

        return [
            'avatar' => $avatar,
            'avatar_media' => $this->relationLoaded('media')
                ? ($this->getFirstMedia('avatar') ? (new MediaResource($this->getFirstMedia('avatar')))->resolve() : null)
                : null,
            'cover' => $cover,
            'cover_media' => $this->relationLoaded('media')
                ? ($this->getFirstMedia('cover') ? (new MediaResource($this->getFirstMedia('cover')))->resolve() : null)
                : null,
            'gallery' => $gallery,
            'videos' => $videos,
        ];
    }

    /**
     * Format services list.
     */
    protected function formatServices(): array
    {
        if (! $this->relationLoaded('services')) {
            return [];
        }

        return $this->services->map(fn ($service) => [
            'id' => $service->id,
            'uuid' => $service->uuid,
            'name' => $service->name,
            'description' => $service->description,
            'category' => $service->relationLoaded('category') ? $service->category->name : null,
            'duration_minutes' => $service->duration_minutes,
            'duration_display' => $service->duration_display,
            'price' => (float) $service->price,
            'price_display' => $service->price_display,
            'is_active' => (bool) $service->is_active,
        ])->toArray();
    }

    /**
     * Format availability slots.
     */
    protected function formatAvailability(): array
    {
        if (! $this->relationLoaded('availability')) {
            return [];
        }

        return $this->availability->map(fn ($slot) => [
            'day_of_week' => $slot->day_of_week,
            'day' => $slot->day_name,
            'start_time' => $slot->formatted_start_time,
            'end_time' => $slot->formatted_end_time,
            'is_available' => (bool) $slot->is_available,
        ])->toArray();
    }

    /**
     * Format admin-specific details.
     */
    protected function formatAdminDetails(): array
    {
        return [
            'commission_rate' => (float) $this->commission_rate,
            'verified_at_formatted' => $this->verified_at?->format('M d, Y H:i'),
            'created_at_formatted' => $this->created_at->format('M d, Y H:i'),
            'requires_approval' => (bool) $this->requires_approval,
            'deposit_type' => $this->deposit_type,
            'deposit_amount' => $this->deposit_amount ? (float) $this->deposit_amount : null,
            'deposit_percentage' => $this->deposit_percentage ? (float) $this->deposit_percentage : null,
            'cancellation_policy' => $this->cancellation_policy,
            'advance_booking_days' => $this->advance_booking_days,
            'min_booking_notice_hours' => $this->min_booking_notice_hours,
            'fee_payer' => $this->fee_payer,
            'founding_member_at' => $this->founding_member_at?->format('M d, Y'),
            'founding_fee_waiver_ends_at' => $this->getFoundingFeeWaiverEndsAt()?->format('M d, Y'),
            'has_founding_fee_waiver' => $this->hasFoundingFeeWaiver(),
        ];
    }

    /**
     * Format relationship counts.
     */
    protected function formatCounts(): array
    {
        return [
            'services_count' => $this->services_count ?? $this->services()->count(),
            'reviews_count' => $this->reviews_count ?? $this->reviews()->count(),
        ];
    }
}

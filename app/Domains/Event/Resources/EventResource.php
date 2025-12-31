<?php

namespace App\Domains\Event\Resources;

use App\Domains\Media\Resources\MediaResource;
use App\Domains\Media\Resources\VideoEmbedResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    protected bool $includeCategories = false;
    protected bool $includeProvider = false;
    protected bool $includeMedia = false;
    protected bool $includeOccurrences = false;
    protected bool $includeRecurrenceRule = false;
    protected bool $includeBookingSettings = false;
    protected bool $includeCounts = false;
    protected bool $includeTeamMembers = false;

    /**
     * Include categories information.
     */
    public function withCategories(bool $include = true): self
    {
        $this->includeCategories = $include;

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
     * Include occurrences.
     */
    public function withOccurrences(bool $include = true): self
    {
        $this->includeOccurrences = $include;

        return $this;
    }

    /**
     * Include recurrence rule.
     */
    public function withRecurrenceRule(bool $include = true): self
    {
        $this->includeRecurrenceRule = $include;

        return $this;
    }

    /**
     * Include booking settings.
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
     * Include team members assigned to this event.
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
            'event_type' => $this->event_type->value,
            'event_type_label' => $this->event_type->label(),
            'location_type' => $this->location_type->value,
            'location_type_label' => $this->location_type->label(),
            'location' => $this->location,
            'location_display' => $this->location_display,
            'virtual_meeting_url' => $this->virtual_meeting_url,
            'duration_minutes' => (int) $this->duration_minutes,
            'duration_display' => $this->duration_display,
            'capacity' => $this->capacity,
            'capacity_display' => $this->capacity_display,
            'price' => (float) $this->price,
            'price_display' => $this->price_display,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'is_recurring' => $this->isRecurring(),
            'is_published' => $this->isPublished(),
        ];

        if ($this->includeCategories) {
            $data['categories'] = $this->formatCategories();
            $data['category_ids'] = $this->formatCategoryIds();
        }

        if ($this->includeProvider) {
            $data['provider'] = $this->formatProvider();
        }

        if ($this->includeMedia) {
            $data = array_merge($data, $this->formatMedia());
        }

        if ($this->includeOccurrences) {
            $data['occurrences'] = $this->formatOccurrences();
            $data['next_occurrence'] = $this->formatNextOccurrence();
        }

        if ($this->includeRecurrenceRule) {
            $data['recurrence_rule'] = $this->formatRecurrenceRule();
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
     * Format categories data.
     */
    protected function formatCategories(): array
    {
        if (! $this->relationLoaded('categories')) {
            return [];
        }

        return $this->categories->map(fn ($category) => [
            'id' => $category->id,
            'uuid' => $category->uuid,
            'name' => $category->name,
            'slug' => $category->slug,
        ])->toArray();
    }

    /**
     * Format category IDs for form binding.
     */
    protected function formatCategoryIds(): array
    {
        if (! $this->relationLoaded('categories')) {
            return [];
        }

        return $this->categories->pluck('id')->toArray();
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
            'cover_url' => $this->getFirstMediaUrl('cover', 'medium'),
            'cover_thumbnail' => $this->getFirstMediaUrl('cover', 'thumbnail'),
            'gallery' => $gallery,
            'videos' => $videos,
        ];
    }

    /**
     * Format occurrences.
     */
    protected function formatOccurrences(): array
    {
        if (! $this->relationLoaded('occurrences')) {
            return [];
        }

        return $this->occurrences->map(fn ($occurrence) => (new EventOccurrenceResource($occurrence))->resolve())->toArray();
    }

    /**
     * Format next occurrence.
     */
    protected function formatNextOccurrence(): ?array
    {
        $nextOccurrence = $this->getNextOccurrence();

        if (! $nextOccurrence) {
            return null;
        }

        return (new EventOccurrenceResource($nextOccurrence))->resolve();
    }

    /**
     * Format recurrence rule.
     */
    protected function formatRecurrenceRule(): ?array
    {
        if (! $this->relationLoaded('recurrenceRule') || ! $this->recurrenceRule) {
            return null;
        }

        $rule = $this->recurrenceRule;

        return [
            'frequency' => $rule->frequency->value,
            'frequency_label' => $rule->frequency->label(),
            'interval' => $rule->interval,
            'days_of_week' => $rule->days_of_week,
            'day_names' => $rule->day_names,
            'time_of_day' => $rule->time_of_day?->format('H:i'),
            'time_display' => $rule->time_display,
            'starts_at' => $rule->starts_at?->format('Y-m-d'),
            'ends_at' => $rule->ends_at?->format('Y-m-d'),
            'max_occurrences' => $rule->max_occurrences,
            'description' => $rule->description,
        ];
    }

    /**
     * Format booking settings.
     */
    protected function formatBookingSettings(): array
    {
        $requiresApproval = $this->getSetting('requires_approval');
        $depositAmount = $this->getSetting('deposit_amount');
        $advanceBookingDays = $this->getSetting('advance_booking_days');
        $minBookingNoticeHours = $this->getSetting('min_booking_notice_hours');

        return [
            'use_provider_defaults' => $this->getSetting('use_provider_defaults', true),
            'requires_approval' => $requiresApproval !== null ? (bool) $requiresApproval : null,
            'deposit_type' => $this->getSetting('deposit_type'),
            'deposit_amount' => $depositAmount !== null ? (float) $depositAmount : null,
            'cancellation_policy' => $this->getSetting('cancellation_policy'),
            'advance_booking_days' => $advanceBookingDays !== null ? (int) $advanceBookingDays : null,
            'min_booking_notice_hours' => $minBookingNoticeHours !== null ? (int) $minBookingNoticeHours : null,
            'allow_waitlist' => $this->getSetting('allow_waitlist', false),
            'max_spots_per_booking' => $this->getSetting('max_spots_per_booking', 10),
        ];
    }

    /**
     * Format relationship counts.
     */
    protected function formatCounts(): array
    {
        return [
            'occurrences_count' => $this->occurrences_count ?? $this->occurrences()->count(),
            'bookings_count' => $this->bookings_count ?? $this->getTotalBookingsCount(),
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

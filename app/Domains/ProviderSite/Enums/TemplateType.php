<?php

namespace App\Domains\ProviderSite\Enums;

use App\Domains\Subscription\Enums\SubscriptionTier;

enum TemplateType: string
{
    case DEFAULT = 'default';
    case MINIMAL = 'minimal';
    case BARBER_DELUX = 'barber_delux';
    case ARCHITECT_BOLD = 'architect_bold';
    case SHOWCASE = 'showcase';

    public function label(): string
    {
        return match ($this) {
            self::DEFAULT => 'Classic',
            self::MINIMAL => 'Minimal',
            self::BARBER_DELUX => 'Barber Delux',
            self::ARCHITECT_BOLD => 'Architect Bold',
            self::SHOWCASE => 'The Showcase',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DEFAULT => 'A balanced, professional layout suitable for all businesses with full-featured sections.',
            self::MINIMAL => 'Clean and simple design focused on content with less visual clutter.',
            self::BARBER_DELUX => 'Feature-rich template with split hero layout, team showcase, and feature cards.',
            self::ARCHITECT_BOLD => 'Professional template for contractors, lawyers, and consultants. Features bold typography, stats bar, and lead capture.',
            self::SHOWCASE => 'Luxury experience booking template with dramatic cover imagery, overlapping typography, and highlight gallery.',
        };
    }

    public function previewImage(): string
    {
        return "/images/templates/{$this->value}-preview.png";
    }

    /**
     * Get the minimum subscription tier required for this template.
     */
    public function requiredTier(): SubscriptionTier
    {
        return match ($this) {
            self::DEFAULT => SubscriptionTier::STARTER,
            self::MINIMAL => SubscriptionTier::PREMIUM,
            self::BARBER_DELUX => SubscriptionTier::PREMIUM,
            self::ARCHITECT_BOLD => SubscriptionTier::PREMIUM,
            self::SHOWCASE => SubscriptionTier::PREMIUM,
        };
    }

    /**
     * Check if a provider with the given tier can use this template.
     */
    public function isAvailableForTier(SubscriptionTier $tier): bool
    {
        $tierOrder = [
            SubscriptionTier::STARTER->value => 1,
            SubscriptionTier::PREMIUM->value => 2,
            SubscriptionTier::ENTERPRISE->value => 3,
        ];

        $requiredLevel = $tierOrder[$this->requiredTier()->value];
        $currentLevel = $tierOrder[$tier->value];

        return $currentLevel >= $requiredLevel;
    }

    /**
     * Get all templates with their metadata.
     *
     * @return array<array{value: string, label: string, description: string, preview_image: string, required_tier: string}>
     */
    public static function allWithMetadata(): array
    {
        return array_map(fn (self $template) => [
            'value' => $template->value,
            'label' => $template->label(),
            'description' => $template->description(),
            'preview_image' => $template->previewImage(),
            'required_tier' => $template->requiredTier()->value,
            'required_tier_label' => $template->requiredTier()->label(),
        ], self::cases());
    }
}

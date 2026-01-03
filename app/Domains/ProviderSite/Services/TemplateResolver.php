<?php

namespace App\Domains\ProviderSite\Services;

use App\Domains\Provider\Models\Provider;
use App\Domains\ProviderSite\Enums\TemplateType;

class TemplateResolver
{
    /**
     * Resolve which template a provider should use.
     * Falls back to default if the selected template is not available for their tier.
     */
    public function resolve(Provider $provider): TemplateType
    {
        $templateSlug = $provider->site_template ?? TemplateType::DEFAULT->value;
        $template = TemplateType::tryFrom($templateSlug) ?? TemplateType::DEFAULT;

        // Check if provider can use this template based on their tier
        if (! $template->isAvailableForTier($provider->getTier())) {
            return TemplateType::DEFAULT;
        }

        return $template;
    }

    /**
     * Get the Inertia page path for a template and page.
     */
    public function getPagePath(TemplateType $template, string $page): string
    {
        // 
        return "ProviderSite/templates/{$template->value}/{$page}";
    }

    /**
     * Check if a template slug is valid.
     */
    public function isValidTemplate(string $slug): bool
    {
        return TemplateType::tryFrom($slug) !== null;
    }

    /**
     * Get all available templates with metadata.
     *
     * @return array<array{value: string, label: string, description: string, preview_image: string, required_tier: string}>
     */
    public function getAvailableTemplates(): array
    {
        return TemplateType::allWithMetadata();
    }

    /**
     * Get available templates for a specific provider based on their tier.
     *
     * @return array<array{value: string, label: string, description: string, preview_image: string, required_tier: string, available: bool}>
     */
    public function getTemplatesForProvider(Provider $provider): array
    {
        $currentTier = $provider->getTier();

        return array_map(fn(array $template) => [
            ...$template,
            'available' => TemplateType::from($template['value'])->isAvailableForTier($currentTier),
        ], $this->getAvailableTemplates());
    }
}

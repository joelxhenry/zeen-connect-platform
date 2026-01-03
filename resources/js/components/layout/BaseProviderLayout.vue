<script setup lang="ts">
/**
 * BaseProviderLayout - The Controller Layout
 *
 * This is the top-level layout for all provider site pages.
 * It handles:
 * - Fetching provider settings from __provider Inertia prop
 * - Injecting branding CSS variables into the root wrapper
 * - Managing light/dark/system color mode
 * - Providing branding context to child components via provide/inject
 *
 * All other provider site layouts should extend this layout.
 */
import { provide, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useProviderBranding, type ProviderBrandingData } from '@/composables/useProviderBranding';
import FlashMessages from '@/components/error/FlashMessages.vue';

defineProps<{
    title?: string;
}>();

// Use the branding composable for centralized branding management
const {
    provider,
    brandingCssVars,
    colorMode,
    colorModeClass,
    prefersDark,
} = useProviderBranding();

// Compute page title
const pageTitle = computed(() => {
    const props = defineProps<{ title?: string }>();
    const businessName = provider.value?.business_name || 'Provider';
    return props.title ? `${props.title} | ${businessName}` : businessName;
});

// Provide branding context to child components
provide('providerBranding', {
    provider,
    colorMode,
    prefersDark,
    brandingCssVars,
});

// Provide provider data for easy access in child components
provide('provider', provider);
</script>

<template>
    <Head :title="title ? `${title} | ${provider?.business_name}` : provider?.business_name" />
    <FlashMessages />

    <div
        class="base-provider-layout"
        :style="brandingCssVars"
        :class="[colorModeClass]"
    >
        <slot />
    </div>
</template>

<style scoped>
.base-provider-layout {
    /* Default CSS variables - overridden by brandingCssVars */
    --provider-primary: #106B4F;
    --provider-primary-rgb: 16, 107, 79;
    --provider-primary-hover: #0D5A42;
    --provider-text: #0D1F1B;
    --provider-primary-10: rgba(var(--provider-primary-rgb), 0.1);
    --provider-primary-05: rgba(var(--provider-primary-rgb), 0.05);
    --provider-primary-20: rgba(var(--provider-primary-rgb), 0.2);

    /* Semantic colors */
    --provider-success: #22C55E;
    --provider-warning: #F59E0B;
    --provider-danger: #EF4444;
    --provider-info: #3B82F6;
    --provider-secondary: #6B7280;
    --provider-accent: #8B5CF6;

    /* Layout */
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Color mode classes for dark mode support */
.color-mode-light {
    --provider-bg: #ffffff;
    --provider-bg-secondary: #f9fafb;
    --provider-border: #e5e7eb;
    color-scheme: light;
}

.color-mode-dark {
    --provider-bg: #1f2937;
    --provider-bg-secondary: #111827;
    --provider-border: #374151;
    --provider-text: #f9fafb;
    color-scheme: dark;
}
</style>

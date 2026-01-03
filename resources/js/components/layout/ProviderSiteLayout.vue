<script setup lang="ts">
/**
 * ProviderSiteLayout - Provider Site UI Layout
 *
 * This layout extends BaseProviderLayout and provides the common UI structure
 * for all provider site pages including:
 * - Header with logo, navigation, and booking CTA
 * - Banner/Hero area
 * - Main content area
 * - Footer
 *
 * Branding is inherited from BaseProviderLayout via provide/inject.
 */
import { computed, useSlots, inject } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { User } from '@/types/models';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import BaseProviderLayout from '@/components/layout/BaseProviderLayout.vue';
import SiteFooter from '@/components/common/SiteFooter.vue';
import site from '@/routes/providersite';
import { login } from '@/routes';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';
import client from '@/routes/client';
import { resolveUrl } from '@/utils/url';
import type { ProviderBrandingData } from '@/composables/useProviderBranding';

defineProps<{
    title?: string;
    showBanner?: boolean;
}>();

const slots = useSlots();

const page = usePage();
const user = (page.props.auth as { user?: User } | undefined)?.user;

// Get provider from Inertia props (also available via inject from BaseProviderLayout)
const __provider = page.props.__provider as ProviderBrandingData | null;

// Navigation helpers
const currentPath = computed(() => {
    if (typeof window === 'undefined') return '/';
    const url = new URL(window.location.href);
    return url.pathname;
});

const isActive = (path: string) => {
    if (path === '/') {
        return currentPath.value === '/' && !currentPath.value.includes('/services') && !currentPath.value.includes('/reviews') && !currentPath.value.includes('/book') && !currentPath.value.includes('/events');
    }
    return currentPath.value.includes(path);
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

// URL generators
const homeUrl = computed(() => {
    return site.home({ provider: __provider?.domain ?? '' }).url;
});

const servicesUrl = computed(() => {
    return site.services({ provider: __provider?.domain ?? '' }).url;
});

const reviewsUrl = computed(() => {
    return site.reviews({ provider: __provider?.domain ?? '' }).url;
});

// Events URL (new)
const eventsUrl = computed(() => {
    // Will be added when events route is created
    
    return site.events({ provider: __provider?.domain ?? '' }).url;
});

const getBookingUrl = () => {
    return ProviderSiteBookingController.create({ provider: __provider?.domain ?? '' }).url;
};

// Check if provider has events (will be used to show/hide Events nav)
const hasEvents = computed(() => {
    // This will be populated from page props when events data is available
    const pageProps = page.props as { hasEvents?: boolean };
    return pageProps.hasEvents ?? false;
});
</script>

<template>
    <BaseProviderLayout :title="title">
        <div class="provider-site-layout">
            <!-- Header -->
            <header class="header">
                <div class="header-content">
                    <!-- Provider Logo/Name -->
                    <AppLink :href="homeUrl" class="provider-brand">
                        <img v-if="__provider?.logo" :src="__provider.logo" :alt="__provider?.business_name" class="provider-logo" />
                        <Avatar v-else-if="__provider?.avatar" :image="__provider.avatar" shape="circle" class="!w-10 !h-10" />
                        <Avatar v-else :label="getInitials(__provider?.business_name || '')" shape="circle"
                            class="avatar-fallback" />
                        <span class="provider-name">{{ __provider?.business_name }}</span>
                    </AppLink>

                    <!-- Main Navigation -->
                    <nav class="main-nav">
                        <AppLink :href="homeUrl" class="nav-link" :class="{ active: isActive('/') }">
                            Home
                        </AppLink>
                        <AppLink :href="servicesUrl" class="nav-link" :class="{ active: isActive('/services') }">
                            Services
                        </AppLink>
                        <AppLink v-if="hasEvents" :href="eventsUrl" class="nav-link" :class="{ active: isActive('/events') }">
                            Events
                        </AppLink>
                        <AppLink :href="reviewsUrl" class="nav-link" :class="{ active: isActive('/reviews') }">
                            Reviews
                        </AppLink>
                    </nav>

                    <!-- Right Side -->
                    <div class="header-right">
                        <AppLink :href="getBookingUrl()">
                            <Button label="Book Now" class="btn-primary" />
                        </AppLink>

                        <div class="auth-nav">
                            <template v-if="user">
                                <AppLink :href="resolveUrl(client.bookings.index().url)" class="nav-link text-sm">
                                    My Bookings
                                </AppLink>
                            </template>
                            <template v-else>
                                <AppLink :href="login().url" class="nav-link text-sm">
                                    Login
                                </AppLink>
                            </template>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Banner (cover image) -->
            <div v-if="showBanner !== false && !slots.hero" class="layout-banner">
                <div class="layout-banner__cover" :style="__provider?.cover_image
                    ? { backgroundImage: `url(${__provider.cover_image})` }
                    : undefined">
                    <div class="layout-banner__overlay"></div>
                </div>
            </div>

            <!-- Hero Slot (replaces banner when provided) -->
            <slot name="hero" />

            <!-- Main Content -->
            <main class="main-content">
                <slot />
            </main>

            <!-- Footer -->
            <SiteFooter :copyrightName="__provider?.business_name" :showPoweredBy="true" />
        </div>
    </BaseProviderLayout>
</template>

<style scoped>
.provider-site-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.header {
    background-color: var(--provider-bg, white);
    border-bottom: 1px solid var(--provider-border, #e5e7eb);
    position: sticky;
    top: 0;
    z-index: 50;
}

.header-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}

.provider-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.provider-logo {
    height: 40px;
    width: auto;
    max-width: 140px;
    object-fit: contain;
}

.provider-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text);
}

.main-nav {
    display: flex;
    gap: 0.5rem;
}

.nav-link {
    padding: 0.5rem 1rem;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    transition: all 0.15s;
}

.nav-link:hover {
    color: var(--provider-text);
    background-color: #f3f4f6;
}

.nav-link.active {
    color: var(--provider-primary);
    background-color: var(--provider-primary-10);
    font-weight: 500;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.auth-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Primary button styling */
:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

/* Avatar fallback styling */
:deep(.avatar-fallback) {
    width: 2.5rem !important;
    height: 2.5rem !important;
    background-color: var(--provider-primary) !important;
}

.main-content {
    flex: 1;
    background-color: var(--provider-bg-secondary, #f9fafb);
}

.layout-banner {
    position: relative;
}

.layout-banner__cover {
    height: 200px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 50%, #d1d5db 100%);
    background-size: cover;
    background-position: center;
    position: relative;
}

.layout-banner__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 30%, rgba(249, 250, 251, 1) 100%);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .header-content {
        padding: 0 1rem;
        gap: 1rem;
    }

    .provider-name {
        display: none;
    }

    .main-nav {
        display: none;
    }
}
</style>

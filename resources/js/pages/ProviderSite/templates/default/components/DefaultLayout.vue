<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, useSlots, onMounted } from 'vue';
import type { User } from '@/types/models';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import SiteFooter from '@/components/common/SiteFooter.vue';
import FlashMessages from '@/components/error/FlashMessages.vue';
import site from '@/routes/providersite';
import { login } from '@/routes';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';
import client from '@/routes/client';
import { resolveUrl } from '@/utils/url';

defineProps<{
    title?: string;
    showBanner?: boolean;
}>();

const slots = useSlots();

const page = usePage();
const user = (page.props.auth as { user?: User } | undefined)?.user;
const __provider = page.props.__provider as {
    id: number;
    business_name: string;
    domain: string;
    avatar?: string;
    cover_image?: string;
    logo?: string;
    brand_primary_color?: string;
    brand_primary_rgb?: string;
    brand_hover_color?: string;
    brand_text_color?: string;
    brand_success_color?: string;
    brand_warning_color?: string;
    brand_danger_color?: string;
    brand_info_color?: string;
    brand_secondary_color?: string;
} | null;

// Dynamic branding styles
const brandingStyles = computed(() => {
    const p = __provider;
    if (!p) return {};

    const styles: Record<string, string> = {};

    if (p.brand_primary_color) {
        styles['--provider-primary'] = p.brand_primary_color;
    }
    if (p.brand_primary_rgb) {
        styles['--provider-primary-rgb'] = p.brand_primary_rgb;
    }
    if (p.brand_hover_color) {
        styles['--provider-primary-hover'] = p.brand_hover_color;
    }
    if (p.brand_text_color) {
        styles['--provider-text'] = p.brand_text_color;
    }
    if (p.brand_success_color) {
        styles['--provider-success'] = p.brand_success_color;
    }
    if (p.brand_warning_color) {
        styles['--provider-warning'] = p.brand_warning_color;
    }
    if (p.brand_danger_color) {
        styles['--provider-danger'] = p.brand_danger_color;
    }
    if (p.brand_info_color) {
        styles['--provider-info'] = p.brand_info_color;
    }
    if (p.brand_secondary_color) {
        styles['--provider-secondary'] = p.brand_secondary_color;
    }

    return styles;
});

const currentPath = computed(() => {
    const url = new URL(window.location.href);
    return url.pathname;
});

const isActive = (path: string) => {
    if (path === '/') {
        return currentPath.value === '/';
    }
    return currentPath.value.includes(path);
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

// Load Google Font
onMounted(() => {
    if (!document.querySelector('link[href*="fonts.googleapis.com/css2?family=Inter"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap';
        document.head.appendChild(link);
    }
});

const homeUrl = computed(() => {
    return site.home({ provider: __provider?.domain ?? '' }).url;
});

const servicesUrl = computed(() => {
    return site.services({ provider: __provider?.domain ?? '' }).url;
});

const reviewsUrl = computed(() => {
    return site.reviews({ provider: __provider?.domain ?? '' }).url;
});

const eventsUrl = computed(() => {
    return site.events({ provider: __provider?.domain ?? '' }).url;
});

const myBookingsUrl = computed(() => {
    return site.myBookings({ provider: __provider?.domain ?? '' }).url;
});

// Check if provider has events
const hasEvents = computed(() => {
    const pageProps = page.props as { hasEvents?: boolean };
    return pageProps.hasEvents ?? false;
});

const getBookingUrl = () => {
    return ProviderSiteBookingController.create({ provider: __provider?.domain ?? '' }).url;
};
</script>

<template>
    <Head :title="title ? `${title} | ${__provider?.business_name}` : __provider?.business_name" />
    <FlashMessages />

    <div class="default-layout" :style="brandingStyles">
        <!-- Header - Default Template Style -->
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
                    <AppLink :href="homeUrl" class="nav-link"
                        :class="{ active: isActive('/') && !isActive('/services') && !isActive('/reviews') && !isActive('/book') && !isActive('/events') }">
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
                            <AppLink :href="myBookingsUrl" class="nav-link text-sm">
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
                : null">
                <div class="layout-banner__overlay"></div>
            </div>
        </div>

        <!-- Hero Slot -->
        <slot name="hero" />

        <!-- Main Content -->
        <main class="main-content">
            <slot />
        </main>

        <!-- Footer -->
        <SiteFooter :copyrightName="__provider?.business_name" :showPoweredBy="true" />
    </div>
</template>

<style scoped>
.default-layout {
    /* Typography - Inter: Clean, professional sans-serif */
    --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-heading: var(--font-sans);

    /* Provider branding CSS variables */
    --provider-primary: #106B4F;
    --provider-primary-rgb: 16, 107, 79;
    --provider-primary-hover: #0D5A42;
    --provider-text: #0D1F1B;
    --provider-primary-10: rgba(var(--provider-primary-rgb), 0.1);
    --provider-primary-05: rgba(var(--provider-primary-rgb), 0.05);

    /* Semantic colors */
    --provider-success: #22C55E;
    --provider-warning: #F59E0B;
    --provider-danger: #EF4444;
    --provider-info: #3B82F6;
    --provider-secondary: #6B7280;

    /* Surface & Background colors */
    --provider-surface: #ffffff;
    --provider-background: #f9fafb;
    --provider-background-alt: #f3f4f6;

    /* Border colors */
    --provider-border: #e5e7eb;
    --provider-border-light: #f3f4f6;

    /* Text variants */
    --provider-text-muted: #6b7280;
    --provider-text-subtle: #9ca3af;
    --provider-text-body: #4b5563;

    /* Shadows */
    --provider-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
    --provider-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);

    min-height: 100vh;
    display: flex;
    flex-direction: column;
    font-family: var(--font-sans);
}

/* Typography hierarchy */
.default-layout :deep(h1) {
    font-family: var(--font-heading);
    font-weight: 700;
    letter-spacing: -0.025em;
    line-height: 1.2;
}

.default-layout :deep(h2) {
    font-family: var(--font-heading);
    font-weight: 600;
    letter-spacing: -0.02em;
    line-height: 1.3;
}

.default-layout :deep(h3),
.default-layout :deep(h4) {
    font-family: var(--font-heading);
    font-weight: 600;
    line-height: 1.4;
}

.header {
    background-color: var(--provider-surface);
    border-bottom: 1px solid var(--provider-border);
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
    color: var(--provider-text-muted);
    text-decoration: none;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    transition: all 0.15s;
}

.nav-link:hover {
    color: var(--provider-text);
    background-color: var(--provider-background-alt);
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
    background-color: var(--provider-background);
}

.layout-banner {
    position: relative;
}

.layout-banner__cover {
    height: 200px;
    background: linear-gradient(135deg, var(--provider-background-alt) 0%, var(--provider-border) 50%, var(--provider-border) 100%);
    background-size: cover;
    background-position: center;
    position: relative;
}

.layout-banner__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 30%, var(--provider-background) 100%);
}

/* PrimeVue Rating - Use provider primary color for stars */
:deep(.p-rating) {
    gap: 0.125rem;
}

:deep(.p-rating .p-rating-icon) {
    color: var(--provider-border);
    transition: color 0.15s;
}

:deep(.p-rating .p-rating-icon.p-rating-icon-active) {
    color: var(--provider-primary);
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

<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, useSlots, onMounted } from 'vue';
import type { User } from '@/types/models';
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

// Load Google Font - DM Sans: Elegant, geometric sans-serif
onMounted(() => {
    if (!document.querySelector('link[href*="fonts.googleapis.com/css2?family=DM+Sans"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap';
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

    <div class="minimal-layout" :style="brandingStyles">
        <!-- Header - Minimal Template Style: Clean, centered, no border -->
        <header class="header">
            <div class="header-content">
                <!-- Left: Auth -->
                <div class="header-left">
                    <template v-if="user">
                        <AppLink :href="myBookingsUrl" class="text-link">
                            My Bookings
                        </AppLink>
                    </template>
                    <template v-else>
                        <AppLink :href="login().url" class="text-link">
                            Login
                        </AppLink>
                    </template>
                </div>

                <!-- Center: Logo Only -->
                <AppLink :href="homeUrl" class="provider-brand">
                    <img v-if="__provider?.logo" :src="__provider.logo" :alt="__provider?.business_name" class="provider-logo" />
                    <span v-else class="provider-name">{{ __provider?.business_name }}</span>
                </AppLink>

                <!-- Right: Book Button -->
                <div class="header-right">
                    <AppLink :href="getBookingUrl()">
                        <Button label="Book" class="btn-primary" size="small" />
                    </AppLink>
                </div>
            </div>

            <!-- Minimal Navigation: Simple text links below header -->
            <nav class="minimal-nav">
                <AppLink :href="homeUrl" class="nav-link"
                    :class="{ active: isActive('/') && !isActive('/services') && !isActive('/reviews') && !isActive('/book') && !isActive('/events') }">
                    Home
                </AppLink>
                <span class="nav-divider">·</span>
                <AppLink :href="servicesUrl" class="nav-link" :class="{ active: isActive('/services') }">
                    Services
                </AppLink>
                <template v-if="hasEvents">
                    <span class="nav-divider">·</span>
                    <AppLink :href="eventsUrl" class="nav-link" :class="{ active: isActive('/events') }">
                        Events
                    </AppLink>
                </template>
                <span class="nav-divider">·</span>
                <AppLink :href="reviewsUrl" class="nav-link" :class="{ active: isActive('/reviews') }">
                    Reviews
                </AppLink>
            </nav>
        </header>

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
.minimal-layout {
    /* Typography - DM Sans: Elegant geometric sans-serif for minimal aesthetic */
    --font-sans: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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

    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #fff;
    font-family: var(--font-sans);
}

/* Typography hierarchy - Minimal: Clean, light weights, generous spacing */
.minimal-layout :deep(h1) {
    font-family: var(--font-heading);
    font-weight: 700;
    letter-spacing: -0.03em;
    line-height: 1.15;
}

.minimal-layout :deep(h2) {
    font-family: var(--font-heading);
    font-weight: 600;
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.minimal-layout :deep(h3),
.minimal-layout :deep(h4) {
    font-family: var(--font-heading);
    font-weight: 500;
    line-height: 1.4;
}

.minimal-layout :deep(p) {
    line-height: 1.7;
}

.header {
    background-color: white;
    padding: 1.5rem 0 1rem;
}

.header-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-left,
.header-right {
    flex: 1;
}

.header-right {
    display: flex;
    justify-content: flex-end;
}

.text-link {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.15s;
}

.text-link:hover {
    color: var(--provider-text);
}

.provider-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.provider-logo {
    height: 48px;
    width: auto;
    max-width: 180px;
    object-fit: contain;
}

.provider-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--provider-text);
    letter-spacing: -0.025em;
}

/* Minimal navigation: centered, simple */
.minimal-nav {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

.nav-link {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.15s;
}

.nav-link:hover {
    color: var(--provider-text);
}

.nav-link.active {
    color: var(--provider-primary);
    font-weight: 500;
}

.nav-divider {
    color: #d1d5db;
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

.main-content {
    flex: 1;
    background-color: #fff;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .header-content {
        padding: 0 1rem;
    }

    .header-left {
        display: none;
    }

    .provider-logo {
        height: 36px;
    }

    .provider-name {
        font-size: 1.25rem;
    }

    .minimal-nav {
        gap: 0.5rem;
    }

    .nav-link {
        font-size: 0.8125rem;
    }
}
</style>

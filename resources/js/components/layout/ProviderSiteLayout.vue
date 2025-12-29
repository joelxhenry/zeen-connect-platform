<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, useSlots } from 'vue';
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

// Dynamic branding styles - uses provider's custom colors or falls back to defaults
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



const homeUrl = computed(() => {
    return site.home({ provider: __provider?.domain ?? '' }).url;
});


const servicesUrl = computed(() => {
    return site.services({ provider: __provider?.domain ?? '' }).url;
});


const reviewsUrl = computed(() => {
    return site.reviews({ provider: __provider?.domain ?? '' }).url;
});


const getBookingUrl = () => {
    return ProviderSiteBookingController.create({ provider: __provider?.domain ?? '' }).url;
};



</script>

<template>

    <Head :title="title ? `${title} | ${__provider?.business_name}` : __provider?.business_name" />
    <FlashMessages />

    <div class="provider-site-layout" :style="brandingStyles">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <!-- Provider Logo/Name -->
                <AppLink :href="homeUrl" class="provider-brand">
                    <Avatar v-if="__provider?.avatar" :image="__provider.avatar" shape="circle" class="!w-10 !h-10" />
                    <Avatar v-else :label="getInitials(__provider?.business_name || '')" shape="circle"
                        class="avatar-fallback" />
                    <span class="provider-name">{{ __provider?.business_name }}</span>
                </AppLink>

                <!-- Main Navigation -->
                <nav class="main-nav">
                    <AppLink :href="homeUrl" class="nav-link"
                        :class="{ active: isActive('/') && !isActive('/services') && !isActive('/reviews') && !isActive('/book') }">
                        Home
                    </AppLink>
                    <AppLink :href="servicesUrl" class="nav-link" :class="{ active: isActive('/services') }">
                        Services
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
                : null">
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
</template>

<style scoped>
.provider-site-layout {
    /* Provider branding CSS variables - can be overridden dynamically */
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
}

.header {
    background-color: white;
    border-bottom: 1px solid #e5e7eb;
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
    background-color: #f9fafb;
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

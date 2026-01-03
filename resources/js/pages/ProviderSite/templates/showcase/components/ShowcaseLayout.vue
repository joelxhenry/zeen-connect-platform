<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
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
    transparentHeader?: boolean;
}>();

const mobileMenuOpen = ref(false);

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

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

// Load Google Fonts - Oswald (Condensed) + Space Mono + Inter
onMounted(() => {
    if (!document.querySelector('link[href*="fonts.googleapis.com/css2?family=Oswald"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Space+Mono:wght@400;700&family=Inter:wght@400;500;600&display=swap';
        document.head.appendChild(link);
    }
});
</script>

<template>
    <Head :title="title ? `${title} | ${__provider?.business_name}` : __provider?.business_name" />
    <FlashMessages />

    <div class="showcase-layout" :style="brandingStyles">
        <!-- Header -->
        <header class="header" :class="{ 'header--transparent': transparentHeader }">
            <div class="header-content">
                <!-- Logo -->
                <AppLink :href="homeUrl" class="provider-brand">
                    <img v-if="__provider?.logo" :src="__provider.logo" :alt="__provider?.business_name" class="provider-logo" />
                    <span v-else class="provider-name">{{ __provider?.business_name }}</span>
                </AppLink>

                <!-- Center Navigation -->
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
                    <template v-if="user">
                        <AppLink :href="myBookingsUrl" class="text-link">
                            My Bookings
                        </AppLink>
                    </template>
                    <template v-else>
                        <AppLink :href="login().url" class="text-link">
                            Sign In
                        </AppLink>
                    </template>
                    <AppLink :href="getBookingUrl()">
                        <Button label="BOOK NOW" class="btn-book" />
                    </AppLink>
                </div>

                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" @click="toggleMobileMenu">
                    <i :class="mobileMenuOpen ? 'pi pi-times' : 'pi pi-bars'"></i>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div v-if="mobileMenuOpen" class="mobile-menu">
                <nav class="mobile-nav">
                    <AppLink :href="homeUrl" class="mobile-nav-link" @click="mobileMenuOpen = false">
                        Home
                    </AppLink>
                    <AppLink :href="servicesUrl" class="mobile-nav-link" @click="mobileMenuOpen = false">
                        Services
                    </AppLink>
                    <AppLink v-if="hasEvents" :href="eventsUrl" class="mobile-nav-link" @click="mobileMenuOpen = false">
                        Events
                    </AppLink>
                    <AppLink :href="reviewsUrl" class="mobile-nav-link" @click="mobileMenuOpen = false">
                        Reviews
                    </AppLink>
                    <div class="mobile-nav-divider"></div>
                    <template v-if="user">
                        <AppLink :href="myBookingsUrl" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            My Bookings
                        </AppLink>
                    </template>
                    <template v-else>
                        <AppLink :href="login().url" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            Sign In
                        </AppLink>
                    </template>
                    <AppLink :href="getBookingUrl()" class="mobile-book-btn" @click="mobileMenuOpen = false">
                        <Button label="BOOK NOW" class="btn-book w-full" />
                    </AppLink>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <slot />
        </main>

        <!-- Footer -->
        <SiteFooter :copyrightName="__provider?.business_name" :showPoweredBy="true" class="showcase-footer" />
    </div>
</template>

<style scoped>
.showcase-layout {
    /* Typography - Oswald (Condensed) for headings, Space Mono for CTAs/prices, Inter for body */
    --font-heading: 'Oswald', 'Barlow Condensed', sans-serif;
    --font-mono: 'Space Mono', 'JetBrains Mono', monospace;
    --font-body: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

    /* Provider branding CSS variables */
    --provider-primary: #1a1a1a;
    --provider-primary-rgb: 26, 26, 26;
    --provider-primary-hover: #333333;
    --provider-text: #1a1a1a;
    --provider-primary-10: rgba(var(--provider-primary-rgb), 0.1);
    --provider-primary-05: rgba(var(--provider-primary-rgb), 0.05);

    /* Semantic colors */
    --provider-success: #22C55E;
    --provider-warning: #F59E0B;
    --provider-danger: #EF4444;
    --provider-info: #3B82F6;
    --provider-secondary: #6B7280;

    /* Surface colors */
    --provider-background: #fafafa;
    --provider-surface: #ffffff;
    --provider-border: #e5e5e5;

    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--provider-background);
    font-family: var(--font-body);
}

/* Typography hierarchy - Showcase: Condensed uppercase headings, monospace accents */
.showcase-layout :deep(h1) {
    font-family: var(--font-heading);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    line-height: 1;
}

.showcase-layout :deep(h2) {
    font-family: var(--font-heading);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    line-height: 1.1;
}

.showcase-layout :deep(h3) {
    font-family: var(--font-heading);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    line-height: 1.2;
}

.showcase-layout :deep(h4),
.showcase-layout :deep(h5),
.showcase-layout :deep(h6) {
    font-family: var(--font-body);
    font-weight: 600;
    line-height: 1.3;
}

.showcase-layout :deep(p) {
    font-family: var(--font-body);
    line-height: 1.7;
}

/* Monospace styling for prices and CTAs */
.showcase-layout :deep(.mono-text),
.showcase-layout :deep(.price-display) {
    font-family: var(--font-mono);
    letter-spacing: 0.05em;
}

.header {
    background-color: var(--provider-surface);
    border-bottom: 1px solid var(--provider-border);
    position: sticky;
    top: 0;
    z-index: 50;
    transition: background-color 0.3s, border-color 0.3s;
}

.header--transparent {
    background-color: transparent;
    border-bottom-color: transparent;
    position: absolute;
    left: 0;
    right: 0;
}

.header--transparent .provider-name,
.header--transparent .nav-link,
.header--transparent .text-link {
    color: white;
}

.header--transparent .mobile-menu-btn {
    color: white;
    border-color: rgba(255, 255, 255, 0.5);
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}

.provider-brand {
    display: flex;
    align-items: center;
    text-decoration: none;
}

.provider-logo {
    height: 40px;
    width: auto;
    max-width: 160px;
    object-fit: contain;
}

.provider-name {
    font-family: var(--font-heading);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-text);
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.main-nav {
    display: flex;
    gap: 2rem;
}

.nav-link {
    color: var(--provider-text);
    text-decoration: none;
    font-family: var(--font-body);
    font-size: 0.875rem;
    font-weight: 500;
    transition: opacity 0.2s;
}

.nav-link:hover {
    opacity: 0.7;
}

.nav-link.active {
    font-weight: 600;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.text-link {
    color: var(--provider-text);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: opacity 0.2s;
}

.text-link:hover {
    opacity: 0.7;
}

/* Book Now button with monospace font */
:deep(.btn-book) {
    font-family: var(--font-mono) !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 0 !important;
    padding: 0.75rem 1.5rem;
}

:deep(.btn-book:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

/* Mobile menu button */
.mobile-menu-btn {
    display: none;
    background: transparent;
    border: 1px solid var(--provider-border);
    color: var(--provider-text);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem 0.75rem;
    border-radius: 0;
}

/* Mobile menu */
.mobile-menu {
    display: none;
    background: var(--provider-surface);
    border-top: 1px solid var(--provider-border);
    padding: 1rem 2rem;
}

.mobile-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.mobile-nav-link {
    display: block;
    padding: 0.75rem 0;
    color: var(--provider-text);
    text-decoration: none;
    font-size: 0.9375rem;
    font-weight: 500;
    border-bottom: 1px solid var(--provider-border);
}

.mobile-nav-divider {
    height: 1px;
    background: var(--provider-border);
    margin: 0.5rem 0;
}

.mobile-book-btn {
    display: block;
    text-decoration: none;
    margin-top: 1rem;
}

.main-content {
    flex: 1;
}

/* Footer styling */
:deep(.showcase-footer) {
    background-color: var(--provider-text) !important;
    color: rgba(255, 255, 255, 0.8) !important;
}

/* Mobile responsiveness */
@media (max-width: 1024px) {
    .main-nav {
        display: none;
    }

    .header-right {
        display: none;
    }

    .mobile-menu-btn {
        display: block;
    }

    .mobile-menu {
        display: block;
    }
}

@media (max-width: 768px) {
    .header-content {
        padding: 0 1rem;
        height: 64px;
    }

    .provider-logo {
        height: 32px;
    }

    .provider-name {
        font-size: 1rem;
    }

    .mobile-menu {
        padding: 1rem;
    }
}
</style>

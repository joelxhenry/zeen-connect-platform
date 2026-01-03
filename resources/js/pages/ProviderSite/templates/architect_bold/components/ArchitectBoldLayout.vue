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
    showBanner?: boolean;
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

// Load Google Font - Space Grotesk: Modern, geometric Neo-Grotesque
onMounted(() => {
    if (!document.querySelector('link[href*="fonts.googleapis.com/css2?family=Space+Grotesk"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap';
        document.head.appendChild(link);
    }
});
</script>

<template>
    <Head :title="title ? `${title} | ${__provider?.business_name}` : __provider?.business_name" />
    <FlashMessages />

    <div class="architect-bold-layout" :style="brandingStyles">
        <!-- Header - Architect Bold Style: Clean, professional, sharp corners -->
        <header class="header">
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
                        HOME
                    </AppLink>
                    <AppLink :href="servicesUrl" class="nav-link" :class="{ active: isActive('/services') }">
                        SERVICES
                    </AppLink>
                    <AppLink v-if="hasEvents" :href="eventsUrl" class="nav-link" :class="{ active: isActive('/events') }">
                        EVENTS
                    </AppLink>
                    <AppLink :href="reviewsUrl" class="nav-link" :class="{ active: isActive('/reviews') }">
                        REVIEWS
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
                        <Button label="Book Now" class="btn-primary" />
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
                        HOME
                    </AppLink>
                    <AppLink :href="servicesUrl" class="mobile-nav-link" @click="mobileMenuOpen = false">
                        SERVICES
                    </AppLink>
                    <AppLink v-if="hasEvents" :href="eventsUrl" class="mobile-nav-link" @click="mobileMenuOpen = false">
                        EVENTS
                    </AppLink>
                    <AppLink :href="reviewsUrl" class="mobile-nav-link" @click="mobileMenuOpen = false">
                        REVIEWS
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
                        <Button label="Book Now" class="btn-primary w-full" />
                    </AppLink>
                </nav>
            </div>
        </header>

        <!-- Hero Slot -->
        <slot name="hero" />

        <!-- Main Content -->
        <main class="main-content">
            <slot />
        </main>

        <!-- Footer -->
        <SiteFooter :copyrightName="__provider?.business_name" :showPoweredBy="true" class="architect-footer" />
    </div>
</template>

<style scoped>
.architect-bold-layout {
    /* Typography - Space Grotesk: Modern, geometric Neo-Grotesque for authoritative feel */
    --font-sans: 'Space Grotesk', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --font-heading: var(--font-sans);

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
    --provider-background: #f5f5f5;
    --provider-surface: #ffffff;
    --provider-border: #e5e5e5;

    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--provider-background);
    font-family: var(--font-sans);
    letter-spacing: 0.01em;
}

/* Typography hierarchy - Architect Bold: Strong, authoritative, uppercase sub-headings */
.architect-bold-layout :deep(h1) {
    font-family: var(--font-heading);
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.1;
}

.architect-bold-layout :deep(h2) {
    font-family: var(--font-heading);
    font-weight: 600;
    letter-spacing: 0.05em;
    line-height: 1.2;
    text-transform: uppercase;
}

.architect-bold-layout :deep(h3) {
    font-family: var(--font-heading);
    font-weight: 600;
    letter-spacing: 0.03em;
    line-height: 1.3;
    text-transform: uppercase;
}

.architect-bold-layout :deep(h4),
.architect-bold-layout :deep(h5),
.architect-bold-layout :deep(h6) {
    font-family: var(--font-sans);
    font-weight: 600;
    line-height: 1.4;
}

.architect-bold-layout :deep(p) {
    font-family: var(--font-sans);
    line-height: 1.7;
    letter-spacing: 0.01em;
}

.header {
    background-color: var(--provider-surface);
    border-bottom: 2px solid var(--provider-text);
    position: sticky;
    top: 0;
    z-index: 50;
}

.header-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
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
    height: 44px;
    width: auto;
    max-width: 160px;
    object-fit: contain;
}

.provider-name {
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--provider-text);
    letter-spacing: -0.02em;
}

.main-nav {
    display: flex;
    gap: 0.5rem;
}

.nav-link {
    padding: 0.625rem 1.25rem;
    color: var(--provider-text);
    text-decoration: none;
    font-size: 0.8125rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    transition: all 0.15s;
    border-radius: 0;
}

.nav-link:hover {
    background-color: var(--provider-primary-10);
}

.nav-link.active {
    background-color: var(--provider-text);
    color: var(--provider-surface);
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
    transition: opacity 0.15s;
}

.text-link:hover {
    opacity: 0.7;
}

/* Mobile menu button */
.mobile-menu-btn {
    display: none;
    background: transparent;
    border: 2px solid var(--provider-text);
    color: var(--provider-text);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0;
}

/* Mobile menu */
.mobile-menu {
    display: none;
    background: var(--provider-surface);
    border-top: 1px solid var(--provider-border);
    padding: 1rem;
}

.mobile-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.mobile-nav-link {
    display: block;
    padding: 0.875rem 1rem;
    color: var(--provider-text);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    border-radius: 0;
    transition: all 0.15s;
}

.mobile-nav-link:hover {
    background-color: var(--provider-primary-10);
}

.mobile-nav-divider {
    height: 1px;
    background: var(--provider-border);
    margin: 0.5rem 0;
}

.mobile-book-btn {
    display: block;
    text-decoration: none;
    margin-top: 0.5rem;
}

/* Primary button styling - Sharp corners */
:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    font-weight: 600;
    letter-spacing: 0.05em;
    border-radius: 0 !important;
    text-transform: uppercase;
    font-size: 0.8125rem;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.main-content {
    flex: 1;
}

/* Footer styling */
:deep(.architect-footer) {
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
        height: 36px;
    }

    .provider-name {
        font-size: 1.125rem;
    }
}
</style>

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

// Load Google Fonts - Cormorant Garamond (Serif) + Nunito Sans (Light Sans-Serif)
onMounted(() => {
    if (!document.querySelector('link[href*="fonts.googleapis.com/css2?family=Cormorant"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Nunito+Sans:wght@300;400;500;600&display=swap';
        document.head.appendChild(link);
    }
});
</script>

<template>
    <Head :title="title ? `${title} | ${__provider?.business_name}` : __provider?.business_name" />
    <FlashMessages />

    <div class="boutique-layout" :style="brandingStyles">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <!-- Logo (centered on desktop) -->
                <AppLink :href="homeUrl" class="provider-brand">
                    <img v-if="__provider?.logo" :src="__provider.logo" :alt="__provider?.business_name" class="provider-logo" />
                    <span v-else class="provider-name">{{ __provider?.business_name }}</span>
                </AppLink>

                <!-- Navigation -->
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
                    <AppLink :href="getBookingUrl()" class="nav-link nav-link--book">
                        Book Now
                    </AppLink>
                </nav>

                <!-- Right Side (auth) -->
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
                        <Button label="Book Now" class="btn-book w-full" />
                    </AppLink>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <slot />
        </main>

        <!-- Footer -->
        <SiteFooter :copyrightName="__provider?.business_name" :showPoweredBy="true" class="boutique-footer" />
    </div>
</template>

<style scoped>
.boutique-layout {
    /* Typography - Cormorant Garamond (Serif) for headings, Nunito Sans (Light) for body */
    --font-heading: 'Cormorant Garamond', Georgia, serif;
    --font-body: 'Nunito Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

    /* Provider branding CSS variables */
    --provider-primary: #8b7355;
    --provider-primary-rgb: 139, 115, 85;
    --provider-primary-hover: #6d5a43;
    --provider-text: #3d3d3d;
    --provider-primary-10: rgba(var(--provider-primary-rgb), 0.1);
    --provider-primary-05: rgba(var(--provider-primary-rgb), 0.05);

    /* Semantic colors */
    --provider-success: #7cb798;
    --provider-warning: #e5b567;
    --provider-danger: #d4726a;
    --provider-info: #6b9ac4;
    --provider-secondary: #8a8a8a;

    /* Surface colors - light and airy */
    --provider-background: #fdfcfb;
    --provider-surface: #ffffff;
    --provider-border: #ebe8e4;

    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--provider-background);
    font-family: var(--font-body);
    font-weight: 300;
    color: var(--provider-text);
}

/* Typography hierarchy - Boutique: Elegant serif headings, light sans body */
.boutique-layout :deep(h1) {
    font-family: var(--font-heading);
    font-weight: 500;
    letter-spacing: 0.02em;
    line-height: 1.2;
}

.boutique-layout :deep(h2) {
    font-family: var(--font-heading);
    font-weight: 500;
    letter-spacing: 0.02em;
    line-height: 1.25;
}

.boutique-layout :deep(h3) {
    font-family: var(--font-heading);
    font-weight: 500;
    letter-spacing: 0.01em;
    line-height: 1.3;
}

.boutique-layout :deep(h4),
.boutique-layout :deep(h5),
.boutique-layout :deep(h6) {
    font-family: var(--font-body);
    font-weight: 500;
    line-height: 1.4;
}

.boutique-layout :deep(p) {
    font-family: var(--font-body);
    font-weight: 300;
    line-height: 1.8;
}

/* Header */
.header {
    background-color: var(--provider-surface);
    border-bottom: 1px solid var(--provider-border);
    position: sticky;
    top: 0;
    z-index: 50;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    height: 80px;
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
    font-size: 1.5rem;
    font-weight: 500;
    color: var(--provider-text);
    letter-spacing: 0.02em;
}

.main-nav {
    display: flex;
    gap: 2.5rem;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.nav-link {
    color: var(--provider-secondary);
    text-decoration: none;
    font-family: var(--font-body);
    font-size: 0.875rem;
    font-weight: 400;
    letter-spacing: 0.03em;
    transition: color 0.2s;
}

.nav-link:hover {
    color: var(--provider-text);
}

.nav-link.active {
    color: var(--provider-text);
    font-weight: 500;
}

.nav-link--book {
    color: var(--provider-primary);
    font-weight: 500;
}

.nav-link--book:hover {
    color: var(--provider-primary-hover);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.text-link {
    color: var(--provider-secondary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 400;
    transition: color 0.2s;
}

.text-link:hover {
    color: var(--provider-text);
}

/* Book Now button */
:deep(.btn-book) {
    font-family: var(--font-body) !important;
    font-weight: 500;
    font-size: 0.875rem;
    letter-spacing: 0.03em;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 2rem !important;
    padding: 0.625rem 1.5rem;
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
    border-radius: 0.5rem;
}

/* Mobile menu */
.mobile-menu {
    display: none;
    background: var(--provider-surface);
    border-top: 1px solid var(--provider-border);
    padding: 1.5rem 2rem;
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
    font-size: 1rem;
    font-weight: 400;
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
:deep(.boutique-footer) {
    background-color: var(--provider-surface) !important;
    border-top: 1px solid var(--provider-border);
}

/* Mobile responsiveness */
@media (max-width: 900px) {
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
        height: 70px;
    }

    .provider-logo {
        height: 32px;
    }

    .provider-name {
        font-size: 1.25rem;
    }

    .mobile-menu {
        padding: 1rem;
    }
}
</style>

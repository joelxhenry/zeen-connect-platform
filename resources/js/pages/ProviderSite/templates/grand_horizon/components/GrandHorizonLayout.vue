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

const getBookingUrl = () => {
    return ProviderSiteBookingController.create({ provider: __provider?.domain ?? '' }).url;
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

// Load Google Fonts - Playfair Display (Serif) + Montserrat (Wide Sans-Serif)
onMounted(() => {
    if (!document.querySelector('link[href*="fonts.googleapis.com/css2?family=Playfair"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap';
        document.head.appendChild(link);
    }
});
</script>

<template>
    <Head :title="title ? `${title} | ${__provider?.business_name}` : __provider?.business_name" />
    <FlashMessages />

    <div class="grand-horizon-layout" :class="{ 'transparent-header': transparentHeader }" :style="brandingStyles">
        <!-- Header -->
        <header class="header" :class="{ 'header--transparent': transparentHeader }">
            <div class="header-content">
                <!-- Logo -->
                <AppLink :href="homeUrl" class="provider-brand">
                    <img v-if="__provider?.logo" :src="__provider.logo" :alt="__provider?.business_name" class="provider-logo" />
                    <span v-else class="provider-name">{{ __provider?.business_name }}</span>
                </AppLink>

                <!-- Navigation -->
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
                    <template v-if="user">
                        <AppLink :href="resolveUrl(client.bookings.index().url)" class="text-link">
                            My Bookings
                        </AppLink>
                    </template>
                    <template v-else>
                        <AppLink :href="login().url" class="text-link">
                            Sign In
                        </AppLink>
                    </template>
                    <AppLink :href="getBookingUrl()">
                        <Button label="Reserve" class="btn-reserve" />
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
                    <AppLink :href="reviewsUrl" class="mobile-nav-link" @click="mobileMenuOpen = false">
                        Reviews
                    </AppLink>
                    <div class="mobile-nav-divider"></div>
                    <template v-if="user">
                        <AppLink :href="resolveUrl(client.bookings.index().url)" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            My Bookings
                        </AppLink>
                    </template>
                    <template v-else>
                        <AppLink :href="login().url" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            Sign In
                        </AppLink>
                    </template>
                    <AppLink :href="getBookingUrl()" class="mobile-book-btn" @click="mobileMenuOpen = false">
                        <Button label="Reserve Now" class="btn-reserve w-full" />
                    </AppLink>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <slot />
        </main>

        <!-- Footer -->
        <SiteFooter :copyrightName="__provider?.business_name" :showPoweredBy="true" class="horizon-footer" />
    </div>
</template>

<style scoped>
.grand-horizon-layout {
    /* Typography - Playfair Display (Elegant Serif) for headings, Montserrat (Wide Sans) for body */
    --font-heading: 'Playfair Display', Georgia, serif;
    --font-body: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

    /* Provider branding CSS variables - Dark cinematic scheme */
    --provider-primary: #c9a87c;
    --provider-primary-rgb: 201, 168, 124;
    --provider-primary-hover: #b8956a;
    --provider-text: #1a1a1a;
    --provider-text-light: #ffffff;
    --provider-primary-10: rgba(var(--provider-primary-rgb), 0.1);
    --provider-primary-05: rgba(var(--provider-primary-rgb), 0.05);

    /* Semantic colors */
    --provider-success: #7cb798;
    --provider-warning: #e5b567;
    --provider-danger: #d4726a;
    --provider-info: #6b9ac4;
    --provider-secondary: #6a6a6a;

    /* Surface colors - Warm neutrals */
    --provider-background: #f8f6f3;
    --provider-surface: #ffffff;
    --provider-border: #e5e0d8;
    --provider-dark: #1a1a1a;

    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--provider-background);
    font-family: var(--font-body);
    font-weight: 400;
    color: var(--provider-text);
}

/* Typography hierarchy - Grand Horizon: Dramatic serif headings, wide-spaced sans body */
.grand-horizon-layout :deep(h1) {
    font-family: var(--font-heading);
    font-weight: 500;
    letter-spacing: 0.01em;
    line-height: 1.15;
}

.grand-horizon-layout :deep(h2) {
    font-family: var(--font-heading);
    font-weight: 500;
    letter-spacing: 0.01em;
    line-height: 1.2;
}

.grand-horizon-layout :deep(h3) {
    font-family: var(--font-heading);
    font-weight: 500;
    letter-spacing: 0.01em;
    line-height: 1.25;
}

.grand-horizon-layout :deep(h4),
.grand-horizon-layout :deep(h5),
.grand-horizon-layout :deep(h6) {
    font-family: var(--font-body);
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    line-height: 1.4;
}

.grand-horizon-layout :deep(p) {
    font-family: var(--font-body);
    font-weight: 400;
    line-height: 1.8;
    letter-spacing: 0.02em;
}

/* Header - Default solid */
.header {
    background-color: var(--provider-surface);
    border-bottom: 1px solid var(--provider-border);
    position: sticky;
    top: 0;
    z-index: 100;
    transition: background-color 0.3s, border-color 0.3s;
}

/* Header - Transparent mode (overlay on hero) */
.header--transparent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background: transparent;
    border-bottom: none;
}

.header--transparent .nav-link,
.header--transparent .text-link,
.header--transparent .provider-name {
    color: rgba(255, 255, 255, 0.9);
}

.header--transparent .nav-link:hover,
.header--transparent .text-link:hover {
    color: #ffffff;
}

.header--transparent .nav-link.active {
    color: #ffffff;
}

.header--transparent .mobile-menu-btn {
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.3);
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 3rem;
    height: 90px;
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
    height: 48px;
    width: auto;
    max-width: 180px;
    object-fit: contain;
}

.provider-name {
    font-family: var(--font-heading);
    font-size: 1.625rem;
    font-weight: 500;
    color: var(--provider-text);
    letter-spacing: 0.02em;
}

.main-nav {
    display: flex;
    gap: 3rem;
}

.nav-link {
    color: var(--provider-secondary);
    text-decoration: none;
    font-family: var(--font-body);
    font-size: 0.8125rem;
    font-weight: 500;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    transition: color 0.2s;
}

.nav-link:hover {
    color: var(--provider-text);
}

.nav-link.active {
    color: var(--provider-text);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.text-link {
    color: var(--provider-secondary);
    text-decoration: none;
    font-size: 0.8125rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    transition: color 0.2s;
}

.text-link:hover {
    color: var(--provider-text);
}

/* Reserve button */
:deep(.btn-reserve) {
    font-family: var(--font-body) !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 0 !important;
    padding: 0.875rem 2rem;
}

:deep(.btn-reserve:hover) {
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
    padding: 1.5rem 3rem;
}

.mobile-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.mobile-nav-link {
    display: block;
    padding: 1rem 0;
    color: var(--provider-text);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
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
    margin-top: 1.5rem;
}

.main-content {
    flex: 1;
}

/* Transparent header mode - content starts from top */
.transparent-header .main-content {
    margin-top: 0;
}

/* Footer styling */
:deep(.horizon-footer) {
    background-color: var(--provider-dark) !important;
    color: rgba(255, 255, 255, 0.7) !important;
}

:deep(.horizon-footer a) {
    color: rgba(255, 255, 255, 0.7) !important;
}

:deep(.horizon-footer a:hover) {
    color: #ffffff !important;
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

    .header-content {
        padding: 0 2rem;
    }
}

@media (max-width: 768px) {
    .header-content {
        padding: 0 1.5rem;
        height: 70px;
    }

    .provider-logo {
        height: 40px;
    }

    .provider-name {
        font-size: 1.375rem;
    }

    .mobile-menu {
        padding: 1.5rem;
    }
}
</style>

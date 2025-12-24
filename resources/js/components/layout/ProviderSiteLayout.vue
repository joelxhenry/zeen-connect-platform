<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, useSlots } from 'vue';
import type { User } from '@/types/models';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import SiteFooter from '@/components/common/SiteFooter.vue';
import ProviderSiteController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteController';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';

defineProps<{
    title?: string;
    showBanner?: boolean;
}>();

const slots = useSlots();

const page = usePage();
const user = (page.props.auth as { user?: User } | undefined)?.user;
const providerSiteProvider = page.props.providerSiteProvider as {
    id: number;
    business_name: string;
    slug: string;
    avatar?: string;
    cover_image?: string;
} | null;
const mainPlatformUrl = page.props.mainPlatformUrl as string;

const currentPath = computed(() => {
    const url = new URL(window.location.href);
    return url.pathname;
});

const isActive = (path: string) => {
    if (path === '/') {
        return currentPath.value === '/';
    }
    return currentPath.value.startsWith(path);
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>

    <Head :title="title ? `${title} | ${providerSiteProvider?.business_name}` : providerSiteProvider?.business_name" />

    <div class="provider-site-layout">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <!-- Provider Logo/Name -->
                <Link :href="ProviderSiteController.home({ provider: providerSiteProvider?.slug ?? '' }).url"
                    class="provider-brand">
                    <Avatar v-if="providerSiteProvider?.avatar" :image="providerSiteProvider.avatar" shape="circle"
                        class="!w-10 !h-10" />
                    <Avatar v-else :label="getInitials(providerSiteProvider?.business_name || '')" shape="circle"
                        class="!w-10 !h-10 !bg-[#106B4F]" />
                    <span class="provider-name">{{ providerSiteProvider?.business_name }}</span>
                </Link>

                <!-- Main Navigation -->
                <nav class="main-nav">
                    <Link :href="ProviderSiteController.home({ provider: providerSiteProvider?.slug ?? '' }).url"
                        class="nav-link"
                        :class="{ active: isActive('/') && !isActive('/services') && !isActive('/reviews') && !isActive('/book') }">
                        Home
                    </Link>
                    <Link :href="ProviderSiteController.services({ provider: providerSiteProvider?.slug ?? '' }).url"
                        class="nav-link" :class="{ active: isActive('/services') }">
                        Services
                    </Link>
                    <Link :href="ProviderSiteController.reviews({ provider: providerSiteProvider?.slug ?? '' }).url"
                        class="nav-link" :class="{ active: isActive('/reviews') }">
                        Reviews
                    </Link>
                </nav>

                <!-- Right Side -->
                <div class="header-right">
                    <Link
                        :href="ProviderSiteBookingController.create({ provider: providerSiteProvider?.slug ?? '' }).url">
                        <Button label="Book Now" class="!bg-[#106B4F] !border-[#106B4F]" />
                    </Link>

                    <div class="auth-nav">
                        <template v-if="user">
                            <Link :href="`${mainPlatformUrl}/dashboard`" class="nav-link text-sm">
                                My Bookings
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="`${mainPlatformUrl}/login`" class="nav-link text-sm">
                                Login
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <!-- Banner (cover image) -->
        <div v-if="showBanner !== false && !slots.hero" class="layout-banner">
            <div class="layout-banner__cover"
                :style="providerSiteProvider?.cover_image
                    ? { backgroundImage: `url(${providerSiteProvider.cover_image})` }
                    : { backgroundImage: `url('https://images.unsplash.com/photo-1600051831735-9e0b949949b6?q=80&w=1074&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D')` }">
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
        <SiteFooter :copyrightName="providerSiteProvider?.business_name" :showPoweredBy="true" />
    </div>
</template>

<style scoped>
.provider-site-layout {
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
    color: #0D1F1B;
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
    color: #0D1F1B;
    background-color: #f3f4f6;
}

.nav-link.active {
    color: #106B4F;
    background-color: rgba(16, 107, 79, 0.1);
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

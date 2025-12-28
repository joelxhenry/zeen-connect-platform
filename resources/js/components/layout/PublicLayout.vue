<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import type { User } from '@/types/models';
import SiteFooter from '@/components/common/SiteFooter.vue';
import FlashMessages from '@/components/error/FlashMessages.vue';
import { home, pricing, forYou, foundingMembers, login, logout } from '@/routes';
import provider from '@/routes/provider';
import client from '@/routes/client';

defineProps<{
    title?: string;
}>();

const page = usePage();
const user = (page.props.auth as { user?: User } | undefined)?.user;

</script>

<template>
    <Head :title="title" />
    <FlashMessages />



    <div class="public-layout">
        <!-- Header -->
        <header class="site-header">
            <div class="header-container">
                <AppLink :href="home.url()" class="logo">Zeen</AppLink>

                <nav class="main-nav">
                    <AppLink :href="home.url()" class="nav-link">Home</AppLink>
                    <AppLink :href="forYou.url()" class="nav-link">For You</AppLink>
                    <AppLink :href="pricing.url()" class="nav-link">Pricing</AppLink>
                </nav>

                <div class="auth-nav">
                    <template v-if="user">
                        <AppLink v-if="user.role === 'provider'" :href="provider.dashboard.url()" class="nav-link">Console</AppLink>
                        <AppLink v-else :href="client.dashboard.url()" class="nav-link">Dashboard</AppLink>
                        <AppLink :href="logout.url()" method="post" as="button" class="logout-btn">Logout</AppLink>
                    </template>
                    <template v-else>
                        <AppLink :href="foundingMembers.url()" class="cta-btn outline">Join Waitlist</AppLink>
                        <AppLink :href="login.url()" class="cta-btn filled">Log In</AppLink>
                    </template>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <slot />
        </main>

        <!-- Footer -->
        <SiteFooter />
    </div>
</template>

<style scoped>
.public-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Header */
.site-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: linear-gradient(180deg, #0D1F1B 0%, #0D1F1B 90%, transparent 100%);
    padding-bottom: 1rem;
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2rem;
}

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    font-style: italic;
    color: #1ABC9C;
    text-decoration: none;
}

.main-nav {
    display: flex;
    gap: 0.5rem;
}

.nav-link {
    padding: 0.5rem 1rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: color 0.2s;
}

.nav-link:hover {
    color: white;
}

.auth-nav {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.logout-btn {
    padding: 0.5rem 1rem;
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: color 0.2s;
}

.logout-btn:hover {
    color: white;
}

.cta-btn {
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s;
}

.cta-btn.outline {
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: transparent;
}

.cta-btn.outline:hover {
    border-color: rgba(255, 255, 255, 0.6);
    background: rgba(255, 255, 255, 0.05);
}

.cta-btn.filled {
    background: #106B4F;
    color: white;
    border: 1px solid #106B4F;
}

.cta-btn.filled:hover {
    background: #0D5A42;
    border-color: #0D5A42;
}

.main-content {
    flex: 1;
    padding-top: 64px;
}

/* Responsive */
@media (max-width: 768px) {
    .header-container {
        padding: 0 1rem;
        height: 56px;
    }

    .main-nav {
        display: none;
    }

    .cta-btn {
        padding: 0.375rem 1rem;
        font-size: 0.8125rem;
    }

    .main-content {
        padding-top: 56px;
    }
}
</style>

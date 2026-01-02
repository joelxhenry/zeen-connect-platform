<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
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

const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

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

                <!-- Mobile Menu Button -->
                <button @click="toggleMobileMenu" class="mobile-menu-btn" aria-label="Toggle menu">
                    <svg v-if="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="menu-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="menu-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Mobile Menu Overlay -->
        <Transition name="fade">
            <div v-if="mobileMenuOpen" class="mobile-menu-overlay" @click="closeMobileMenu"></div>
        </Transition>

        <!-- Mobile Menu Drawer -->
        <Transition name="slide">
            <nav v-if="mobileMenuOpen" class="mobile-menu">
                <div class="mobile-menu-content">
                    <AppLink :href="home.url()" class="mobile-nav-link" @click="closeMobileMenu">Home</AppLink>
                    <AppLink :href="forYou.url()" class="mobile-nav-link" @click="closeMobileMenu">For You</AppLink>
                    <AppLink :href="pricing.url()" class="mobile-nav-link" @click="closeMobileMenu">Pricing</AppLink>

                    <div class="mobile-menu-divider"></div>

                    <template v-if="user">
                        <AppLink v-if="user.role === 'provider'" :href="provider.dashboard.url()" class="mobile-nav-link" @click="closeMobileMenu">Console</AppLink>
                        <AppLink v-else :href="client.dashboard.url()" class="mobile-nav-link" @click="closeMobileMenu">Dashboard</AppLink>
                        <AppLink :href="logout.url()" method="post" as="button" class="mobile-nav-link logout" @click="closeMobileMenu">Logout</AppLink>
                    </template>
                    <template v-else>
                        <AppLink :href="foundingMembers.url()" class="mobile-cta-btn outline" @click="closeMobileMenu">Join Waitlist</AppLink>
                        <AppLink :href="login.url()" class="mobile-cta-btn filled" @click="closeMobileMenu">Log In</AppLink>
                    </template>
                </div>
            </nav>
        </Transition>

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

/* Mobile Menu Button */
.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0.5rem;
    margin-right: -0.5rem;
}

.menu-icon {
    width: 24px;
    height: 24px;
}

/* Mobile Menu Overlay */
.mobile-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1100;
}

/* Mobile Menu Drawer */
.mobile-menu {
    position: fixed;
    top: 56px;
    right: 0;
    bottom: 0;
    width: 280px;
    max-width: 85vw;
    background: #0D1F1B;
    border-left: 1px solid rgba(255, 255, 255, 0.1);
    z-index: 1200;
    overflow-y: auto;
}

.mobile-menu-content {
    padding: 1.5rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.mobile-nav-link {
    padding: 0.875rem 1rem;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    font-size: 1rem;
    font-weight: 500;
    border-radius: 6px;
    transition: background 0.2s;
    display: block;
}

.mobile-nav-link:hover,
.mobile-nav-link:active {
    background: rgba(255, 255, 255, 0.05);
}

.mobile-nav-link.logout {
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    font-family: inherit;
    cursor: pointer;
    color: rgba(255, 255, 255, 0.7);
}

.mobile-menu-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 0.75rem 0;
}

.mobile-cta-btn {
    padding: 0.875rem 1.25rem;
    font-size: 0.9375rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s;
    text-align: center;
    display: block;
    margin-top: 0.5rem;
}

.mobile-cta-btn.outline {
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: transparent;
}

.mobile-cta-btn.outline:hover {
    border-color: rgba(255, 255, 255, 0.6);
    background: rgba(255, 255, 255, 0.05);
}

.mobile-cta-btn.filled {
    background: #106B4F;
    color: white;
    border: 1px solid #106B4F;
}

.mobile-cta-btn.filled:hover {
    background: #0D5A42;
    border-color: #0D5A42;
}

/* Transitions */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.slide-enter-active, .slide-leave-active {
    transition: transform 0.3s ease-out;
}

.slide-enter-from, .slide-leave-to {
    transform: translateX(100%);
}

/* Responsive */
@media (max-width: 768px) {
    .header-container {
        padding: 0 1rem;
        height: 56px;
    }

    .main-nav,
    .auth-nav {
        display: none;
    }

    .mobile-menu-btn {
        display: block;
    }

    .main-content {
        padding-top: 56px;
    }
}
</style>

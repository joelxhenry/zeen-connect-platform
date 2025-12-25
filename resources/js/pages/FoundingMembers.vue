<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FoundingHero from '@/components/founding/FoundingHero.vue';
import FoundingVision from '@/components/founding/FoundingVision.vue';
import FoundingFeatures from '@/components/founding/FoundingFeatures.vue';
import FoundingPerks from '@/components/founding/FoundingPerks.vue';
import FoundingPioneers from '@/components/founding/FoundingPioneers.vue';
import FoundingCta from '@/components/founding/FoundingCta.vue';
import { home, pricing, forYou, login } from '@/routes';

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<template>
    <Head title="Founding Members - Join the Waitlist" />

    <div class="page">
        <!-- Header -->
        <header class="site-header">
            <div class="header-container">
                <Link :href="home.url()" class="logo">Zeen</Link>

                <nav class="main-nav">
                    <Link :href="home.url()" class="nav-link">Home</Link>
                    <Link :href="forYou.url()" class="nav-link">For You</Link>
                    <Link :href="pricing.url()" class="nav-link">Pricing</Link>
                </nav>

                <div class="auth-nav">
                    <span class="header-badge">Founding Members</span>
                    <Link :href="login.url()" class="cta-btn">Log In</Link>
                </div>
            </div>
        </header>

        <main class="main-content">
            <FoundingHero />
            <FoundingVision />
            <FoundingFeatures />
            <FoundingPerks />
            <FoundingPioneers @join="scrollToTop" />
            <FoundingCta />
        </main>
    </div>
</template>

<style scoped>
.page {
    min-height: 100vh;
    background: #fafbfc;
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
    gap: 1rem;
}

.header-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #1ABC9C;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 0.375rem 0.75rem;
    border: 1px solid rgba(26, 188, 156, 0.3);
    border-radius: 4px;
}

.cta-btn {
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 6px;
    background: #106B4F;
    color: white;
    border: 1px solid #106B4F;
    transition: all 0.2s;
}

.cta-btn:hover {
    background: #0D5A42;
    border-color: #0D5A42;
}

.main-content {
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

    .header-badge {
        display: none;
    }

    .main-content {
        padding-top: 56px;
    }
}
</style>

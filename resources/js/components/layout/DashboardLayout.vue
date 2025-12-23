<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
}>();

const navItems = [
    { label: 'Dashboard', icon: 'pi pi-home', route: '/dashboard' },
    { label: 'Profile', icon: 'pi pi-user', route: '/dashboard/profile' },
    { label: 'Bookings', icon: 'pi pi-calendar', route: '/dashboard/bookings' },
    { label: 'Reviews', icon: 'pi pi-star', route: '/dashboard/reviews' },
    { label: 'Favorites', icon: 'pi pi-heart', route: '/dashboard/favorites' },
];
</script>

<template>
    <Head :title="title" />

    <div class="dashboard-layout">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-left">
                    <Link href="/" class="logo">Zeen</Link>

                    <nav class="main-nav">
                        <Link
                            v-for="item in navItems"
                            :key="item.route"
                            :href="item.route"
                            class="nav-link"
                        >
                            <i :class="item.icon"></i>
                            {{ item.label }}
                        </Link>
                    </nav>
                </div>

                <div class="header-right">
                    <Link href="/explore" class="explore-link">Find Services</Link>
                    <Link href="/logout" method="post" as="button" class="logout-btn">Logout</Link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.dashboard-layout {
    min-height: 100vh;
    background-color: #F5F5F5;
}

.header {
    background-color: white;
    border-bottom: 1px solid #e5e7eb;
}

.header-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    color: #106B4F;
    text-decoration: none;
}

.main-nav {
    display: flex;
    gap: 0.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    border-radius: 0.375rem;
}

.nav-link:hover {
    background-color: #F5F5F5;
    color: #0D1F1B;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.explore-link {
    padding: 0.5rem 1rem;
    color: #106B4F;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.logout-btn {
    padding: 0.5rem 1rem;
    background: none;
    border: none;
    color: #6b7280;
    font-size: 0.875rem;
    cursor: pointer;
}

.logout-btn:hover {
    color: #0D1F1B;
}

.main-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
}
</style>

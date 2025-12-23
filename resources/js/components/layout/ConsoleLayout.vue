<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
}>();

const navItems = [
    { label: 'Dashboard', icon: 'pi pi-home', route: '/console' },
    { label: 'Profile', icon: 'pi pi-user', route: '/console/profile' },
    { label: 'Services', icon: 'pi pi-th-large', route: '/console/services' },
    { label: 'Availability', icon: 'pi pi-clock', route: '/console/availability' },
    { label: 'Bookings', icon: 'pi pi-calendar', route: '/console/bookings' },
    { label: 'Payments', icon: 'pi pi-wallet', route: '/console/payments' },
    { label: 'Reviews', icon: 'pi pi-star', route: '/console/reviews' },
];
</script>

<template>
    <Head :title="title" />

    <div class="console-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <Link href="/" class="logo">Zeen</Link>
                <span class="badge">Provider</span>
            </div>

            <nav class="sidebar-nav">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="item.route"
                    class="nav-item"
                >
                    <i :class="item.icon"></i>
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <div class="sidebar-footer">
                <Link href="/logout" method="post" as="button" class="logout-btn">
                    <i class="pi pi-sign-out"></i>
                    <span>Logout</span>
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-wrapper">
            <header class="top-header">
                <h1>{{ title || 'Dashboard' }}</h1>
                <Link href="/dashboard" class="switch-link">Switch to Client</Link>
            </header>

            <main class="main-content">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.console-layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 240px;
    background-color: white;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logo {
    font-size: 1.25rem;
    font-weight: 700;
    color: #106B4F;
    text-decoration: none;
}

.badge {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    background-color: rgba(16, 107, 79, 0.1);
    color: #106B4F;
    border-radius: 1rem;
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #6b7280;
    text-decoration: none;
}

.nav-item:hover {
    background-color: #F5F5F5;
    color: #0D1F1B;
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.75rem;
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    text-align: left;
}

.logout-btn:hover {
    color: #0D1F1B;
}

.main-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: #F5F5F5;
}

.top-header {
    padding: 1rem 1.5rem;
    background-color: white;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.top-header h1 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #0D1F1B;
}

.switch-link {
    font-size: 0.875rem;
    color: #6b7280;
    text-decoration: none;
}

.switch-link:hover {
    color: #106B4F;
}

.main-content {
    flex: 1;
    padding: 1.5rem;
}
</style>

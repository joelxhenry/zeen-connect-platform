<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
}>();

const navItems = [
    { label: 'Dashboard', icon: 'pi pi-home', route: '/admin' },
    { label: 'Users', icon: 'pi pi-users', route: '/admin/users' },
    { label: 'Providers', icon: 'pi pi-briefcase', route: '/admin/providers' },
    { label: 'Bookings', icon: 'pi pi-calendar', route: '/admin/bookings' },
    { label: 'Payments', icon: 'pi pi-wallet', route: '/admin/payments' },
    { label: 'Payouts', icon: 'pi pi-money-bill', route: '/admin/payouts' },
    { label: 'Reviews', icon: 'pi pi-star', route: '/admin/reviews' },
    { label: 'Categories', icon: 'pi pi-tags', route: '/admin/categories' },
    { label: 'Locations', icon: 'pi pi-map-marker', route: '/admin/locations' },
    { label: 'Settings', icon: 'pi pi-cog', route: '/admin/settings' },
];
</script>

<template>
    <Head :title="title ? `${title} - Admin` : 'Admin'" />

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <Link href="/admin" class="logo">Zeen Admin</Link>
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
            </header>

            <main class="main-content">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.admin-layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 240px;
    background-color: #0D1F1B;
    color: white;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1ABC9C;
    text-decoration: none;
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
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
}

.nav-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.75rem;
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
    text-align: left;
}

.logout-btn:hover {
    color: white;
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
}

.top-header h1 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #0D1F1B;
}

.main-content {
    flex: 1;
    padding: 1.5rem;
}
</style>

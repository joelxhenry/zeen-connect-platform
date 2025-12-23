<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types/models';
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const user = page.props.auth?.user;
const sidebarCollapsed = ref(false);
const mobileMenuOpen = ref(false);
const userMenu = ref();

const vTooltip = Tooltip;

const currentPath = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.pathname;
    }
    return '/admin';
});

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const menuItems = [
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

const userMenuItems = ref([
    {
        label: 'Profile',
        icon: 'pi pi-user',
        command: () => window.location.href = '/admin/profile'
    },
    { separator: true },
    {
        label: 'Sign Out',
        icon: 'pi pi-sign-out',
        command: () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrf) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = csrf;
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
        }
    }
]);

const toggleUserMenu = (event: Event) => {
    userMenu.value.toggle(event);
};

const isActive = (route: string) => {
    if (route === '/admin') {
        return currentPath.value === '/admin';
    }
    return currentPath.value.startsWith(route);
};

const getInitials = (name: string | undefined) => {
    if (!name) return 'A';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <Head :title="title ? `${title} - Admin` : 'Admin'" />
    <div class="admin-layout" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
        <!-- Mobile Header -->
        <header class="mobile-header">
            <button class="mobile-menu-btn" @click="toggleMobileMenu">
                <i class="pi pi-bars"></i>
            </button>
            <span class="mobile-logo">Zeen Admin</span>
            <div class="mobile-user" @click="toggleUserMenu">
                <Avatar
                    :label="getInitials(user?.name)"
                    shape="circle"
                    size="normal"
                />
            </div>
        </header>

        <!-- Mobile Menu Overlay -->
        <div
            v-if="mobileMenuOpen"
            class="mobile-overlay"
            @click="toggleMobileMenu"
        ></div>

        <!-- Sidebar -->
        <aside :class="['sidebar', { 'mobile-open': mobileMenuOpen }]">
            <div class="sidebar-header">
                <Link href="/admin" class="logo-link">
                    <Avatar icon="pi pi-shield" shape="square" size="large" class="admin-avatar" />
                    <span v-if="!sidebarCollapsed" class="logo-text">Admin</span>
                </Link>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span v-if="!sidebarCollapsed" class="nav-section-title">Management</span>
                </div>
                <Link
                    v-for="item in menuItems"
                    :key="item.route"
                    :href="item.route"
                    v-tooltip.right="sidebarCollapsed ? item.label : null"
                    :class="['nav-item', { 'nav-item-active': isActive(item.route) }]"
                    @click="mobileMenuOpen = false"
                >
                    <span class="nav-icon-wrapper">
                        <i :class="[item.icon, 'nav-icon']"></i>
                    </span>
                    <span v-if="!sidebarCollapsed" class="nav-label">{{ item.label }}</span>
                </Link>
            </nav>

            <div class="sidebar-footer">
                <Button
                    :icon="sidebarCollapsed ? 'pi pi-angle-double-right' : 'pi pi-angle-double-left'"
                    severity="secondary"
                    text
                    rounded
                    @click="toggleSidebar"
                    v-tooltip.right="sidebarCollapsed ? 'Expand' : null"
                />
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-wrapper">
            <!-- Header -->
            <header class="top-header">
                <div class="header-left">
                    <div class="page-info">
                        <h1 class="page-title">{{ title || 'Dashboard' }}</h1>
                        <nav class="breadcrumb-nav">
                            <Link href="/admin" class="breadcrumb-link">Admin</Link>
                            <span class="breadcrumb-sep">/</span>
                            <span class="breadcrumb-current">{{ title || 'Dashboard' }}</span>
                        </nav>
                    </div>
                </div>

                <div class="header-right">
                    <div class="header-divider"></div>

                    <div class="user-menu-trigger" @click="toggleUserMenu">
                        <Avatar
                            :label="getInitials(user?.name)"
                            shape="circle"
                            class="admin-user-avatar"
                        />
                        <div class="user-info">
                            <span class="user-name">{{ user?.name }}</span>
                            <span class="user-role">Administrator</span>
                        </div>
                        <i class="pi pi-chevron-down chevron-icon"></i>
                    </div>
                    <Menu ref="userMenu" :model="userMenuItems" :popup="true" />
                </div>
            </header>

            <!-- Page Content -->
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
    background-color: var(--p-surface-100);
}

/* Sidebar - Dark Theme for Admin */
.sidebar {
    width: 260px;
    background-color: var(--p-surface-900);
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
}

.sidebar-collapsed .sidebar {
    width: 72px;
}

.sidebar-header {
    height: 64px;
    display: flex;
    align-items: center;
    padding: 0 1rem;
    border-bottom: 1px solid var(--p-surface-700);
}

.logo-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.admin-avatar {
    background-color: var(--p-red-500);
    color: white;
}

.logo-text {
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: white;
}

/* Navigation */
.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}

.nav-section {
    padding: 0 1rem;
    margin-bottom: 0.5rem;
}

.nav-section-title {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--p-surface-500);
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 0.625rem 1rem;
    margin: 0.125rem 0.5rem;
    color: var(--p-surface-400);
    text-decoration: none;
    transition: all 0.2s ease;
    gap: 0.75rem;
    border-radius: 0.5rem;
}

.nav-item:hover {
    color: white;
    background-color: var(--p-surface-800);
}

.nav-item-active {
    color: white;
    background-color: var(--p-red-500);
}

.nav-icon-wrapper {
    position: relative;
    width: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-icon {
    font-size: 1.125rem;
}

.nav-label {
    font-size: 0.875rem;
    font-weight: 500;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid var(--p-surface-700);
    display: flex;
    justify-content: center;
}

.sidebar-footer :deep(.p-button) {
    color: var(--p-surface-400);
}

.sidebar-footer :deep(.p-button:hover) {
    color: white;
}

/* Main Wrapper */
.main-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    margin-left: 260px;
    transition: margin-left 0.3s ease;
}

.sidebar-collapsed .main-wrapper {
    margin-left: 72px;
}

/* Header */
.top-header {
    height: 64px;
    background-color: var(--p-surface-0);
    border-bottom: 1px solid var(--p-surface-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.5rem;
    position: sticky;
    top: 0;
    z-index: 50;
}

.header-left {
    display: flex;
    align-items: center;
}

.page-info {
    display: flex;
    flex-direction: column;
}

.page-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0;
    line-height: 1.2;
}

.breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    margin-top: 0.125rem;
}

.breadcrumb-link {
    color: var(--p-surface-500);
    text-decoration: none;
}

.breadcrumb-link:hover {
    color: var(--p-red-500);
}

.breadcrumb-sep {
    color: var(--p-surface-400);
}

.breadcrumb-current {
    color: var(--p-surface-600);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.header-divider {
    width: 1px;
    height: 24px;
    background-color: var(--p-surface-200);
    margin: 0 0.75rem;
}

.user-menu-trigger {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    padding: 0.375rem 0.5rem;
    border-radius: 0.5rem;
    transition: background-color 0.2s;
}

.user-menu-trigger:hover {
    background-color: var(--p-surface-100);
}

.admin-user-avatar {
    background-color: var(--p-red-500);
    color: white;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-size: 0.875rem;
    color: var(--p-surface-900);
    font-weight: 500;
    line-height: 1.2;
}

.user-role {
    font-size: 0.6875rem;
    color: var(--p-red-500);
}

.chevron-icon {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

/* Main Content */
.main-content {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
}

/* Mobile Styles */
.mobile-header {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 56px;
    background-color: var(--p-surface-900);
    align-items: center;
    justify-content: space-between;
    padding: 0 1rem;
    z-index: 90;
}

.mobile-menu-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    border-radius: 0.5rem;
}

.mobile-logo {
    font-size: 1.125rem;
    font-weight: 700;
    color: white;
}

.mobile-user {
    cursor: pointer;
}

.mobile-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 95;
}

@media (max-width: 1024px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.mobile-open {
        transform: translateX(0);
    }

    .main-wrapper {
        margin-left: 0;
    }

    .sidebar-collapsed .main-wrapper {
        margin-left: 0;
    }

    .mobile-header {
        display: flex;
    }

    .mobile-overlay {
        display: block;
    }

    .main-content {
        padding-top: calc(56px + 1.5rem);
    }

    .top-header {
        display: none;
    }

    .user-info {
        display: none;
    }

    .chevron-icon {
        display: none;
    }
}
</style>

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
const provider = page.props.auth?.provider;
const sidebarCollapsed = ref(false);
const mobileMenuOpen = ref(false);
const userMenu = ref();

const vTooltip = Tooltip;

const currentPath = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.pathname;
    }
    return '/console';
});

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const menuItems = [
    { label: 'Dashboard', icon: 'pi pi-home', route: '/console' },
    { label: 'Profile', icon: 'pi pi-user', route: '/console/profile' },
    { label: 'Services', icon: 'pi pi-th-large', route: '/console/services' },
    { label: 'Portfolio', icon: 'pi pi-images', route: '/console/portfolios' },
    { label: 'Bookings', icon: 'pi pi-calendar', route: '/console/bookings', badge: '3' },
    { label: 'Payments', icon: 'pi pi-wallet', route: '/console/payments' },
    { label: 'Availability', icon: 'pi pi-clock', route: '/console/availability' },
    { label: 'Settings', icon: 'pi pi-cog', route: '/console/settings' },
];

const userMenuItems = ref([
    {
        label: 'View Public Profile',
        icon: 'pi pi-external-link',
        command: () => window.location.href = `/providers/${provider?.slug || ''}`
    },
    {
        label: 'Account Settings',
        icon: 'pi pi-cog',
        command: () => window.location.href = '/console/settings'
    },
    { separator: true },
    {
        label: 'Switch to Client View',
        icon: 'pi pi-sync',
        command: () => window.location.href = '/dashboard'
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
    if (route === '/console') {
        return currentPath.value === '/console';
    }
    return currentPath.value.startsWith(route);
};

const getInitials = (name: string | undefined) => {
    if (!name) return 'U';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <Head :title="title" />
    <div class="console-layout" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
        <!-- Mobile Header -->
        <header class="mobile-header">
            <button class="mobile-menu-btn" @click="toggleMobileMenu">
                <i class="pi pi-bars"></i>
            </button>
            <Link href="/" class="mobile-logo">Zeen</Link>
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
                <Link href="/" class="logo-link">
                    <Avatar icon="pi pi-bolt" shape="square" size="large" />
                    <span v-if="!sidebarCollapsed" class="logo-text">Zeen</span>
                </Link>
            </div>

            <!-- Business Info (when expanded) -->
            <div v-if="!sidebarCollapsed" class="business-info">
                <Avatar
                    :label="getInitials(provider?.business_name || user?.name)"
                    shape="circle"
                    size="large"
                />
                <div class="business-details">
                    <p class="business-name">{{ provider?.business_name || 'Your Business' }}</p>
                    <span class="business-status">
                        <i class="pi pi-circle-fill status-dot"></i>
                        Active
                    </span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span v-if="!sidebarCollapsed" class="nav-section-title">Menu</span>
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
                        <Badge
                            v-if="item.badge"
                            :value="item.badge"
                            severity="danger"
                            class="nav-badge"
                        />
                    </span>
                    <span v-if="!sidebarCollapsed" class="nav-label">{{ item.label }}</span>
                </Link>
            </nav>

            <div class="sidebar-footer">
                <Button
                    v-if="!sidebarCollapsed"
                    label="Help & Support"
                    icon="pi pi-question-circle"
                    severity="secondary"
                    text
                    class="help-btn"
                />
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
                            <Link href="/console" class="breadcrumb-link">Console</Link>
                            <span class="breadcrumb-sep">/</span>
                            <span class="breadcrumb-current">{{ title || 'Dashboard' }}</span>
                        </nav>
                    </div>
                </div>

                <div class="header-right">
                    <Button
                        icon="pi pi-search"
                        severity="secondary"
                        text
                        rounded
                        v-tooltip.bottom="'Search'"
                    />

                    <div class="notification-wrapper">
                        <Button
                            icon="pi pi-bell"
                            severity="secondary"
                            text
                            rounded
                            v-tooltip.bottom="'Notifications'"
                        />
                        <Badge value="" severity="danger" class="notification-badge" />
                    </div>

                    <div class="header-divider"></div>

                    <div class="user-menu-trigger" @click="toggleUserMenu">
                        <Avatar
                            :label="getInitials(user?.name)"
                            shape="circle"
                        />
                        <div class="user-info">
                            <span class="user-name">{{ user?.name }}</span>
                            <span class="user-role">Provider</span>
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
.console-layout {
    display: flex;
    min-height: 100vh;
    background-color: var(--p-surface-100);
}

/* Sidebar - Light Theme */
.sidebar {
    width: 260px;
    background-color: var(--p-surface-0);
    border-right: 1px solid var(--p-surface-200);
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
    border-bottom: 1px solid var(--p-surface-200);
}

.logo-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--p-surface-900);
}

/* Business Info */
.business-info {
    padding: 1.25rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1px solid var(--p-surface-200);
}

.business-details {
    flex: 1;
    min-width: 0;
}

.business-name {
    font-weight: 600;
    font-size: 0.875rem;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--p-surface-900);
}

.business-status {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: var(--p-green-500);
}

.status-dot {
    font-size: 0.5rem;
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
    color: var(--p-surface-600);
    text-decoration: none;
    transition: all 0.2s ease;
    gap: 0.75rem;
    border-radius: 0.5rem;
}

.nav-item:hover {
    color: var(--p-surface-900);
    background-color: var(--p-surface-100);
}

.nav-item-active {
    color: var(--p-primary-color);
    background-color: var(--p-primary-50);
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

.nav-badge {
    position: absolute;
    top: -6px;
    right: -8px;
    min-width: 1rem;
    height: 1rem;
    font-size: 0.625rem;
}

.nav-label {
    font-size: 0.875rem;
    font-weight: 500;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid var(--p-surface-200);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.help-btn {
    justify-content: flex-start;
    color: var(--p-surface-600);
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
    color: var(--p-primary-color);
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

.notification-wrapper {
    position: relative;
}

.notification-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    min-width: 8px;
    height: 8px;
    padding: 0;
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
    color: var(--p-surface-500);
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
    background-color: var(--p-surface-0);
    border-bottom: 1px solid var(--p-surface-200);
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
    color: var(--p-surface-700);
    cursor: pointer;
    border-radius: 0.5rem;
}

.mobile-logo {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--p-primary-color);
    text-decoration: none;
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

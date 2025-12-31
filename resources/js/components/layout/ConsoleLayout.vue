<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import provider from '@/routes/provider';
import { logout } from '@/routes';
import InstallPrompt from '@/components/console/InstallPrompt.vue';
import FlashMessages from '@/components/error/FlashMessages.vue';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';
import { resolveUrl } from '@/utils/url';

defineProps<{
    title?: string;
}>();

const page = usePage();
const user = computed(() => (page.props as any).auth?.user);
const providerData = computed(() => (page.props as any).auth?.provider);
const subscription = computed(() => (page.props as any).auth?.subscription);
const isStarterPlan = computed(() => !subscription.value || subscription.value?.plan === 'starter');

const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);

// Register service worker for PWA
onMounted(() => {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/console-sw.js').catch((error) => {
            console.log('SW registration failed:', error);
        });
    }

    // Load collapsed state from localStorage
    const saved = localStorage.getItem('sidebar-collapsed');
    if (saved !== null) {
        sidebarCollapsed.value = saved === 'true';
    }
});

// Main Menu Items
const mainMenuItems = computed(() => [
    {
        label: 'Bookings',
        icon: 'pi pi-calendar',
        route: provider.dashboard.url()
    },
    {
        label: 'Services',
        icon: 'pi pi-th-large',
        route: provider.services.index.url()
    },

    {
        label: 'Events',
        icon: 'pi pi-calendar-plus',
        route: provider.events.index.url()
    }, {
        label: 'Categories',
        icon: 'pi pi-folder',
        route: provider.categories.index.url()
    },
    {
        label: 'Customers',
        icon: 'pi pi-users',
        route: '#',
        badge: 'Soon'
    },
    {
        label: 'Payments',
        icon: 'pi pi-wallet',
        route: provider.payments.index.url()
    },
    {
        label: 'Integrations',
        icon: 'pi pi-link',
        route: '#',
        badge: 'Soon'
    },
    {
        label: 'Settings',
        icon: 'pi pi-cog',
        route: provider.settings.edit.url()
    },
]);

// Profile menu
const profileMenu = ref();
const profileMenuItems = ref([
    {
        label: 'Profile Settings',
        icon: 'pi pi-user',
        command: () => router.get(resolveUrl(provider.profile.edit.url())),
    },
    {
        label: 'Account Settings',
        icon: 'pi pi-cog',
        command: () => router.get(resolveUrl(provider.settings.edit.url())),
    },
    { separator: true },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: () => router.post(resolveUrl(logout.url())),
    },
]);

const toggleProfileMenu = (event: Event) => {
    profileMenu.value.toggle(event);
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const toggleCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('sidebar-collapsed', String(sidebarCollapsed.value));
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};

const isActiveRoute = (route: string | undefined) => {
    if (!route || route === '#') return false;
    const currentPath = page.url;
    const routePath = route.replace(/^\/\/[^/]+/, '');
    const normalizedCurrent = currentPath.replace(/\/$/, '');
    const normalizedRoute = routePath.replace(/\/$/, '');

    if (normalizedRoute === '' || normalizedRoute === '/') {
        return normalizedCurrent === '' || normalizedCurrent === '/';
    }
    return normalizedCurrent.startsWith(normalizedRoute);
};

const handleLogout = () => {
    router.post(resolveUrl(logout.url()));
};

const copySiteUrl = async () => {
    if (providerData.value?.slug) {
        const url = `${window.location.origin}/${providerData.value.slug}`;
        await navigator.clipboard.writeText(url);
    }
};
</script>

<template>

    <Head :title="title" />
    <FlashMessages />

    <div class="console-layout" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
        <!-- Mobile Overlay -->
        <div v-if="sidebarOpen" class="sidebar-overlay" @click="closeSidebar"></div>

        <!-- Sidebar -->
        <aside class="sidebar" :class="{ open: sidebarOpen, collapsed: sidebarCollapsed }">
            <!-- Logo Header -->
            <div class="sidebar-header">
                <AppLink href="/" class="logo">
                    <span class="logo-icon">Z</span>
                    <span class="logo-text">Zeen</span>
                </AppLink>
            </div>

            <!-- Main Navigation -->
            <nav class="sidebar-nav">
                <!-- Main Menu Section -->
                <div class="nav-section">
                    <AppLink v-for="item in mainMenuItems" :key="item.label" :href="item.route" class="nav-item" :class="{
                        active: isActiveRoute(item.route),
                        disabled: item.badge === 'Soon'
                    }" :title="sidebarCollapsed ? item.label : undefined" @click="closeSidebar">
                        <i :class="item.icon" class="nav-icon"></i>
                        <span class="nav-label">{{ item.label }}</span>
                        <span v-if="item.badge" class="nav-badge">
                            {{ item.badge }}
                        </span>
                    </AppLink>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <!-- Copy Site URL Button -->
                <button class="footer-action-btn" @click="copySiteUrl"
                    :title="sidebarCollapsed ? 'Copy site URL' : undefined">
                    <i class="pi pi-external-link"></i>
                    <span class="footer-btn-label">Copy Site URL</span>
                </button>

                <!-- Upgrade Card (only for starter plan) -->
                <div v-if="isStarterPlan" class="upgrade-card">
                    <div class="upgrade-icon">
                        <i class="pi pi-bolt"></i>
                    </div>
                    <div class="upgrade-content">
                        <span class="upgrade-title">Upgrade to Premium</span>
                        <span class="upgrade-desc">Unlock all features and grow your business</span>
                    </div>
                    <AppLink :href="provider.subscription.index.url()" class="upgrade-btn">
                        Upgrade
                    </AppLink>
                </div>

                <!-- Logout Button -->
                <button class="footer-action-btn logout-btn" @click="handleLogout"
                    :title="sidebarCollapsed ? 'Log out' : undefined">
                    <i class="pi pi-sign-out"></i>
                    <span class="footer-btn-label">Log Out</span>
                </button>

                <!-- Collapse Toggle Button -->
                <button class="collapse-btn" @click="toggleCollapse"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                    <i class="pi" :class="sidebarCollapsed ? 'pi-angle-double-right' : 'pi-angle-double-left'"></i>
                    <span class="collapse-label">{{ sidebarCollapsed ? 'Expand' : 'Collapse' }}</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-wrapper">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" @click="toggleSidebar">
                        <i class="pi pi-bars"></i>
                    </button>
                    <h1 class="page-title">{{ title || 'Dashboard' }}</h1>
                </div>

                <div class="header-right">
                    <!-- Copy Site URL Button -->
                    <button class="header-action-btn" @click="copySiteUrl" title="Copy booking site URL">
                        <i class="pi pi-link"></i>
                    </button>

                    <!-- Profile -->
                    <div class="profile-section">
                        <button class="profile-btn" @click="toggleProfileMenu">
                            <Avatar :image="user?.avatar" :label="user?.name?.charAt(0).toUpperCase()" shape="circle"
                                class="profile-avatar" />
                            <div class="profile-info">
                                <span class="profile-name">{{ user?.name }}</span>
                                <span class="profile-email">{{ user?.email }}</span>
                            </div>
                        </button>
                        <Menu ref="profileMenu" :model="profileMenuItems" :popup="true" />
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content">
                <slot />
            </main>
        </div>

        <!-- PWA Install Prompt -->
        <InstallPrompt />
    </div>
</template>

<style scoped>
.console-layout {
    display: flex;
    min-height: 100vh;
}

/* Sidebar Overlay (Mobile) */
.sidebar-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(15, 23, 42, 0.4);
    z-index: 90;
    backdrop-filter: blur(2px);
}

/* Sidebar - White Background */
.sidebar {
    width: 260px;
    background-color: white;
    border-right: 1px solid var(--color-slate-200, #e2e8f0);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
    transform: translateX(-100%);
    transition: all 0.3s ease;
}

.sidebar.open {
    transform: translateX(0);
}

.sidebar.collapsed {
    width: 72px;
}

@media (min-width: 1024px) {
    .sidebar {
        transform: translateX(0);
    }

    .sidebar-overlay {
        display: none;
    }
}

/* Sidebar Header */
.sidebar-header {
    padding: 1.25rem 1rem;
    display: flex;
    justify-content: center;
}

.sidebar:not(.collapsed) .sidebar-header {
    justify-content: flex-start;
    padding-left: 1.5rem;
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.logo-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    background: linear-gradient(135deg, #1ABC9C 0%, #106B4F 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
}

.logo-text {
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--color-slate-900, #0f172a);
    white-space: nowrap;
    transition: opacity 0.2s ease, width 0.2s ease;
}

.sidebar.collapsed .logo-text {
    opacity: 0;
    width: 0;
    overflow: hidden;
}

/* Sidebar Navigation */
.sidebar-nav {
    flex: 1;
    padding: 0.5rem 0;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Nav Section */
.nav-section {
    padding: 0 0.5rem;
}

.sidebar:not(.collapsed) .nav-section {
    padding: 0 0.75rem;
}

/* Nav Item */
.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.75rem;
    color: var(--color-slate-600, #475569);
    text-decoration: none;
    font-size: 0.9375rem;
    font-weight: 500;
    border-radius: 0.625rem;
    border: none;
    background: none;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
    position: relative;
}

.sidebar.collapsed .nav-item {
    justify-content: center;
    padding: 0.75rem 0;
}

.nav-icon {
    width: 1.25rem;
    min-width: 1.25rem;
    text-align: center;
    font-size: 1.125rem;
    color: var(--color-slate-400, #94a3b8);
    transition: color 0.15s ease;
}

.nav-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    transition: opacity 0.2s ease, width 0.2s ease;
}

.sidebar.collapsed .nav-label {
    opacity: 0;
    width: 0;
    position: absolute;
}

.nav-badge {
    padding: 0.125rem 0.5rem;
    background-color: var(--color-slate-100, #f1f5f9);
    color: var(--color-slate-500, #64748b);
    font-size: 0.625rem;
    font-weight: 600;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    white-space: nowrap;
    transition: opacity 0.2s ease;
}

.sidebar.collapsed .nav-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    padding: 2px 4px;
    font-size: 0.5rem;
}

.nav-item:hover:not(.disabled) {
    background-color: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-900, #0f172a);
}

.nav-item:hover:not(.disabled) .nav-icon {
    color: var(--color-slate-600, #475569);
}

.nav-item.active {
    background-color: rgba(16, 107, 79, 0.08);
    color: #106B4F;
}

.nav-item.active .nav-icon {
    color: #106B4F;
}

.nav-item.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 0.75rem;
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    border-top: 1px solid var(--color-slate-100, #f1f5f9);
}

/* Footer Action Buttons */
.footer-action-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.625rem 0.75rem;
    background: none;
    border: none;
    border-radius: 0.5rem;
    color: var(--color-slate-600, #475569);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
}

.sidebar.collapsed .footer-action-btn {
    justify-content: center;
    padding: 0.625rem;
}

.footer-action-btn:hover {
    background-color: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-900, #0f172a);
}

.footer-action-btn i {
    font-size: 1rem;
    min-width: 1.25rem;
    text-align: center;
    color: var(--color-slate-400, #94a3b8);
    transition: color 0.15s ease;
}

.footer-action-btn:hover i {
    color: var(--color-slate-600, #475569);
}

.footer-btn-label {
    white-space: nowrap;
    transition: opacity 0.2s ease;
}

.sidebar.collapsed .footer-btn-label {
    display: none;
}

.logout-btn:hover {
    color: #DC2626;
}

.logout-btn:hover i {
    color: #DC2626;
}

/* Upgrade Card */
.upgrade-card {
    background: linear-gradient(135deg, rgba(16, 107, 79, 0.08) 0%, rgba(26, 188, 156, 0.08) 100%);
    border: 1px solid rgba(16, 107, 79, 0.15);
    border-radius: 0.75rem;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    transition: all 0.2s ease;
}

.sidebar.collapsed .upgrade-card {
    padding: 0.5rem;
    align-items: center;
}

.upgrade-icon {
    width: 2.5rem;
    height: 2.5rem;
    min-width: 2.5rem;
    background: linear-gradient(135deg, #1ABC9C 0%, #106B4F 100%);
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.sidebar.collapsed .upgrade-icon {
    width: 2rem;
    height: 2rem;
    min-width: 2rem;
}

.upgrade-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    transition: opacity 0.2s ease;
}

.sidebar.collapsed .upgrade-content {
    display: none;
}

.upgrade-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.upgrade-desc {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.4;
}

.upgrade-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1rem;
    background-color: #106B4F;
    color: white;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: background-color 0.15s ease;
}

.sidebar.collapsed .upgrade-btn {
    display: none;
}

.upgrade-btn:hover {
    background-color: #0d5a42;
}

/* Collapse Button */
.collapse-btn {
    display: none;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.625rem;
    background: none;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    color: var(--color-slate-500, #64748b);
    font-size: 0.8125rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

@media (min-width: 1024px) {
    .collapse-btn {
        display: flex;
    }
}

.collapse-btn:hover {
    background-color: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-700, #334155);
    border-color: var(--color-slate-300, #cbd5e1);
}

.collapse-label {
    transition: opacity 0.2s ease;
}

.sidebar.collapsed .collapse-label {
    display: none;
}

.sidebar.collapsed .collapse-btn {
    padding: 0.625rem;
}

/* Main wrapper */
.main-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: var(--color-slate-50, #f8fafc);
    min-width: 0;
    transition: margin-left 0.3s ease;
}

@media (min-width: 1024px) {
    .main-wrapper {
        margin-left: 260px;
    }

    .sidebar-collapsed .main-wrapper {
        margin-left: 72px;
    }
}

/* Top header */
.top-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.875rem 1rem;
    background-color: white;
    border-bottom: 1px solid var(--color-slate-200, #e2e8f0);
    position: sticky;
    top: 0;
    z-index: 50;
}

@media (min-width: 1024px) {
    .top-header {
        padding: 1rem 1.5rem;
    }
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.mobile-menu-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: none;
    border: none;
    color: var(--color-slate-500, #64748b);
    cursor: pointer;
    border-radius: 0.5rem;
    transition: all 0.15s;
}

.mobile-menu-btn:hover {
    background-color: var(--color-slate-100, #f1f5f9);
    color: var(--color-slate-700, #334155);
}

@media (min-width: 1024px) {
    .mobile-menu-btn {
        display: none;
    }
}

.page-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

@media (min-width: 1024px) {
    .page-title {
        font-size: 1.375rem;
    }
}

.header-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: var(--color-slate-100, #f1f5f9);
    border: none;
    border-radius: 0.5rem;
    color: var(--color-slate-500, #64748b);
    cursor: pointer;
    transition: all 0.15s ease;
}

.header-action-btn:hover {
    background-color: var(--color-slate-200, #e2e8f0);
    color: var(--color-slate-700, #334155);
}

.profile-section {
    position: relative;
}

.profile-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.375rem;
    background: none;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s;
}

.profile-btn:hover {
    background-color: var(--color-slate-50, #f8fafc);
}

.profile-avatar {
    width: 40px !important;
    height: 40px !important;
    background-color: #106B4F !important;
    color: white !important;
    font-size: 1rem !important;
}

.profile-info {
    display: none;
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
    text-align: left;
}

@media (min-width: 768px) {
    .profile-info {
        display: flex;
    }
}

.profile-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
    line-height: 1.2;
}

.profile-email {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.2;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Main content */
.main-content {
    flex: 1;
    padding: 1rem;
}

@media (min-width: 1024px) {
    .main-content {
        padding: 1.5rem;
    }
}
</style>

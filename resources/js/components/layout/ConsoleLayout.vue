<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import provider from '@/routes/provider';
import { logout } from '@/routes';
import InstallPrompt from '@/components/console/InstallPrompt.vue';
import FlashMessages from '@/components/error/FlashMessages.vue';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';

defineProps<{
    title?: string;
}>();

const page = usePage();
const user = computed(() => (page.props as any).auth?.user);
const providerData = computed(() => (page.props as any).auth?.provider);

const sidebarOpen = ref(false);

// Register service worker for PWA
onMounted(() => {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/console-sw.js').catch((error) => {
            console.log('SW registration failed:', error);
        });
    }
});

const navItems = [
    { label: 'Dashboard', icon: 'pi pi-home', route: provider.dashboard.url() },
    { label: 'Profile', icon: 'pi pi-user', route: provider.profile.edit.url() },
    { label: 'Services', icon: 'pi pi-th-large', route: provider.services.index.url() },
    { label: 'Availability', icon: 'pi pi-clock', route: provider.availability.edit.url() },
    { label: 'Bookings', icon: 'pi pi-calendar', route: provider.bookings.index.url() },
    { label: 'Payments', icon: 'pi pi-wallet', route: provider.payments.index.url() },
    { label: 'Reviews', icon: 'pi pi-star', route: provider.reviews.index.url() },
    { label: 'Team', icon: 'pi pi-users', route: provider.team.index.url() },
    { label: 'Settings', icon: 'pi pi-cog', route: provider.settings.edit.url() },
];

// Profile menu
const profileMenu = ref();
const profileMenuItems = ref([
    {
        label: 'Profile Settings',
        icon: 'pi pi-user',
        command: () => router.get(provider.profile.edit.url()),
    },
    {
        label: 'Account Settings',
        icon: 'pi pi-cog',
        command: () => router.get(provider.settings.edit.url()),
    },
    { separator: true },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: () => router.post(logout.url()),
    },
]);

const toggleProfileMenu = (event: Event) => {
    profileMenu.value.toggle(event);
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};

const isActiveRoute = (route: string) => {
    const currentPath = page.url;
    // Extract path without domain for comparison
    const routePath = route.replace(/^\/\/[^/]+/, '');
    const normalizedCurrent = currentPath.replace(/\/$/, '');
    const normalizedRoute = routePath.replace(/\/$/, '');

    if (normalizedRoute === '' || normalizedRoute === '/') {
        return normalizedCurrent === '' || normalizedCurrent === '/';
    }
    return normalizedCurrent.startsWith(normalizedRoute);
};
</script>

<template>
    <Head :title="title" />
    <FlashMessages />

    <div class="console-layout">
        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            class="sidebar-overlay"
            @click="closeSidebar"
        ></div>

        <!-- Sidebar -->
        <aside class="sidebar" :class="{ open: sidebarOpen }">
            <div class="sidebar-header">
                <AppLink href="/" class="logo">
                    <span class="logo-icon">Z</span>
                    <span class="logo-text">Zeen</span>
                </AppLink>
            </div>

            <nav class="sidebar-nav">
                <AppLink
                    v-for="item in navItems"
                    :key="item.route"
                    :href="item.route"
                    class="nav-item"
                    :class="{ active: isActiveRoute(item.route) }"
                    @click="closeSidebar"
                >
                    <i :class="item.icon"></i>
                    <span>{{ item.label }}</span>
                </AppLink>
            </nav>

            <div class="sidebar-footer">
                <div class="provider-badge">
                    <i class="pi pi-briefcase"></i>
                    <span>Provider Console</span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-wrapper">
            <header class="top-header">
                <div class="header-left">
                    <!-- Mobile menu button -->
                    <button class="mobile-menu-btn" @click="toggleSidebar">
                        <i class="pi pi-bars"></i>
                    </button>

                    <!-- Page title -->
                    <h1 class="page-title">{{ title || 'Dashboard' }}</h1>
                </div>

                <div class="header-right">
                    <div class="profile-section">
                        <button class="profile-btn" @click="toggleProfileMenu">
                            <Avatar
                                :image="user?.avatar"
                                :label="user?.name?.charAt(0).toUpperCase()"
                                shape="circle"
                                class="profile-avatar"
                            />
                            <div class="profile-info">
                                <span class="profile-name">{{ user?.name }}</span>
                                <span class="profile-business">{{ providerData?.business_name }}</span>
                            </div>
                            <i class="pi pi-chevron-down profile-chevron"></i>
                        </button>
                        <Menu ref="profileMenu" :model="profileMenuItems" :popup="true" />
                    </div>
                </div>
            </header>

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
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 90;
}

/* Sidebar */
.sidebar {
    width: 260px;
    background-color: #0D1F1B;
    color: white;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
}

.sidebar.open {
    transform: translateX(0);
}

@media (min-width: 1024px) {
    .sidebar {
        transform: translateX(0);
    }

    .sidebar-overlay {
        display: none;
    }
}

.sidebar-header {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.logo-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #106B4F 0%, #1ABC9C 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
}

.logo-text {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    color: rgba(255, 255, 255, 0.65);
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.15s ease;
}

.nav-item i {
    width: 1.25rem;
    text-align: center;
}

.nav-item:hover {
    background-color: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.9);
}

.nav-item.active {
    background-color: rgba(16, 107, 79, 0.3);
    color: #1ABC9C;
    border-right: 3px solid #1ABC9C;
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.provider-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background-color: rgba(16, 107, 79, 0.2);
    border-radius: 6px;
    font-size: 0.75rem;
    color: #1ABC9C;
}

/* Main wrapper */
.main-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: #F8FAFC;
    min-width: 0;
}

@media (min-width: 1024px) {
    .main-wrapper {
        margin-left: 260px;
    }
}

/* Top header */
.top-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background-color: white;
    border-bottom: 1px solid #E2E8F0;
    position: sticky;
    top: 0;
    z-index: 50;
}

@media (min-width: 1024px) {
    .top-header {
        padding: 0.75rem 1.5rem;
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
    color: #64748B;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.15s;
}

.mobile-menu-btn:hover {
    background-color: #F1F5F9;
    color: #0D1F1B;
}

@media (min-width: 1024px) {
    .mobile-menu-btn {
        display: none;
    }
}

.page-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #0D1F1B;
}

@media (min-width: 1024px) {
    .page-title {
        font-size: 1.25rem;
    }
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.profile-section {
    position: relative;
}

.profile-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.375rem 0.75rem;
    background: none;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
}

.profile-btn:hover {
    background-color: #F8FAFC;
    border-color: #CBD5E1;
}

.profile-avatar {
    width: 32px !important;
    height: 32px !important;
    background-color: #106B4F !important;
    color: white !important;
    font-size: 0.875rem !important;
}

.profile-info {
    display: none;
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
}

@media (min-width: 640px) {
    .profile-info {
        display: flex;
    }
}

.profile-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0F172A;
    line-height: 1.2;
}

.profile-business {
    font-size: 0.75rem;
    color: #64748B;
    line-height: 1.2;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-chevron {
    font-size: 0.75rem;
    color: #94A3B8;
}

@media (max-width: 639px) {
    .profile-chevron {
        display: none;
    }

    .profile-btn {
        padding: 0.375rem;
        border: none;
    }
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

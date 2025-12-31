<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import FlashMessages from '@/components/error/FlashMessages.vue';
import NotificationBell from '@/components/admin/NotificationBell.vue';
import InputText from 'primevue/inputtext';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';
import admin from "@/routes/admin";
import { useRoute } from '@/composables/useRoute';

interface AdminPageProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            avatar?: string;
            role: string;
        } | null;
    };
    criticalAlerts?: Array<{
        id: string;
        type: string;
        title: string;
        message: string;
        timestamp: string;
        read: boolean;
        link?: string;
    }>;
    [key: string]: unknown;
}

defineProps<{
    title?: string;
}>();


const { resolve } = useRoute();

const page = usePage<AdminPageProps>();
const user = computed(() => page.props.auth.user);
const criticalAlerts = computed(() => (page.props as any).criticalAlerts || []);

// Sidebar state
const expandedMenus = ref<string[]>(['providers']);

// Search
const searchQuery = ref('');
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get('/admin/search', { q: searchQuery.value });
    }
};

// Profile menu
const profileMenu = ref();
const profileMenuItems = ref([
    {
        label: 'Profile',
        icon: 'pi pi-user',
        command: () => router.get('/admin/profile'),
    },
    {
        label: 'Settings',
        icon: 'pi pi-cog',
        command: () => router.get(resolve(admin.settings.index.url())),
    },
    { separator: true },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: () => router.post(resolve(admin.logout.url())),
    },
]);

const toggleProfileMenu = (event: Event) => {
    profileMenu.value.toggle(event);
};

// Navigation structure
interface NavItem {
    label: string;
    icon: string;
    route?: string;
    key?: string;
    children?: NavItem[];
}

const navItems: NavItem[] = [
    { label: 'Dashboard', icon: 'pi pi-home', route: admin.dashboard.url() },
    {
        label: 'Providers',
        icon: 'pi pi-briefcase',
        key: 'providers',
        children: [
            { label: 'Onboarding', icon: 'pi pi-user-plus', route: '/admin/providers/onboarding' },
            { label: 'Management', icon: 'pi pi-list', route: '/admin/providers' },
        ],
    },
    { label: 'Industries', icon: 'pi pi-building', route: admin.industries.index.url() },
    { label: 'Bookings', icon: 'pi pi-calendar', route: admin.bookings.index.url() },
    {
        label: 'Subscriptions',
        icon: 'pi pi-credit-card',
        key: 'subscriptions',
        children: [
            { label: 'Exemptions', icon: 'pi pi-pause-circle', route: '/admin/subscriptions/exemptions' },
            { label: 'Discounts', icon: 'pi pi-percentage', route: '/admin/subscriptions/discounts' },
        ],
    },
    { label: 'Payouts', icon: 'pi pi-money-bill', route: admin.payouts.index.url() },
    { label: 'Marketing CMS', icon: 'pi pi-megaphone', route: '/admin/marketing' },
    { label: 'Waitlist', icon: 'pi pi-list-check', route: admin.waitlist.index.url() },
    { label: 'Settings', icon: 'pi pi-cog', route: admin.settings.index.url() },
];

const toggleMenu = (key: string) => {
    const idx = expandedMenus.value.indexOf(key);
    if (idx > -1) {
        expandedMenus.value.splice(idx, 1);
    } else {
        expandedMenus.value.push(key);
    }
};

const isMenuExpanded = (key: string) => expandedMenus.value.includes(key);

const isActiveRoute = (route: string) => {
    const currentPath = page.url;
    if (route === '/admin') {
        return currentPath === '/admin' || currentPath === '/admin/';
    }
    return currentPath.startsWith(route);
};
</script>

<template>

    <Head :title="title ? `${title} - Admin` : 'Admin'" />
    <FlashMessages />

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <AppLink href="/admin" class="logo">
                    <span class="logo-icon">Z</span>
                    <span class="logo-text">Zeen Admin</span>
                </AppLink>
            </div>

            <nav class="sidebar-nav">
                <template v-for="item in navItems" :key="item.route || item.key">
                    <!-- Items with children -->
                    <template v-if="item.children">
                        <button class="nav-item nav-parent" :class="{ expanded: isMenuExpanded(item.key!) }"
                            @click="toggleMenu(item.key!)">
                            <i :class="item.icon"></i>
                            <span>{{ item.label }}</span>
                            <i class="pi expand-icon"
                                :class="isMenuExpanded(item.key!) ? 'pi-chevron-down' : 'pi-chevron-right'"></i>
                        </button>
                        <div v-show="isMenuExpanded(item.key!)" class="nav-children">
                            <AppLink v-for="child in item.children" :key="child.route" :href="child.route!"
                                class="nav-item nav-child" :class="{ active: isActiveRoute(child.route!) }">
                                <i :class="child.icon"></i>
                                <span>{{ child.label }}</span>
                            </AppLink>
                        </div>
                    </template>

                    <!-- Simple items -->
                    <AppLink v-else :href="item.route!" class="nav-item"
                        :class="{ active: isActiveRoute(item.route!) }">
                        <i :class="item.icon"></i>
                        <span>{{ item.label }}</span>
                    </AppLink>
                </template>
            </nav>

            <div class="sidebar-footer">
                <div class="admin-badge">
                    <i class="pi pi-shield"></i>
                    <span>Administrator</span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-wrapper">
            <header class="top-header">
                <div class="header-left">
                    <div class="search-wrapper">
                        <i class="pi pi-search search-icon"></i>
                        <InputText v-model="searchQuery" placeholder="Search providers, bookings..."
                            class="search-input" @keyup.enter="handleSearch" />
                    </div>
                </div>

                <div class="header-right">
                    <NotificationBell :alerts="criticalAlerts" />

                    <div class="profile-section">
                        <button class="profile-btn" @click="toggleProfileMenu">
                            <Avatar :image="user?.avatar" :label="user?.name?.charAt(0).toUpperCase()" shape="circle"
                                class="profile-avatar" />
                            <div class="profile-info">
                                <span class="profile-name">{{ user?.name }}</span>
                                <span class="profile-role">Admin</span>
                            </div>
                            <i class="pi pi-chevron-down profile-chevron"></i>
                        </button>
                        <Menu ref="profileMenu" :model="profileMenuItems" :popup="true" />
                    </div>
                </div>
            </header>

            <div class="page-header" v-if="title">
                <h1>{{ title }}</h1>
            </div>

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
    font-size: 1.125rem;
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
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.nav-item i:first-child {
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

.nav-parent {
    justify-content: flex-start;
}

.nav-parent .expand-icon {
    margin-left: auto;
    font-size: 0.75rem;
    opacity: 0.6;
}

.nav-children {
    background-color: rgba(0, 0, 0, 0.15);
}

.nav-child {
    padding-left: 3rem;
}

.nav-child i:first-child {
    font-size: 0.75rem;
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.admin-badge {
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
    margin-left: 260px;
}

/* Top header */
.top-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.5rem;
    background-color: white;
    border-bottom: 1px solid #E2E8F0;
    position: sticky;
    top: 0;
    z-index: 50;
}

.header-left {
    flex: 1;
    max-width: 480px;
}

.search-wrapper {
    position: relative;
    width: 100%;
}

.search-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    font-size: 0.875rem;
}

.search-input {
    width: 100%;
    padding-left: 2.5rem !important;
    background-color: #F1F5F9;
    border: 1px solid transparent;
    border-radius: 8px;
    font-size: 0.875rem;
}

.search-input:focus {
    background-color: white;
    border-color: #106B4F;
    box-shadow: 0 0 0 3px rgba(16, 107, 79, 0.1);
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
    width: 32px;
    height: 32px;
    background-color: #106B4F;
    color: white;
}

.profile-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
}

.profile-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0F172A;
    line-height: 1.2;
}

.profile-role {
    font-size: 0.75rem;
    color: #64748B;
    line-height: 1.2;
}

.profile-chevron {
    font-size: 0.75rem;
    color: #94A3B8;
}

/* Page header */
.page-header {
    padding: 1.5rem 1.5rem 0;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #0F172A;
}

/* Main content */
.main-content {
    flex: 1;
    padding: 1.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .sidebar {
        width: 80px;
    }

    .logo-text,
    .nav-item span,
    .admin-badge span,
    .nav-parent .expand-icon {
        display: none;
    }

    .nav-children {
        display: none;
    }

    .nav-item {
        justify-content: center;
        padding: 0.75rem;
    }

    .nav-item i:first-child {
        width: auto;
    }

    .admin-badge {
        justify-content: center;
    }

    .main-wrapper {
        margin-left: 80px;
    }

    .profile-info {
        display: none;
    }

    .profile-btn {
        padding: 0.375rem;
    }
}
</style>

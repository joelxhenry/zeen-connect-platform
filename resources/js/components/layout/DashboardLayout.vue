<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types/models';
import { ref, computed } from 'vue';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const user = page.props.auth?.user;
const userMenu = ref();

const currentPath = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.pathname;
    }
    return '/dashboard';
});

const navItems = [
    { label: 'Dashboard', icon: 'pi pi-home', route: '/dashboard' },
    { label: 'My Bookings', icon: 'pi pi-calendar', route: '/dashboard/bookings' },
    { label: 'Favorites', icon: 'pi pi-heart', route: '/dashboard/favorites' },
];

const userMenuItems = ref([
    {
        label: 'Profile',
        icon: 'pi pi-user',
        command: () => window.location.href = '/dashboard/profile'
    },
    {
        label: 'Settings',
        icon: 'pi pi-cog',
        command: () => window.location.href = '/dashboard/settings'
    },
    { separator: true },
    {
        label: 'Logout',
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
    return currentPath.value === route;
};
</script>

<template>
    <Head :title="title" />
    <div class="dashboard-layout">
        <!-- Navigation -->
        <header class="dashboard-header">
            <div class="header-container">
                <div class="header-left">
                    <Link href="/" class="logo">Zeen</Link>

                    <nav class="main-nav">
                        <Link
                            v-for="item in navItems"
                            :key="item.route"
                            :href="item.route"
                            :class="['nav-link', { 'nav-link-active': isActive(item.route) }]"
                        >
                            <i :class="[item.icon, 'mr-2']"></i>
                            {{ item.label }}
                        </Link>
                    </nav>
                </div>

                <div class="header-right">
                    <Link href="/explore" class="explore-link">
                        <i class="pi pi-search mr-2"></i>
                        Find Services
                    </Link>

                    <div class="user-menu-trigger" @click="toggleUserMenu">
                        <Avatar
                            :label="user?.name?.charAt(0)"
                            shape="circle"
                            class="user-avatar"
                        />
                        <span class="user-name">{{ user?.name }}</span>
                        <i class="pi pi-chevron-down ml-2"></i>
                    </div>
                    <Menu ref="userMenu" :model="userMenuItems" :popup="true" />
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="dashboard-content">
            <div class="content-container">
                <slot />
            </div>
        </main>
    </div>
</template>

<style scoped>
.dashboard-layout {
    min-height: 100vh;
    background-color: var(--color-surface-alt);
}

.dashboard-header {
    background-color: var(--color-surface);
    border-bottom: 1px solid var(--p-surface-200);
}

.header-container {
    max-width: 80rem;
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
    font-weight: bold;
    color: var(--color-primary);
    text-decoration: none;
}

.main-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    color: var(--color-text-secondary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.2s;
}

.nav-link:hover {
    color: var(--color-text-primary);
    background-color: var(--p-surface-100);
}

.nav-link-active {
    color: var(--color-primary);
    background-color: var(--color-primary-bg);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.explore-link {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    color: var(--color-primary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.2s;
}

.explore-link:hover {
    background-color: var(--color-primary-bg);
}

.user-menu-trigger {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: background-color 0.2s;
}

.user-menu-trigger:hover {
    background-color: var(--p-surface-100);
}

.user-avatar {
    background-color: var(--color-primary);
    color: white;
}

.user-name {
    font-size: 0.875rem;
    color: var(--color-text-primary);
    font-weight: 500;
}

.dashboard-content {
    padding: 2.5rem 0;
}

.content-container {
    max-width: 80rem;
    margin: 0 auto;
    padding: 0 1.5rem;
}
</style>

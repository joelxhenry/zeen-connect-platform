<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { User } from '@/types/models';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Menu from 'primevue/menu';

defineProps<{
    title?: string;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as { user?: User } | undefined)?.user);

const currentPath = computed(() => {
    if (typeof window !== 'undefined') {
        return new URL(window.location.href).pathname;
    }
    return '/dashboard';
});

const isActive = (path: string) => {
    if (path === '/dashboard') {
        return currentPath.value === '/dashboard';
    }
    return currentPath.value.startsWith(path);
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const navItems = [
    { label: 'Home', route: '/dashboard', icon: 'pi pi-home' },
    { label: 'Bookings', route: '/dashboard/bookings', icon: 'pi pi-calendar' },
    { label: 'Favorites', route: '/dashboard/favorites', icon: 'pi pi-heart' },
];

const userMenu = ref();
const userMenuItems = ref([
    { label: 'My Profile', icon: 'pi pi-user', command: () => window.location.href = '/dashboard/profile' },
    { label: 'Settings', icon: 'pi pi-cog', command: () => window.location.href = '/dashboard/settings' },
    { separator: true },
    { label: 'Sign Out', icon: 'pi pi-sign-out', command: () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/logout';
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }},
]);

const toggleUserMenu = (event: Event) => {
    userMenu.value.toggle(event);
};

const mobileMenuOpen = ref(false);
</script>

<template>
    <Head :title="title ? `${title} | Zeen` : 'Zeen'" />

    <div class="client-layout">
        <!-- Header -->
        <header class="client-header">
            <div class="header-container">
                <!-- Logo -->
                <AppLink href="/" class="logo">
                    <span class="logo-text">Zeen</span>
                </AppLink>

                <!-- Desktop Navigation -->
                <nav class="desktop-nav">
                    <AppLink
                        v-for="item in navItems"
                        :key="item.route"
                        :href="item.route"
                        class="nav-item"
                        :class="{ 'nav-item--active': isActive(item.route) }"
                    >
                        {{ item.label }}
                    </AppLink>
                </nav>

                <!-- Right Side -->
                <div class="header-actions">
                    <AppLink href="/explore" class="explore-btn">
                        <Button label="Find Services" icon="pi pi-search" text class="!text-[#106B4F] !font-medium" />
                    </AppLink>

                    <!-- User Menu -->
                    <button
                        v-if="user"
                        @click="toggleUserMenu"
                        class="user-btn"
                        aria-haspopup="true"
                    >
                        <Avatar
                            v-if="user.avatar"
                            :image="user.avatar"
                            shape="circle"
                            class="!w-9 !h-9"
                        />
                        <Avatar
                            v-else
                            :label="getInitials(user.name || '')"
                            shape="circle"
                            class="!w-9 !h-9 !bg-[#106B4F] !text-white !text-sm"
                        />
                    </button>
                    <Menu ref="userMenu" :model="userMenuItems" :popup="true" class="user-dropdown" />

                    <!-- Mobile Menu Toggle -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="mobile-menu-btn"
                    >
                        <i :class="mobileMenuOpen ? 'pi pi-times' : 'pi pi-bars'" class="text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <nav v-if="mobileMenuOpen" class="mobile-nav">
                <AppLink
                    v-for="item in navItems"
                    :key="item.route"
                    :href="item.route"
                    class="mobile-nav-item"
                    :class="{ 'mobile-nav-item--active': isActive(item.route) }"
                    @click="mobileMenuOpen = false"
                >
                    <i :class="item.icon"></i>
                    {{ item.label }}
                </AppLink>
                <AppLink href="/explore" class="mobile-nav-item" @click="mobileMenuOpen = false">
                    <i class="pi pi-search"></i>
                    Find Services
                </AppLink>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="client-main">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.client-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background-color: #fafafa;
}

.client-header {
    background-color: white;
    border-bottom: 1px solid #f0f0f0;
    position: sticky;
    top: 0;
    z-index: 40;
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    text-decoration: none;
    display: flex;
    align-items: center;
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 700;
    color: #106B4F;
    letter-spacing: -0.02em;
}

.desktop-nav {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.nav-item {
    padding: 0.625rem 1rem;
    color: #64748b;
    text-decoration: none;
    font-size: 0.9375rem;
    font-weight: 500;
    border-radius: 0.5rem;
    transition: all 0.15s ease;
}

.nav-item:hover {
    color: #0D1F1B;
    background-color: #f8fafc;
}

.nav-item--active {
    color: #106B4F;
    background-color: rgba(16, 107, 79, 0.08);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.explore-btn {
    text-decoration: none;
}

.user-btn {
    background: none;
    border: none;
    padding: 0.25rem;
    cursor: pointer;
    border-radius: 50%;
    transition: all 0.15s ease;
}

.user-btn:hover {
    background-color: #f1f5f9;
}

.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    padding: 0.5rem;
    color: #64748b;
    cursor: pointer;
    border-radius: 0.5rem;
}

.mobile-menu-btn:hover {
    background-color: #f1f5f9;
    color: #0D1F1B;
}

.mobile-nav {
    display: none;
    padding: 0.5rem 1rem 1rem;
    border-top: 1px solid #f0f0f0;
    background-color: white;
}

.mobile-nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    color: #64748b;
    text-decoration: none;
    font-size: 0.9375rem;
    font-weight: 500;
    border-radius: 0.5rem;
    transition: all 0.15s ease;
}

.mobile-nav-item:hover {
    color: #0D1F1B;
    background-color: #f8fafc;
}

.mobile-nav-item--active {
    color: #106B4F;
    background-color: rgba(16, 107, 79, 0.08);
}

.client-main {
    flex: 1;
}

/* User dropdown styling */
:deep(.user-dropdown) {
    min-width: 200px;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .header-container {
        padding: 0 1rem;
    }

    .desktop-nav {
        display: none;
    }

    .explore-btn {
        display: none;
    }

    .mobile-menu-btn {
        display: flex;
    }

    .mobile-nav {
        display: block;
    }
}
</style>

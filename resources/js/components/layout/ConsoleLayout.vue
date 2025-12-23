<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
}>();

const sidebarOpen = ref(false);

const navItems = [
    { label: 'Dashboard', icon: 'pi pi-home', route: '/console' },
    { label: 'Profile', icon: 'pi pi-user', route: '/console/profile' },
    { label: 'Services', icon: 'pi pi-th-large', route: '/console/services' },
    { label: 'Availability', icon: 'pi pi-clock', route: '/console/availability' },
    { label: 'Bookings', icon: 'pi pi-calendar', route: '/console/bookings' },
    { label: 'Payments', icon: 'pi pi-wallet', route: '/console/payments' },
    { label: 'Reviews', icon: 'pi pi-star', route: '/console/reviews' },
];

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen">
        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 bg-black/50 z-40 lg:hidden"
            @click="closeSidebar"
        ></div>

        <!-- Sidebar -->
        <aside
            class="fixed lg:static inset-y-0 left-0 z-50 w-64 lg:w-60 bg-white border-r border-gray-200 flex flex-col transform transition-transform duration-300 ease-in-out lg:transform-none"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Link href="/" class="text-xl font-bold text-[#106B4F] no-underline">Zeen</Link>
                    <span class="text-xs px-2 py-0.5 bg-[#106B4F]/10 text-[#106B4F] rounded-full">Provider</span>
                </div>
                <!-- Close button for mobile -->
                <button
                    class="lg:hidden p-2 -mr-2 text-gray-500 hover:text-[#0D1F1B] transition-colors"
                    @click="closeSidebar"
                >
                    <i class="pi pi-times text-lg"></i>
                </button>
            </div>

            <nav class="flex-1 py-4 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="item.route"
                    class="flex items-center gap-3 py-3 px-4 text-gray-500 no-underline hover:bg-neutral-100 hover:text-[#0D1F1B] transition-colors"
                    @click="closeSidebar"
                >
                    <i :class="item.icon"></i>
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex items-center gap-3 w-full py-3 bg-transparent border-none text-gray-500 cursor-pointer text-left hover:text-[#0D1F1B] transition-colors"
                >
                    <i class="pi pi-sign-out"></i>
                    <span>Logout</span>
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col bg-neutral-100 min-w-0">
            <header class="py-3 px-4 lg:py-4 lg:px-6 bg-white border-b border-gray-200 flex justify-between items-center gap-4">
                <!-- Mobile menu button -->
                <button
                    class="lg:hidden p-2 -ml-2 text-gray-500 hover:text-[#0D1F1B] transition-colors"
                    @click="toggleSidebar"
                >
                    <i class="pi pi-bars text-xl"></i>
                </button>

                <!-- Mobile logo (shown on mobile only) -->
                <Link href="/" class="lg:hidden text-lg font-bold text-[#106B4F] no-underline">Zeen</Link>

                <!-- Title (hidden on mobile, shown on desktop) -->
                <h1 class="hidden lg:block m-0 text-xl font-semibold text-[#0D1F1B]">{{ title || 'Dashboard' }}</h1>

                <!-- Spacer for mobile to push switch link to right -->
                <div class="flex-1 lg:hidden"></div>

                <Link href="/dashboard" class="text-sm text-gray-500 no-underline hover:text-[#106B4F] transition-colors whitespace-nowrap">
                    <span class="hidden sm:inline">Switch to Client</span>
                    <span class="sm:hidden">Client</span>
                </Link>
            </header>

            <!-- Mobile page title -->
            <div class="lg:hidden px-4 py-3 bg-neutral-100">
                <h1 class="m-0 text-lg font-semibold text-[#0D1F1B]">{{ title || 'Dashboard' }}</h1>
            </div>

            <main class="p-4 lg:p-6 w-full">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import provider from '@/routes/provider';
import { logout } from '@/routes';
import InstallPrompt from '@/components/console/InstallPrompt.vue';
import FlashMessages from '@/components/error/FlashMessages.vue';

defineProps<{
    title?: string;
}>();

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
    { label: 'Settings', icon: 'pi pi-cog', route: provider.settings.edit.url() },
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
    <FlashMessages />

    <div class="flex min-h-screen">
        <!-- Mobile Overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 bg-black/50 z-40 lg:hidden" @click="closeSidebar"></div>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 lg:w-60 h-screen bg-white border-r border-gray-200 flex flex-col transform transition-transform duration-300 ease-in-out lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <AppLink href="/" class="text-xl font-bold text-[#106B4F] no-underline">Zeen</AppLink>
                    <span class="text-xs px-2 py-0.5 bg-[#106B4F]/10 text-[#106B4F] rounded-full">Provider</span>
                </div>
                <!-- Close button for mobile -->
                <button class="lg:hidden p-2 -mr-2 text-gray-500 hover:text-[#0D1F1B] transition-colors"
                    @click="closeSidebar">
                    <i class="pi pi-times text-lg"></i>
                </button>
            </div>

            <nav class="flex-1 py-4 overflow-y-auto">
                <AppLink v-for="item in navItems" :key="item.route" :href="item.route"
                    class="flex items-center gap-3 py-3 px-4 text-gray-500 no-underline hover:bg-neutral-100 hover:text-[#0D1F1B] transition-colors"
                    @click="closeSidebar">
                    <i :class="item.icon"></i>
                    <span>{{ item.label }}</span>
                </AppLink>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <AppLink :href="logout.url()" method="post" as="button"
                    class="flex items-center gap-3 w-full py-3 bg-transparent border-none text-gray-500 cursor-pointer text-left hover:text-[#0D1F1B] transition-colors">
                    <i class="pi pi-sign-out"></i>
                    <span>Logout</span>
                </AppLink>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col bg-neutral-100 min-w-0 lg:ml-60">
            <header
                class="py-3 px-4 lg:py-4 lg:px-6 bg-white border-b border-gray-200 flex justify-between items-center gap-4">
                <!-- Mobile menu button -->
                <button class="lg:hidden p-2 -ml-2 text-gray-500 hover:text-[#0D1F1B] transition-colors"
                    @click="toggleSidebar">
                    <i class="pi pi-bars text-xl"></i>
                </button>

                <!-- Mobile logo (shown on mobile only) -->
                <AppLink href="/" class="lg:hidden text-lg font-bold text-[#106B4F] no-underline">Zeen</AppLink>

                <!-- Title (hidden on mobile, shown on desktop) -->
                <h1 class="hidden lg:block m-0 text-xl font-semibold text-[#0D1F1B]">{{ title || 'Dashboard' }}</h1>

                <!-- Spacer for mobile to push switch link to right -->
                <div class="flex-1 lg:hidden"></div>
            </header>

            <!-- Mobile page title -->
            <div class="lg:hidden px-4 py-3 bg-neutral-100">
                <h1 class="m-0 text-lg font-semibold text-[#0D1F1B]">{{ title || 'Dashboard' }}</h1>
            </div>

            <main class="p-4 lg:p-6 w-full">
                <slot />
            </main>
        </div>

        <!-- PWA Install Prompt -->
        <InstallPrompt />
    </div>
</template>

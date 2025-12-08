<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types/models';
import { ref } from 'vue';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const user = page.props.auth?.user;
const isCollapse = ref(false);
const activeMenu = ref('/console');
</script>

<template>
    <Head :title="title" />
    <el-container class="min-h-screen">
        <!-- Sidebar -->
        <el-aside :width="isCollapse ? '64px' : '220px'" class="!overflow-visible">
            <div class="h-full bg-gray-900">
                <!-- Logo -->
                <div class="h-16 flex items-center justify-center border-b border-gray-700">
                    <Link href="/" class="text-xl font-bold text-white">
                        {{ isCollapse ? 'Z' : 'Zeen' }}
                    </Link>
                </div>

                <!-- Menu -->
                <el-menu
                    :default-active="activeMenu"
                    :collapse="isCollapse"
                    background-color="#111827"
                    text-color="#d1d5db"
                    active-text-color="#818cf8"
                    class="!border-r-0"
                >
                    <el-menu-item index="/console">
                        <Link href="/console" class="flex items-center w-full">
                            <el-icon><HomeFilled /></el-icon>
                            <template #title>Dashboard</template>
                        </Link>
                    </el-menu-item>

                    <el-menu-item index="/console/profile">
                        <Link href="/console/profile" class="flex items-center w-full">
                            <el-icon><User /></el-icon>
                            <template #title>Profile</template>
                        </Link>
                    </el-menu-item>

                    <el-menu-item index="/console/services">
                        <Link href="/console/services" class="flex items-center w-full">
                            <el-icon><Grid /></el-icon>
                            <template #title>Services</template>
                        </Link>
                    </el-menu-item>

                    <el-menu-item index="/console/portfolios">
                        <Link href="/console/portfolios" class="flex items-center w-full">
                            <el-icon><Picture /></el-icon>
                            <template #title>Portfolio</template>
                        </Link>
                    </el-menu-item>

                    <el-menu-item index="/console/bookings">
                        <Link href="/console/bookings" class="flex items-center w-full">
                            <el-icon><Calendar /></el-icon>
                            <template #title>Bookings</template>
                        </Link>
                    </el-menu-item>

                    <el-menu-item index="/console/payments">
                        <Link href="/console/payments" class="flex items-center w-full">
                            <el-icon><Wallet /></el-icon>
                            <template #title>Payments</template>
                        </Link>
                    </el-menu-item>

                    <el-menu-item index="/console/availability">
                        <Link href="/console/availability" class="flex items-center w-full">
                            <el-icon><Clock /></el-icon>
                            <template #title>Availability</template>
                        </Link>
                    </el-menu-item>
                </el-menu>

                <!-- Collapse Toggle -->
                <div class="absolute bottom-4 left-0 right-0 px-4">
                    <el-button
                        :icon="isCollapse ? 'Expand' : 'Fold'"
                        text
                        class="w-full !text-gray-400 hover:!text-white"
                        @click="isCollapse = !isCollapse"
                    >
                        <el-icon v-if="isCollapse"><Expand /></el-icon>
                        <el-icon v-else><Fold /></el-icon>
                        <span v-if="!isCollapse" class="ml-2">Collapse</span>
                    </el-button>
                </div>
            </div>
        </el-aside>

        <!-- Main Content -->
        <el-container>
            <!-- Top Header -->
            <el-header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 !h-16">
                <div class="flex items-center justify-between h-full px-4">
                    <div class="flex items-center">
                        <el-breadcrumb separator="/">
                            <el-breadcrumb-item :to="{ path: '/console' }">Console</el-breadcrumb-item>
                            <el-breadcrumb-item>{{ title || 'Dashboard' }}</el-breadcrumb-item>
                        </el-breadcrumb>
                    </div>

                    <div class="flex items-center gap-4">
                        <el-badge :value="3" class="mr-2">
                            <el-button :icon="Bell" circle />
                        </el-badge>

                        <el-dropdown trigger="click">
                            <div class="flex items-center cursor-pointer">
                                <el-avatar :size="36" class="mr-2">
                                    {{ user?.name?.charAt(0) }}
                                </el-avatar>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ user?.name }}</span>
                                <el-icon class="ml-1"><ArrowDown /></el-icon>
                            </div>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item>
                                        <Link href="/console/profile" class="flex items-center">
                                            <el-icon class="mr-2"><User /></el-icon>
                                            Profile
                                        </Link>
                                    </el-dropdown-item>
                                    <el-dropdown-item>
                                        <Link href="/console/settings" class="flex items-center">
                                            <el-icon class="mr-2"><Setting /></el-icon>
                                            Settings
                                        </Link>
                                    </el-dropdown-item>
                                    <el-dropdown-item divided>
                                        <Link href="/logout" method="post" as="button" class="flex items-center w-full">
                                            <el-icon class="mr-2"><SwitchButton /></el-icon>
                                            Logout
                                        </Link>
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </div>
                </div>
            </el-header>

            <!-- Page Content -->
            <el-main class="bg-gray-100 dark:bg-gray-900">
                <slot />
            </el-main>
        </el-container>
    </el-container>
</template>

<script lang="ts">
import {
    HomeFilled, User, Grid, Picture, Calendar, Wallet, Clock,
    Expand, Fold, Bell, ArrowDown, Setting, SwitchButton
} from '@element-plus/icons-vue';

export default {
    components: {
        HomeFilled, User, Grid, Picture, Calendar, Wallet, Clock,
        Expand, Fold, Bell, ArrowDown, Setting, SwitchButton
    }
};
</script>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types/models';
import { ref } from 'vue';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const user = page.props.auth?.user;
const activeIndex = ref('/dashboard');
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Navigation -->
        <el-header class="!h-auto !p-0">
            <el-menu
                :default-active="activeIndex"
                mode="horizontal"
                background-color="#fff"
                text-color="#374151"
                active-text-color="#4f46e5"
                class="!border-b border-gray-200"
            >
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center w-full">
                    <Link href="/" class="flex items-center mr-8">
                        <span class="text-xl font-bold text-indigo-600">Zeen</span>
                    </Link>

                    <el-menu-item index="/dashboard">
                        <Link href="/dashboard" class="flex items-center">
                            <el-icon class="mr-2"><HomeFilled /></el-icon>
                            Dashboard
                        </Link>
                    </el-menu-item>

                    <el-menu-item index="/dashboard/bookings">
                        <Link href="/dashboard/bookings" class="flex items-center">
                            <el-icon class="mr-2"><Calendar /></el-icon>
                            My Bookings
                        </Link>
                    </el-menu-item>

                    <el-menu-item index="/dashboard/payments">
                        <Link href="/dashboard/payments" class="flex items-center">
                            <el-icon class="mr-2"><CreditCard /></el-icon>
                            Payments
                        </Link>
                    </el-menu-item>

                    <div class="flex-1"></div>

                    <el-menu-item index="/explore">
                        <Link href="/explore" class="flex items-center">
                            <el-icon class="mr-2"><Search /></el-icon>
                            Find Services
                        </Link>
                    </el-menu-item>

                    <el-sub-menu index="user">
                        <template #title>
                            <el-avatar :size="32" class="mr-2">
                                {{ user?.name?.charAt(0) }}
                            </el-avatar>
                            <span>{{ user?.name }}</span>
                        </template>
                        <el-menu-item index="profile">
                            <Link href="/dashboard/profile">
                                <el-icon class="mr-2"><User /></el-icon>
                                Profile
                            </Link>
                        </el-menu-item>
                        <el-menu-item index="settings">
                            <Link href="/dashboard/settings">
                                <el-icon class="mr-2"><Setting /></el-icon>
                                Settings
                            </Link>
                        </el-menu-item>
                        <el-divider class="!my-1" />
                        <el-menu-item index="logout">
                            <Link href="/logout" method="post" as="button" class="w-full text-left">
                                <el-icon class="mr-2"><SwitchButton /></el-icon>
                                Logout
                            </Link>
                        </el-menu-item>
                    </el-sub-menu>
                </div>
            </el-menu>
        </el-header>

        <!-- Page Content -->
        <el-main class="!p-0">
            <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </el-main>
    </div>
</template>

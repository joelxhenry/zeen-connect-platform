<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types/models';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const user = page.props.auth?.user;
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/" class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                            Zeen
                        </Link>
                        <div class="hidden md:ml-10 md:flex md:space-x-8">
                            <Link
                                href="/explore"
                                class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-white px-3 py-2 text-sm font-medium"
                            >
                                Explore
                            </Link>
                            <Link
                                href="/become-provider"
                                class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-white px-3 py-2 text-sm font-medium"
                            >
                                Become a Provider
                            </Link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <template v-if="user">
                            <Link
                                v-if="user.role === 'provider'"
                                href="/console"
                                class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-white px-3 py-2 text-sm font-medium"
                            >
                                Dashboard
                            </Link>
                            <Link
                                v-else
                                href="/dashboard"
                                class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-white px-3 py-2 text-sm font-medium"
                            >
                                Dashboard
                            </Link>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-white px-3 py-2 text-sm font-medium"
                            >
                                Logout
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                href="/login"
                                class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-white px-3 py-2 text-sm font-medium"
                            >
                                Login
                            </Link>
                            <Link
                                href="/register"
                                class="bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium"
                            >
                                Sign Up
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
            <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        &copy; {{ new Date().getFullYear() }} Zeen. All rights reserved.
                    </p>
                    <div class="flex space-x-6">
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white text-sm">
                            Privacy Policy
                        </a>
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white text-sm">
                            Terms of Service
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

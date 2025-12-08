<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <Link href="/" class="flex justify-center">
                <span class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">Zeen</span>
            </Link>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <el-card shadow="always" class="!rounded-lg">
                <slot />
            </el-card>
        </div>
    </div>
</template>

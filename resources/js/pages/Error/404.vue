<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ErrorPage from '@/components/error/ErrorPage.vue';

defineProps<{
    status?: number;
    message?: string;
}>();
</script>

<template>
    <Head title="Page Not Found" />
    <ErrorPage
        :status="404"
        title="Page Not Found"
        :message="message || 'The page you\'re looking for doesn\'t exist or has been moved. Check the URL or head back home.'"
        icon="pi pi-search"
    />
</template>

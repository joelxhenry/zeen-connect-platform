<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ErrorPage from '@/components/error/ErrorPage.vue';

defineProps<{
    status?: number;
    message?: string;
}>();
</script>

<template>
    <Head title="Access Denied" />
    <ErrorPage
        :status="403"
        title="Access Denied"
        :message="message || 'You don\'t have permission to access this page. If you think this is a mistake, contact support.'"
        icon="pi pi-lock"
    />
</template>

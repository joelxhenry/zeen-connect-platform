<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ErrorPage from '@/components/error/ErrorPage.vue';

defineProps<{
    status?: number;
    message?: string;
}>();
</script>

<template>
    <Head title="Server Error" />
    <ErrorPage
        :status="500"
        title="Something Went Wrong"
        :message="message || 'We\'re experiencing technical difficulties. Our team has been notified and is working to fix the issue.'"
        icon="pi pi-exclamation-triangle"
    />
</template>

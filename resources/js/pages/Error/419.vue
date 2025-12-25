<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ErrorPage from '@/components/error/ErrorPage.vue';

defineProps<{
    status?: number;
    message?: string;
}>();

const refreshPage = () => {
    window.location.reload();
};
</script>

<template>
    <Head title="Session Expired" />
    <ErrorPage
        :status="419"
        title="Session Expired"
        :message="message || 'Your session has expired for security reasons. Please refresh the page and try again.'"
        icon="pi pi-clock"
        :showBackButton="false"
    >
        <template #additional>
            <button @click="refreshPage" class="refresh-btn">
                <i class="pi pi-refresh"></i>
                Refresh Page
            </button>
        </template>
    </ErrorPage>
</template>

<style scoped>
.refresh-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #1ABC9C 0%, #16a085 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.refresh-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(26, 188, 156, 0.35);
}
</style>

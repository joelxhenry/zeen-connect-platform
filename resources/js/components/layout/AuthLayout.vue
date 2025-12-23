<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Card from 'primevue/card';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <Head :title="title" />
    <div class="auth-layout">
        <div class="auth-container">
            <Link href="/" class="logo-link">
                <span class="logo">Zeen</span>
            </Link>
        </div>

        <div class="card-container">
            <Card class="auth-card">
                <template #content>
                    <slot />
                </template>
            </Card>
        </div>
    </div>
</template>

<style scoped>
.auth-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 3rem 1.5rem;
    background-color: var(--color-surface-alt);
}

.auth-container {
    margin: 0 auto;
    width: 100%;
    max-width: 28rem;
    text-align: center;
}

.logo-link {
    display: inline-block;
}

.logo {
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-primary);
}

.card-container {
    margin-top: 2rem;
    margin-left: auto;
    margin-right: auto;
    width: 100%;
    max-width: 28rem;
}

.auth-card {
    background-color: var(--color-surface);
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.auth-card :deep(.p-card-body) {
    padding: 2rem;
}

.auth-card :deep(.p-card-content) {
    padding: 0;
}
</style>

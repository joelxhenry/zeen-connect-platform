<script setup lang="ts">
interface Props {
    title?: string;
    icon?: string;
    noPadding?: boolean;
}

withDefaults(defineProps<Props>(), {
    noPadding: false,
});
</script>

<template>
    <div class="form-card" :class="{ 'no-padding': noPadding }">
        <div v-if="title || $slots['header-actions']" class="card-header">
            <div class="header-title-row">
                <i v-if="icon" :class="icon" class="header-icon"></i>
                <h2 v-if="title" class="card-title">{{ title }}</h2>
            </div>
            <div v-if="$slots['header-actions']" class="header-actions">
                <slot name="header-actions" />
            </div>
        </div>
        <div v-if="!noPadding" class="card-body">
            <slot />
        </div>
        <slot v-else />
    </div>
</template>

<style scoped>
.form-card {
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--color-slate-100, #f1f5f9);
}

@media (min-width: 1024px) {
    .card-header {
        padding: 1rem 1.25rem;
    }
}

.header-title-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-icon {
    color: var(--color-slate-400, #94a3b8);
    font-size: 1rem;
}

.card-title {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 1rem;
}

@media (min-width: 1024px) {
    .card-body {
        padding: 1.25rem;
    }
}

.no-padding .card-body {
    padding: 0;
}
</style>

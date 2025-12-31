<script setup lang="ts">
interface Props {
    title: string;
    subtitle?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="page-header">
        <div class="header-content">
            <h1 class="header-title">{{ title }}</h1>
            <p v-if="subtitle" class="header-subtitle">{{ subtitle }}</p>
            <slot name="badge" />
        </div>
        <div v-if="$slots.actions" class="header-actions">
            <slot name="actions" />
        </div>
    </div>
</template>

<style scoped>
.page-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.header-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.header-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
    line-height: 1.2;
}

@media (min-width: 1024px) {
    .header-title {
        font-size: 1.75rem;
    }
}

.header-subtitle {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
</style>

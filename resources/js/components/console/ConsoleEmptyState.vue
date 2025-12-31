<script setup lang="ts">
import ConsoleButton from './ConsoleButton.vue';

interface Props {
    icon: string;
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
    actionIcon?: string;
    size?: 'default' | 'compact';
}

withDefaults(defineProps<Props>(), {
    size: 'default',
});
</script>

<template>
    <div class="empty-state" :class="[`size-${size}`]">
        <div class="icon-wrapper">
            <i :class="icon"></i>
        </div>
        <h3 class="title">{{ title }}</h3>
        <p class="description">{{ description }}</p>
        <div v-if="actionLabel" class="action">
            <ConsoleButton
                :label="actionLabel"
                :href="actionHref"
                :icon="actionIcon"
                variant="primary"
                :size="size === 'compact' ? 'small' : 'default'"
            />
        </div>
    </div>
</template>

<style scoped>
.empty-state {
    text-align: center;
}

.empty-state.size-default {
    padding: 2rem 1rem;
}

.empty-state.size-compact {
    padding: 1.5rem 1rem;
}

.icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    background-color: var(--color-slate-100, #f1f5f9);
    border-radius: 9999px;
}

.size-compact .icon-wrapper {
    width: 3rem;
    height: 3rem;
    margin-bottom: 0.75rem;
}

.icon-wrapper i {
    font-size: 1.5rem;
    color: var(--color-slate-400, #94a3b8);
}

.size-compact .icon-wrapper i {
    font-size: 1.25rem;
}

.title {
    margin: 0 0 0.375rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.size-compact .title {
    font-size: 0.9375rem;
}

.description {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
    max-width: 20rem;
    margin-left: auto;
    margin-right: auto;
}

.size-compact .description {
    font-size: 0.8125rem;
}

.action {
    margin-top: 1rem;
}

.size-compact .action {
    margin-top: 0.75rem;
}
</style>

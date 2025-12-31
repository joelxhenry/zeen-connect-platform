<script setup lang="ts">
import { resolveUrl } from '@/utils/url';

interface Props {
    icon: string;
    title: string;
    description: string;
    href: string;
    disabled?: boolean;
    badge?: string;
}

withDefaults(defineProps<Props>(), {
    disabled: false,
});
</script>

<template>
    <AppLink
        :href="resolveUrl(href)"
        class="settings-card"
        :class="{ disabled }"
    >
        <div class="card-icon">
            <i :class="icon"></i>
        </div>
        <div class="card-content">
            <div class="card-title-row">
                <h3 class="card-title">{{ title }}</h3>
                <span v-if="badge" class="card-badge">{{ badge }}</span>
            </div>
            <p class="card-description">{{ description }}</p>
        </div>
        <div class="card-arrow">
            <i class="pi pi-chevron-right"></i>
        </div>
    </AppLink>
</template>

<style scoped>
.settings-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
    text-decoration: none;
    transition: all 0.15s ease;
    cursor: pointer;
}

.settings-card:hover:not(.disabled) {
    border-color: var(--color-slate-300, #cbd5e1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.settings-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

.card-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(16, 107, 79, 0.08);
    border-radius: 0.75rem;
}

.card-icon i {
    font-size: 1.25rem;
    color: #106B4F;
}

.card-content {
    flex: 1;
    min-width: 0;
}

.card-title-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.card-badge {
    padding: 0.125rem 0.5rem;
    background-color: var(--color-slate-100, #f1f5f9);
    color: var(--color-slate-500, #64748b);
    font-size: 0.625rem;
    font-weight: 600;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.card-description {
    margin: 0.25rem 0 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.4;
}

.card-arrow {
    flex-shrink: 0;
    color: var(--color-slate-300, #cbd5e1);
    transition: color 0.15s ease, transform 0.15s ease;
}

.settings-card:hover:not(.disabled) .card-arrow {
    color: var(--color-slate-400, #94a3b8);
    transform: translateX(2px);
}
</style>

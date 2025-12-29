<script setup lang="ts">
defineProps<{
    stats: Array<{
        value: string | number;
        label: string;
        icon?: string;
    }>;
}>();
</script>

<template>
    <div class="stats-bar">
        <div
            v-for="(stat, index) in stats"
            :key="index"
            class="stat-item"
        >
            <div class="stat-value">
                <i v-if="stat.icon" :class="stat.icon" class="stat-icon"></i>
                {{ stat.value }}
            </div>
            <div class="stat-label">{{ stat.label }}</div>
        </div>
    </div>
</template>

<style scoped>
.stats-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background: var(--provider-border, #e5e5e5);
    border: 1px solid var(--provider-border, #e5e5e5);
}

.stat-item {
    background: var(--provider-surface, #fff);
    padding: 1.5rem 1rem;
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-primary, #1a1a1a);
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.stat-icon {
    font-size: 1.25rem;
}

.stat-label {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--provider-secondary, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

@media (max-width: 768px) {
    .stats-bar {
        grid-template-columns: repeat(2, 1fr);
    }

    .stat-value {
        font-size: 1.5rem;
    }

    .stat-item {
        padding: 1rem 0.75rem;
    }
}

@media (max-width: 480px) {
    .stat-value {
        font-size: 1.25rem;
    }

    .stat-label {
        font-size: 0.6875rem;
    }
}
</style>

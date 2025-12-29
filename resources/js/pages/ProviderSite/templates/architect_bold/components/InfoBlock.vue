<script setup lang="ts">
withDefaults(defineProps<{
    icon?: string;
    title: string;
    description?: string;
    variant?: 'default' | 'compact' | 'featured';
}>(), {
    variant: 'default',
});
</script>

<template>
    <div class="info-block" :class="[`variant-${variant}`]">
        <div class="info-content">
            <div v-if="icon" class="info-icon">
                <i :class="icon"></i>
            </div>
            <div class="info-text">
                <h4 class="info-title">{{ title }}</h4>
                <p v-if="description" class="info-description">{{ description }}</p>
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
.info-block {
    background: var(--provider-surface, #fff);
    padding: 1.5rem;
}

.info-block.variant-compact {
    padding: 1rem;
}

.info-block.variant-featured {
    padding: 2rem;
    border: 2px solid var(--provider-primary, #1a1a1a);
}

.info-content {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.info-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--provider-primary-10, rgba(26, 26, 26, 0.1));
    color: var(--provider-primary, #1a1a1a);
    font-size: 1.25rem;
}

.variant-compact .info-icon {
    width: 40px;
    height: 40px;
    font-size: 1rem;
}

.variant-featured .info-icon {
    width: 56px;
    height: 56px;
    font-size: 1.5rem;
    background: var(--provider-primary, #1a1a1a);
    color: var(--provider-surface, #fff);
}

.info-text {
    flex: 1;
    min-width: 0;
}

.info-title {
    margin: 0 0 0.375rem 0;
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.variant-compact .info-title {
    font-size: 0.875rem;
}

.variant-featured .info-title {
    font-size: 1rem;
}

.info-description {
    margin: 0;
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.6;
}

.variant-compact .info-description {
    font-size: 0.8125rem;
}

@media (max-width: 640px) {
    .info-content {
        flex-direction: column;
    }

    .info-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>

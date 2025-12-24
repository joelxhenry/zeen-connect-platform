<script setup lang="ts">
interface Props {
    step: number;
    title: string;
    active?: boolean;
    disabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    active: true,
    disabled: false,
});
</script>

<template>
    <div class="step-card" :class="{ 'step-card--disabled': disabled }">
        <div class="step-card__header">
            <h2 class="step-card__title">
                <span class="step-card__number" :class="{ 'step-card__number--active': active }">
                    {{ step }}
                </span>
                {{ title }}
            </h2>
        </div>
        <div class="step-card__content">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.step-card {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: opacity 0.2s;
}

.step-card--disabled {
    opacity: 0.5;
    pointer-events: none;
}

.step-card__header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
}

.step-card__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.step-card__number {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #d1d5db;
    color: #6b7280;
}

.step-card__number--active {
    background-color: #106B4F;
    color: white;
}

.step-card__content {
    padding: 1.25rem;
}
</style>

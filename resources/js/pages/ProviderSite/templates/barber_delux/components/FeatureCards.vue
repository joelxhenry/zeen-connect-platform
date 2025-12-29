<script setup lang="ts">
interface Feature {
    icon: string;
    title: string;
    description: string;
}

interface Props {
    features: Feature[];
    title?: string;
    subtitle?: string;
    columns?: 2 | 3 | 4;
}

const props = withDefaults(defineProps<Props>(), {
    columns: 4,
});

const gridClass = `feature-cards__grid--cols-${props.columns}`;
</script>

<template>
    <section class="feature-cards">
        <div class="feature-cards__container">
            <div v-if="title || subtitle" class="feature-cards__header">
                <h2 v-if="title" class="feature-cards__title">{{ title }}</h2>
                <p v-if="subtitle" class="feature-cards__subtitle">{{ subtitle }}</p>
            </div>

            <div class="feature-cards__grid" :class="gridClass">
                <div
                    v-for="(feature, index) in features"
                    :key="index"
                    class="feature-card"
                >
                    <div class="feature-card__icon-wrapper">
                        <i :class="feature.icon" class="feature-card__icon"></i>
                    </div>
                    <h3 class="feature-card__title">{{ feature.title }}</h3>
                    <p class="feature-card__description">{{ feature.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.feature-cards {
    padding: 4rem 1.5rem;
    background: var(--provider-surface, #fff);
}

.feature-cards__container {
    max-width: 1200px;
    margin: 0 auto;
}

.feature-cards__header {
    text-align: center;
    margin-bottom: 3rem;
}

.feature-cards__title {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--provider-text, #1f2937);
}

.feature-cards__subtitle {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-text-muted, #6b7280);
}

.feature-cards__grid {
    display: grid;
    gap: 1.5rem;
}

.feature-cards__grid--cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.feature-cards__grid--cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.feature-cards__grid--cols-4 {
    grid-template-columns: repeat(4, 1fr);
}

.feature-card {
    padding: 1.5rem;
    background: var(--provider-background, #f9fafb);
    border-radius: 0.75rem;
    text-align: center;
    transition: box-shadow 0.2s;
}

.feature-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.feature-card__icon-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    margin-bottom: 1rem;
    border-radius: 0.75rem;
    background: var(--provider-primary-10, rgba(59, 130, 246, 0.1));
}

.feature-card__icon {
    font-size: 1.5rem;
    color: var(--provider-primary, #3b82f6);
}

.feature-card__title {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.feature-card__description {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--provider-text-muted, #6b7280);
}

@media (max-width: 1024px) {
    .feature-cards__grid--cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .feature-cards__grid--cols-2,
    .feature-cards__grid--cols-3,
    .feature-cards__grid--cols-4 {
        grid-template-columns: 1fr;
    }
}
</style>

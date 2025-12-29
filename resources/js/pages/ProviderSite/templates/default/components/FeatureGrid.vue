<script setup lang="ts">
interface Feature {
    icon: string;
    title: string;
    description: string;
}

interface Props {
    features: Feature[];
    columns?: 2 | 3 | 4;
    variant?: 'light' | 'dark';
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    columns: 4,
    variant: 'light',
});

const gridClass = `feature-grid--cols-${props.columns}`;
</script>

<template>
    <section class="feature-section" :class="`feature-section--${variant}`">
        <div class="feature-section__container">
            <h2 v-if="title" class="feature-section__title">{{ title }}</h2>

            <div class="feature-grid" :class="gridClass">
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
.feature-section {
    padding: 4rem 1.5rem;
}

.feature-section--light {
    background: #f9fafb;
}

.feature-section--dark {
    background: var(--barber-dark-secondary, #3D3024);
}

.feature-section__container {
    max-width: 1200px;
    margin: 0 auto;
}

.feature-section__title {
    text-align: center;
    margin: 0 0 2.5rem 0;
    font-size: 1.75rem;
    font-weight: 600;
}

.feature-section--light .feature-section__title {
    color: var(--provider-text, #1f2937);
}

.feature-section--dark .feature-section__title {
    color: #fff;
}

.feature-grid {
    display: grid;
    gap: 1.5rem;
}

.feature-grid--cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.feature-grid--cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.feature-grid--cols-4 {
    grid-template-columns: repeat(4, 1fr);
}

.feature-card {
    padding: 1.5rem;
    border-radius: 0.75rem;
    text-align: center;
}

.feature-section--light .feature-card {
    background: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.feature-section--dark .feature-card {
    background: var(--barber-dark, #2A2018);
}

.feature-card__icon-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    margin-bottom: 1rem;
    border-radius: 0.5rem;
}

.feature-section--light .feature-card__icon-wrapper {
    background: var(--provider-primary-10, rgba(196, 169, 98, 0.1));
}

.feature-section--dark .feature-card__icon-wrapper {
    background: var(--barber-dark-secondary, #3D3024);
    border: 1px solid var(--provider-primary, #C4A962);
}

.feature-card__icon {
    font-size: 1.5rem;
    color: var(--provider-primary, #C4A962);
}

.feature-card__title {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
}

.feature-section--light .feature-card__title {
    color: var(--provider-text, #1f2937);
}

.feature-section--dark .feature-card__title {
    color: #fff;
}

.feature-card__description {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.5;
}

.feature-section--light .feature-card__description {
    color: #6b7280;
}

.feature-section--dark .feature-card__description {
    color: var(--barber-text-muted, #A69F94);
}

@media (max-width: 1024px) {
    .feature-grid--cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .feature-grid--cols-2,
    .feature-grid--cols-3,
    .feature-grid--cols-4 {
        grid-template-columns: 1fr;
    }
}
</style>

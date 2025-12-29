<script setup lang="ts">
import Button from 'primevue/button';

interface Stat {
    value: string;
    label: string;
}

interface Props {
    headline: string;
    accentWord?: string;
    subtext?: string;
    stats?: Stat[];
    ctaText?: string;
    ctaUrl?: string;
    videoUrl?: string;
    heroImage?: string;
    variant?: 'light' | 'dark';
    layout?: 'centered' | 'split';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'light',
    layout: 'split',
    ctaText: 'Book Now',
    ctaUrl: '/book',
});

// Split headline into parts if accentWord is provided
const headlineParts = (() => {
    if (!props.accentWord) return { before: props.headline, accent: '', after: '' };

    const index = props.headline.toLowerCase().indexOf(props.accentWord.toLowerCase());
    if (index === -1) return { before: props.headline, accent: '', after: '' };

    return {
        before: props.headline.slice(0, index),
        accent: props.headline.slice(index, index + props.accentWord.length),
        after: props.headline.slice(index + props.accentWord.length),
    };
})();

const emit = defineEmits<{
    videoClick: [];
}>();
</script>

<template>
    <section class="hero" :class="[`hero--${variant}`, `hero--${layout}`]">
        <div class="hero__container">
            <div class="hero__content">
                <h1 class="hero__headline">
                    <span v-if="headlineParts.before">{{ headlineParts.before }}</span>
                    <span v-if="headlineParts.accent" class="hero__headline-accent">{{ headlineParts.accent }}</span>
                    <span v-if="headlineParts.after">{{ headlineParts.after }}</span>
                </h1>

                <p v-if="subtext" class="hero__subtext">{{ subtext }}</p>

                <div v-if="stats && stats.length > 0" class="hero__stats">
                    <div v-for="(stat, index) in stats" :key="index" class="hero__stat">
                        <span class="hero__stat-value">{{ stat.value }}</span>
                        <span class="hero__stat-label">{{ stat.label }}</span>
                    </div>
                </div>

                <div class="hero__actions">
                    <button
                        v-if="videoUrl"
                        class="hero__video-btn"
                        @click="emit('videoClick')"
                    >
                        <i class="pi pi-play"></i>
                        <span>Watch Video</span>
                    </button>

                    <AppLink :href="ctaUrl" class="hero__cta-link">
                        <Button :label="ctaText" icon="pi pi-arrow-right" iconPos="right" class="hero__cta" />
                    </AppLink>
                </div>
            </div>

            <div v-if="layout === 'split' && heroImage" class="hero__image">
                <img :src="heroImage" alt="Hero" />
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero {
    padding: 4rem 1.5rem;
    overflow: hidden;
}

.hero--light {
    background: var(--barber-cream, #F5F1E8);
}

.hero--dark {
    background: var(--barber-dark, #2A2018);
}

.hero__container {
    max-width: 1280px;
    margin: 0 auto;
}

.hero--split .hero__container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.hero--centered .hero__container {
    text-align: center;
    max-width: 800px;
}

.hero--centered .hero__content {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.hero__headline {
    margin: 0 0 1rem 0;
    font-size: 3rem;
    font-weight: 700;
    line-height: 1.1;
    text-transform: uppercase;
}

.hero--light .hero__headline {
    color: var(--provider-text, #1f2937);
}

.hero--dark .hero__headline {
    color: #fff;
}

.hero__headline-accent {
    color: var(--provider-primary, #C4A962);
}

.hero__subtext {
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    line-height: 1.6;
    max-width: 500px;
}

.hero--light .hero__subtext {
    color: #6b7280;
}

.hero--dark .hero__subtext {
    color: var(--barber-text-muted, #A69F94);
}

.hero__stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
}

.hero__stat {
    display: flex;
    flex-direction: column;
}

.hero__stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--provider-primary, #C4A962);
}

.hero__stat-label {
    font-size: 0.875rem;
}

.hero--light .hero__stat-label {
    color: #6b7280;
}

.hero--dark .hero__stat-label {
    color: var(--barber-text-muted, #A69F94);
}

.hero__actions {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.hero--centered .hero__actions {
    justify-content: center;
}

.hero__video-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border: none;
    border-radius: 9999px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.hero--light .hero__video-btn {
    background: #fff;
    color: var(--provider-text, #1f2937);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.hero--dark .hero__video-btn {
    background: var(--barber-dark-secondary, #3D3024);
    color: #fff;
}

.hero__video-btn:hover {
    transform: scale(1.05);
}

.hero__video-btn i {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--provider-primary, #C4A962);
    color: var(--barber-dark, #2A2018);
    font-size: 0.75rem;
}

.hero__cta-link {
    text-decoration: none;
}

:deep(.hero__cta) {
    background: var(--provider-primary, #C4A962) !important;
    border-color: var(--provider-primary, #C4A962) !important;
    color: var(--barber-dark, #2A2018) !important;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
}

:deep(.hero__cta:hover) {
    background: var(--provider-primary-hover, #B39952) !important;
    border-color: var(--provider-primary-hover, #B39952) !important;
}

.hero__image {
    position: relative;
}

.hero__image img {
    width: 100%;
    height: auto;
    border-radius: 1rem;
    object-fit: cover;
}

@media (max-width: 1024px) {
    .hero__headline {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .hero--split .hero__container {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .hero__headline {
        font-size: 2rem;
    }

    .hero__stats {
        flex-wrap: wrap;
        gap: 1rem;
    }

    .hero__actions {
        flex-direction: column;
        gap: 1rem;
    }

    .hero__image {
        order: -1;
    }
}
</style>

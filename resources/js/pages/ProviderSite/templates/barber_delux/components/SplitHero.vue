<script setup lang="ts">
import Button from 'primevue/button';

interface Stat {
    value: string;
    label: string;
}

interface Provider {
    business_name: string;
    tagline?: string;
    cover_image?: string;
    avatar?: string;
}

interface Props {
    provider: Provider;
    stats?: Stat[];
    ctaText?: string;
    ctaUrl?: string;
    showVideoButton?: boolean;
    videoUrl?: string;
}

withDefaults(defineProps<Props>(), {
    ctaText: 'Book Now',
    ctaUrl: '/book',
    showVideoButton: false,
});

const emit = defineEmits<{
    videoClick: [];
}>();
</script>

<template>
    <section class="split-hero">
        <div class="split-hero__container">
            <div class="split-hero__content">
                <h1 class="split-hero__title">{{ provider.business_name }}</h1>
                <p v-if="provider.tagline" class="split-hero__tagline">{{ provider.tagline }}</p>

                <div v-if="stats && stats.length > 0" class="split-hero__stats">
                    <div v-for="(stat, index) in stats" :key="index" class="split-hero__stat">
                        <span class="split-hero__stat-value">{{ stat.value }}</span>
                        <span class="split-hero__stat-label">{{ stat.label }}</span>
                    </div>
                </div>

                <div class="split-hero__actions">
                    <button
                        v-if="showVideoButton && videoUrl"
                        class="split-hero__video-btn"
                        @click="emit('videoClick')"
                    >
                        <i class="pi pi-play"></i>
                    </button>

                    <AppLink :href="ctaUrl" class="split-hero__cta-link">
                        <Button :label="ctaText" icon="pi pi-arrow-right" iconPos="right" class="split-hero__cta" />
                    </AppLink>
                </div>
            </div>

            <div class="split-hero__image">
                <img
                    v-if="provider.cover_image || provider.avatar"
                    :src="provider.cover_image || provider.avatar"
                    :alt="provider.business_name"
                />
                <div v-else class="split-hero__image-placeholder">
                    <i class="pi pi-image"></i>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.split-hero {
    padding: 4rem 1.5rem;
    background: var(--provider-background, #f9fafb);
}

.split-hero__container {
    max-width: 1280px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.split-hero__title {
    margin: 0 0 0.5rem 0;
    font-size: 3rem;
    font-weight: 700;
    line-height: 1.1;
    color: var(--provider-text, #1f2937);
}

.split-hero__tagline {
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    line-height: 1.6;
    color: var(--provider-text-muted, #6b7280);
    max-width: 500px;
}

.split-hero__stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
}

.split-hero__stat {
    display: flex;
    flex-direction: column;
}

.split-hero__stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--provider-primary, #3b82f6);
}

.split-hero__stat-label {
    font-size: 0.875rem;
    color: var(--provider-text-muted, #6b7280);
}

.split-hero__actions {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.split-hero__video-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border: none;
    border-radius: 50%;
    background: var(--provider-surface, #fff);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s;
}

.split-hero__video-btn:hover {
    transform: scale(1.05);
}

.split-hero__video-btn i {
    font-size: 1rem;
    color: var(--provider-primary, #3b82f6);
}

.split-hero__cta-link {
    text-decoration: none;
}

:deep(.split-hero__cta) {
    background: var(--provider-primary, #3b82f6) !important;
    border-color: var(--provider-primary, #3b82f6) !important;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
}

:deep(.split-hero__cta:hover) {
    background: var(--provider-primary-hover, #2563eb) !important;
    border-color: var(--provider-primary-hover, #2563eb) !important;
}

.split-hero__image {
    position: relative;
}

.split-hero__image img {
    width: 100%;
    height: auto;
    border-radius: 1rem;
    object-fit: cover;
    max-height: 500px;
}

.split-hero__image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 400px;
    border-radius: 1rem;
    background: var(--provider-surface, #f3f4f6);
}

.split-hero__image-placeholder i {
    font-size: 4rem;
    color: var(--provider-text-muted, #9ca3af);
}

@media (max-width: 1024px) {
    .split-hero__title {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .split-hero__container {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .split-hero__title {
        font-size: 2rem;
    }

    .split-hero__stats {
        flex-wrap: wrap;
        gap: 1rem;
    }

    .split-hero__image {
        order: -1;
    }
}
</style>

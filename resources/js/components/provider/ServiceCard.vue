<script setup lang="ts">
interface Category {
    id: number;
    name: string;
    icon?: string;
    slug: string;
}

interface Service {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    duration_display: string;
    price_display: string;
    display_image?: string;
}

interface Props {
    service: Service;
    category?: Category | null;
    bookingUrl: string;
    showCategory?: boolean;
    showImage?: boolean;
}

withDefaults(defineProps<Props>(), {
    showCategory: true,
    category: null,
    showImage: true,
});
</script>

<template>
    <AppLink :href="bookingUrl" class="service-card" :class="{ 'service-card--with-image': showImage && service.display_image }">
        <!-- Service Image -->
        <div v-if="showImage && service.display_image" class="service-card__image">
            <img :src="service.display_image" :alt="service.name" />
        </div>

        <div class="service-card__content">
            <div class="service-card__info">
                <h3>{{ service.name }}</h3>
                <p v-if="service.description" class="service-card__description">
                    {{ service.description }}
                </p>
                <div class="service-card__meta">
                    <span class="service-card__duration">
                        <i class="pi pi-clock"></i>
                        {{ service.duration_display }}
                    </span>
                    <span v-if="showCategory && category" class="service-card__category">
                        {{ category.name }}
                    </span>
                </div>
            </div>
            <div class="service-card__price">
                {{ service.price_display }}
            </div>
        </div>
    </AppLink>
</template>

<style scoped>
.service-card {
    background: white;
    border-radius: 0.75rem;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.15s, transform 0.15s;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.service-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.service-card__image {
    width: 100%;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: #f3f4f6;
}

.service-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.service-card:hover .service-card__image img {
    transform: scale(1.05);
}

.service-card__content {
    padding: 1.25rem;
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    flex: 1;
}

.service-card__info {
    flex: 1;
    min-width: 0;
}

.service-card__info h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 500;
    color: var(--provider-text);
}

.service-card__description {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: #6b7280;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.service-card__meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: #9ca3af;
}

.service-card__duration {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.service-card__duration i {
    font-size: 0.75rem;
}

.service-card__category {
    padding: 0.125rem 0.5rem;
    background: #f3f4f6;
    border-radius: 9999px;
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.service-card__price {
    font-weight: 600;
    color: var(--provider-primary);
    white-space: nowrap;
    font-size: 1.125rem;
    display: flex;
    align-items: flex-start;
}
</style>

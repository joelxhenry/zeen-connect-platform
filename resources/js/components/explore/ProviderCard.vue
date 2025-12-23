<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Tag from 'primevue/tag';

interface ProviderCardData {
    id: number;
    uuid: string;
    slug: string;
    business_name: string;
    tagline?: string;
    avatar?: string;
    location?: string;
    rating_avg: number;
    rating_count: number;
    services_count: number;
    is_featured: boolean;
    categories: Array<{ id: number; name: string; icon?: string }>;
    preview_services: Array<{ id: number; name: string; price: number; duration_display: string }>;
}

defineProps<{
    provider: ProviderCardData;
}>();

const formatPrice = (price: number): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(price);
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <Link :href="route('provider.public', provider.slug)" class="provider-card">
        <div class="card-header">
            <div class="avatar-wrapper">
                <img
                    v-if="provider.avatar"
                    :src="provider.avatar"
                    :alt="provider.business_name"
                    class="avatar"
                />
                <div v-else class="avatar-placeholder">
                    {{ getInitials(provider.business_name) }}
                </div>
                <div v-if="provider.is_featured" class="featured-badge">
                    <i class="pi pi-star-fill"></i>
                </div>
            </div>
            <div class="header-info">
                <h3 class="business-name">{{ provider.business_name }}</h3>
                <p v-if="provider.tagline" class="tagline">{{ provider.tagline }}</p>
                <div class="location" v-if="provider.location">
                    <i class="pi pi-map-marker"></i>
                    <span>{{ provider.location }}</span>
                </div>
            </div>
        </div>

        <div class="card-stats">
            <div class="stat">
                <div class="stat-value">
                    <i class="pi pi-star-fill star-icon"></i>
                    {{ provider.rating_avg > 0 ? provider.rating_avg.toFixed(1) : 'New' }}
                </div>
                <div class="stat-label" v-if="provider.rating_count > 0">
                    ({{ provider.rating_count }} {{ provider.rating_count === 1 ? 'review' : 'reviews' }})
                </div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ provider.services_count }}</div>
                <div class="stat-label">{{ provider.services_count === 1 ? 'service' : 'services' }}</div>
            </div>
        </div>

        <div class="categories" v-if="provider.categories.length > 0">
            <Tag
                v-for="category in provider.categories.slice(0, 3)"
                :key="category.id"
                severity="secondary"
                class="category-tag"
            >
                <i v-if="category.icon" :class="`pi ${category.icon}`"></i>
                {{ category.name }}
            </Tag>
            <Tag
                v-if="provider.categories.length > 3"
                severity="secondary"
                class="category-tag"
            >
                +{{ provider.categories.length - 3 }}
            </Tag>
        </div>

        <div class="services-preview" v-if="provider.preview_services.length > 0">
            <div
                v-for="service in provider.preview_services"
                :key="service.id"
                class="service-item"
            >
                <span class="service-name">{{ service.name }}</span>
                <span class="service-price">{{ formatPrice(service.price) }}</span>
            </div>
        </div>

        <div class="card-footer">
            <span class="view-profile">View Profile <i class="pi pi-arrow-right"></i></span>
        </div>
    </Link>
</template>

<style scoped>
.provider-card {
    display: flex;
    flex-direction: column;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 16px;
    padding: 1.25rem;
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.provider-card:hover {
    border-color: var(--p-primary-200);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.card-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.avatar-wrapper {
    position: relative;
    flex-shrink: 0;
}

.avatar {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    object-fit: cover;
}

.avatar-placeholder {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--p-primary-100), var(--p-primary-200));
    color: var(--p-primary-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.125rem;
}

.featured-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.625rem;
    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
}

.header-info {
    flex: 1;
    min-width: 0;
}

.business-name {
    font-size: 1.0625rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.25rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tagline {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
    margin: 0 0 0.375rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.location {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.location i {
    font-size: 0.75rem;
    color: var(--p-surface-400);
}

.card-stats {
    display: flex;
    gap: 1.5rem;
    padding: 0.75rem 0;
    border-top: 1px solid var(--p-surface-100);
    border-bottom: 1px solid var(--p-surface-100);
    margin-bottom: 0.75rem;
}

.stat {
    display: flex;
    align-items: baseline;
    gap: 0.375rem;
}

.stat-value {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-weight: 600;
    color: var(--p-surface-900);
}

.star-icon {
    color: #fbbf24;
    font-size: 0.75rem;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.categories {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    margin-bottom: 0.75rem;
}

.category-tag {
    font-size: 0.6875rem;
    padding: 0.25rem 0.5rem;
}

.category-tag i {
    margin-right: 0.25rem;
    font-size: 0.625rem;
}

.services-preview {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.75rem;
    background-color: var(--p-surface-50);
    border-radius: 8px;
    margin-bottom: 0.75rem;
}

.service-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8125rem;
}

.service-name {
    color: var(--p-surface-700);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
    margin-right: 0.5rem;
}

.service-price {
    font-weight: 600;
    color: var(--p-surface-900);
    white-space: nowrap;
}

.card-footer {
    margin-top: auto;
    padding-top: 0.5rem;
}

.view-profile {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-primary-color);
    transition: gap 0.2s;
}

.provider-card:hover .view-profile {
    gap: 0.5rem;
}

.view-profile i {
    font-size: 0.75rem;
}
</style>

<script setup lang="ts">
import DefaultLayout from './components/DefaultLayout.vue';
import Button from 'primevue/button';

interface Service {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    display_image?: string;
}

interface ServiceCategory {
    category: {
        id: number;
        name: string;
        icon?: string;
        slug: string;
    };
    services: Service[];
}

interface Props {
    provider: {
        id: number;
        business_name: string;
        slug: string;
        services_count: number;
    };
    servicesByCategory: ServiceCategory[];
}

const props = defineProps<Props>();

// Use relative path since we're already on the provider's subdomain
const getBookingUrl = (serviceId: number) => `/book?service=${serviceId}`;
</script>

<template>
    <DefaultLayout title="Services">
        <div class="services-page">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>Our Services</h1>
                    <p>Choose from {{ provider.services_count }} services</p>
                </div>

                <!-- Services by Category -->
                <div class="categories-list">
                    <div v-for="categoryGroup in servicesByCategory" :key="categoryGroup.category.id" class="category-section">
                        <div class="category-header">
                            <i v-if="categoryGroup.category.icon" :class="categoryGroup.category.icon" class="category-icon"></i>
                            <h2>{{ categoryGroup.category.name }}</h2>
                            <span class="service-count">{{ categoryGroup.services.length }} {{ categoryGroup.services.length === 1 ? 'service' : 'services' }}</span>
                        </div>
                        <div class="services-list">
                            <div v-for="service in categoryGroup.services" :key="service.id" class="service-item" :class="{ 'has-image': service.display_image }">
                                <div v-if="service.display_image" class="service-image">
                                    <img :src="service.display_image" :alt="service.name" />
                                </div>
                                <div class="service-info">
                                    <h3>{{ service.name }}</h3>
                                    <p v-if="service.description" class="description">{{ service.description }}</p>
                                    <div class="service-meta">
                                        <span class="duration">
                                            <i class="pi pi-clock"></i>
                                            {{ service.duration_display }}
                                        </span>
                                    </div>
                                </div>
                                <div class="service-actions">
                                    <span class="price">{{ service.price_display }}</span>
                                    <AppLink :href="getBookingUrl(service.id)">
                                        <Button label="Book" size="small" class="btn-primary" />
                                    </AppLink>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="servicesByCategory.length === 0" class="empty-state">
                    <i class="pi pi-inbox"></i>
                    <h3>No services available</h3>
                    <p>This provider hasn't added any services yet.</p>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

<style scoped>
.services-page {
    padding: 2rem 0 4rem;
}

.page-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--provider-text);
}

.page-header p {
    margin: 0;
    color: var(--provider-text-muted);
}

.categories-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.category-section {
    background: var(--provider-surface);
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: var(--provider-shadow-sm);
}

.category-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: var(--provider-background);
    border-bottom: 1px solid var(--provider-border);
}

.category-icon {
    font-size: 1.25rem;
    color: var(--provider-primary);
}

.category-header h2 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text);
    flex: 1;
}

.service-count {
    font-size: 0.75rem;
    color: var(--provider-text-subtle);
}

.services-list {
    display: flex;
    flex-direction: column;
}

.service-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
    padding: 1.25rem;
    border-bottom: 1px solid var(--provider-border-light);
}

.service-item:last-child {
    border-bottom: none;
}

.service-item.has-image {
    flex-wrap: wrap;
}

.service-image {
    width: 120px;
    height: 80px;
    flex-shrink: 0;
    border-radius: 0.5rem;
    overflow: hidden;
    background: var(--provider-background-alt);
}

.service-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.service-info {
    flex: 1;
    min-width: 0;
}

.service-info h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 500;
    color: var(--provider-text);
}

.service-info .description {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: var(--provider-text-muted);
    line-height: 1.4;
}

.service-meta {
    display: flex;
    gap: 1rem;
}

.service-meta .duration {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--provider-text-subtle);
}

.service-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
}

.service-actions .price {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-primary);
}

/* Primary button styling */
:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--provider-surface);
    border-radius: 0.75rem;
    box-shadow: var(--provider-shadow-sm);
}

.empty-state i {
    font-size: 3rem;
    color: var(--provider-border);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--provider-text);
}

.empty-state p {
    margin: 0;
    color: var(--provider-text-muted);
}

@media (max-width: 640px) {
    .service-item {
        flex-direction: column;
        gap: 1rem;
    }

    .service-actions {
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
    }
}
</style>

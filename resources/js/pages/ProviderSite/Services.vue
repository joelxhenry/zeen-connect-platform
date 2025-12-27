<script setup lang="ts">
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
import Button from 'primevue/button';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';

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

const getBookingUrl = (serviceId: number) => {
    return ProviderSiteBookingController.create({ provider: props.provider.slug, service: serviceId }).url;
};
</script>

<template>
    <ProviderSiteLayout title="Services">
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
                                        <Button label="Book" size="small" class="!bg-[#106B4F] !border-[#106B4F]" />
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
    </ProviderSiteLayout>
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
    color: #0D1F1B;
}

.page-header p {
    margin: 0;
    color: #6b7280;
}

.categories-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.category-section {
    background: white;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.category-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.category-icon {
    font-size: 1.25rem;
    color: #106B4F;
}

.category-header h2 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #0D1F1B;
    flex: 1;
}

.service-count {
    font-size: 0.75rem;
    color: #9ca3af;
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
    border-bottom: 1px solid #f3f4f6;
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
    background: #f3f4f6;
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
    color: #0D1F1B;
}

.service-info .description {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: #6b7280;
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
    color: #9ca3af;
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
    color: #106B4F;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-state i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: #0D1F1B;
}

.empty-state p {
    margin: 0;
    color: #6b7280;
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

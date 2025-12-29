<script setup lang="ts">
import BarberDeluxLayout from './components/BarberDeluxLayout.vue';
import Button from 'primevue/button';
import type { ServicesPageProps } from '@/types/providersite';

const props = defineProps<ServicesPageProps>();

const getBookingUrl = (serviceId: number) => `/book?service=${serviceId}`;
</script>

<template>
    <BarberDeluxLayout title="Services">
        <div class="services-page">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>{{ provider.business_name }}</h1>
                    <p>{{ provider.services_count }} services available</p>
                </div>

                <!-- Services by Category -->
                <div class="categories-list">
                    <div
                        v-for="categoryGroup in servicesByCategory"
                        :key="categoryGroup.category.id"
                        class="category-section"
                    >
                        <div class="category-header">
                            <div class="category-title-wrapper">
                                <i
                                    v-if="categoryGroup.category.icon"
                                    :class="categoryGroup.category.icon"
                                    class="category-icon"
                                ></i>
                                <h2>{{ categoryGroup.category.name }}</h2>
                            </div>
                            <span class="service-count">
                                {{ categoryGroup.services.length }}
                            </span>
                        </div>

                        <div class="services-list">
                            <div
                                v-for="service in categoryGroup.services"
                                :key="service.id"
                                class="service-item"
                            >
                                <div
                                    v-if="service.display_image"
                                    class="service-image"
                                >
                                    <img :src="service.display_image" :alt="service.name" />
                                </div>
                                <div class="service-info">
                                    <h3>{{ service.name }}</h3>
                                    <p v-if="service.description" class="description">
                                        {{ service.description }}
                                    </p>
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
                                        <Button label="Book" size="small" class="book-btn" />
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
                </div>
            </div>
        </div>
    </BarberDeluxLayout>
</template>

<style scoped>
.services-page {
    padding: 2rem 0 4rem;
    background: var(--provider-background, #f9fafb);
    min-height: 100vh;
}

.page-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.page-header {
    margin-bottom: 2rem;
    text-align: center;
}

.page-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-text, #1f2937);
}

.page-header p {
    margin: 0;
    color: var(--provider-text-muted, #6b7280);
}

.categories-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.category-section {
    background: var(--provider-surface, #fff);
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: var(--provider-background, #f9fafb);
    border-bottom: 1px solid var(--provider-primary, #3b82f6);
}

.category-title-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.category-icon {
    font-size: 1.25rem;
    color: var(--provider-primary, #3b82f6);
}

.category-header h2 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.service-count {
    font-size: 0.875rem;
    color: var(--provider-text-muted, #6b7280);
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
    border-bottom: 1px solid var(--provider-border, #e5e7eb);
    transition: background-color 0.2s;
}

.service-item:hover {
    background: var(--provider-background, #f9fafb);
}

.service-item:last-child {
    border-bottom: none;
}

.service-image {
    width: 100px;
    height: 70px;
    flex-shrink: 0;
    border-radius: 0.5rem;
    overflow: hidden;
    background: var(--provider-background, #f3f4f6);
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
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.service-info .description {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: var(--provider-text-muted, #6b7280);
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
    color: var(--provider-text-muted, #9ca3af);
}

.service-meta .duration i {
    color: var(--provider-primary, #3b82f6);
}

.service-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
}

.service-actions .price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-primary, #3b82f6);
}

:deep(.book-btn) {
    background: var(--provider-primary, #3b82f6) !important;
    border-color: var(--provider-primary, #3b82f6) !important;
}

:deep(.book-btn:hover) {
    background: var(--provider-primary-hover, #2563eb) !important;
    border-color: var(--provider-primary-hover, #2563eb) !important;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--provider-surface, #fff);
    border-radius: 0.75rem;
}

.empty-state i {
    font-size: 3rem;
    color: var(--provider-text-muted, #9ca3af);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0;
    font-size: 1.25rem;
    color: var(--provider-text, #1f2937);
}

@media (max-width: 640px) {
    .service-item {
        flex-direction: column;
        gap: 1rem;
    }

    .service-image {
        width: 100%;
        height: 150px;
    }

    .service-actions {
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
    }
}
</style>

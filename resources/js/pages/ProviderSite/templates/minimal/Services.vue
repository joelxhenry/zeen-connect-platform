<script setup lang="ts">
import MinimalLayout from './components/MinimalLayout.vue';
import Button from 'primevue/button';
import type { ServicesPageProps } from '@/types/providersite';

const props = defineProps<ServicesPageProps>();

const getBookingUrl = (serviceId: number) => `/book?service=${serviceId}`;
</script>

<template>
    <MinimalLayout title="Services">
        <div class="minimal-services">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>Services</h1>
                    <p>{{ provider.services_count }} services available</p>
                </div>

                <!-- Services by Category -->
                <div v-if="servicesByCategory.length > 0" class="categories">
                    <div
                        v-for="categoryGroup in servicesByCategory"
                        :key="categoryGroup.category.id"
                        class="category-group"
                    >
                        <h2 class="category-name">{{ categoryGroup.category.name }}</h2>

                        <div class="service-list">
                            <div
                                v-for="service in categoryGroup.services"
                                :key="service.id"
                                class="service-item"
                            >
                                <div class="service-content">
                                    <h3 class="service-name">{{ service.name }}</h3>
                                    <p v-if="service.description" class="service-description">
                                        {{ service.description }}
                                    </p>
                                    <div class="service-meta">
                                        <span class="service-duration">
                                            <i class="pi pi-clock"></i>
                                            {{ service.duration_display }}
                                        </span>
                                    </div>
                                </div>
                                <div class="service-action">
                                    <span class="service-price">{{ service.price_display }}</span>
                                    <AppLink :href="getBookingUrl(service.id)">
                                        <Button label="Book" size="small" class="book-btn" />
                                    </AppLink>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <i class="pi pi-inbox"></i>
                    <h3>No services available</h3>
                    <p>This provider hasn't added any services yet.</p>
                </div>
            </div>
        </div>
    </MinimalLayout>
</template>

<style scoped>
.minimal-services {
    min-height: 100%;
    background: #fff;
}

.page-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
}

.page-header {
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.page-header h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--provider-text);
}

.page-header p {
    margin: 0;
    color: #6b7280;
}

.categories {
    display: flex;
    flex-direction: column;
    gap: 2.5rem;
}

.category-group {
    display: flex;
    flex-direction: column;
}

.category-name {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-primary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.service-list {
    display: flex;
    flex-direction: column;
}

.service-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
    padding: 1.25rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.service-item:last-child {
    border-bottom: none;
}

.service-content {
    flex: 1;
    min-width: 0;
}

.service-name {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 500;
    color: var(--provider-text);
}

.service-description {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
}

.service-meta {
    display: flex;
    gap: 1rem;
}

.service-duration {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #9ca3af;
}

.service-action {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
    flex-shrink: 0;
}

.service-price {
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text);
}

:deep(.book-btn) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.book-btn:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--provider-text);
}

.empty-state p {
    margin: 0;
}

@media (max-width: 640px) {
    .service-item {
        flex-direction: column;
        gap: 1rem;
    }

    .service-action {
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
    }
}
</style>

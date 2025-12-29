<script setup lang="ts">
import BoutiqueLayout from './components/BoutiqueLayout.vue';
import Button from 'primevue/button';
import type { ServicesPageProps } from '@/types/providersite';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';
import { usePage } from '@inertiajs/vue3';

const props = defineProps<ServicesPageProps>();

const page = usePage();
const __provider = page.props.__provider as { domain: string } | null;

const getBookingUrl = (serviceId?: number) => {
    const url = ProviderSiteBookingController.create({ provider: __provider?.domain ?? '' }).url;
    return serviceId ? `${url}?service=${serviceId}` : url;
};

const totalServices = props.servicesByCategory.reduce((sum, cat) => sum + cat.services.length, 0);
</script>

<template>
    <BoutiqueLayout title="Services">
        <div class="services-page">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-container">
                    <h1>Our Services</h1>
                    <p>{{ totalServices }} services to choose from</p>
                </div>
            </div>

            <!-- Services Content -->
            <div class="services-content">
                <div class="content-container">
                    <!-- Categories -->
                    <div
                        v-for="category in servicesByCategory"
                        :key="category.category.id"
                        class="category-section"
                    >
                        <h2 class="category-title">{{ category.category.name }}</h2>

                        <div class="services-list">
                            <div
                                v-for="service in category.services"
                                :key="service.id"
                                class="service-item"
                            >
                                <div class="service-item__image" v-if="service.display_image">
                                    <img :src="service.display_image" :alt="service.name" />
                                </div>

                                <div class="service-item__content">
                                    <div class="service-item__info">
                                        <h3>{{ service.name }}</h3>
                                        <p v-if="service.description">{{ service.description }}</p>
                                    </div>

                                    <div class="service-item__meta">
                                        <div class="service-pricing">
                                            <span class="service-price">{{ service.price_display }}</span>
                                            <span class="service-duration">{{ service.duration_display }}</span>
                                        </div>
                                        <AppLink :href="getBookingUrl(service.id)">
                                            <Button label="Book" class="btn-book" />
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
                        <p>Check back soon for our service offerings.</p>
                    </div>
                </div>
            </div>
        </div>
    </BoutiqueLayout>
</template>

<style scoped>
.services-page {
    min-height: 100vh;
}

/* Page Header */
.page-header {
    padding: 4rem 2rem;
    text-align: center;
    background: var(--provider-surface, #fff);
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

.header-container {
    max-width: 600px;
    margin: 0 auto;
}

.page-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: clamp(2rem, 4vw, 2.75rem);
    color: var(--provider-text, #3d3d3d);
}

.page-header p {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-secondary, #8a8a8a);
}

/* Services Content */
.services-content {
    padding: 3rem 0 5rem;
}

.content-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Category Section */
.category-section {
    margin-bottom: 3rem;
}

.category-section:last-child {
    margin-bottom: 0;
}

.category-title {
    margin: 0 0 1.5rem 0;
    font-size: 1.375rem;
    color: var(--provider-text, #3d3d3d);
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

/* Services List */
.services-list {
    display: flex;
    flex-direction: column;
}

.service-item {
    display: flex;
    gap: 1.5rem;
    padding: 1.5rem 0;
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

.service-item:last-child {
    border-bottom: none;
}

.service-item__image {
    width: 120px;
    height: 120px;
    flex-shrink: 0;
    border-radius: 0.75rem;
    overflow: hidden;
}

.service-item__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.service-item__content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.service-item__info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--provider-text, #3d3d3d);
}

.service-item__info p {
    margin: 0;
    font-size: 0.9375rem;
    color: var(--provider-secondary, #8a8a8a);
    line-height: 1.7;
}

.service-item__meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
}

.service-pricing {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.service-price {
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 1.5rem;
    font-weight: 500;
    color: var(--provider-text, #3d3d3d);
}

.service-duration {
    font-size: 0.8125rem;
    color: var(--provider-secondary, #8a8a8a);
}

:deep(.btn-book) {
    font-family: var(--font-body, 'Nunito Sans', sans-serif) !important;
    font-weight: 500;
    font-size: 0.875rem;
    letter-spacing: 0.02em;
    background-color: var(--provider-primary, #8b7355) !important;
    border-color: var(--provider-primary, #8b7355) !important;
    border-radius: 2rem !important;
    padding: 0.5rem 1.5rem;
}

:deep(.btn-book:hover) {
    background-color: var(--provider-primary-hover, #6d5a43) !important;
    border-color: var(--provider-primary-hover, #6d5a43) !important;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state i {
    font-size: 3rem;
    color: var(--provider-border, #ebe8e4);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--provider-text, #3d3d3d);
}

.empty-state p {
    margin: 0;
    color: var(--provider-secondary, #8a8a8a);
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        padding: 3rem 1rem;
    }

    .content-container {
        padding: 0 1rem;
    }

    .services-content {
        padding: 2rem 0 4rem;
    }

    .service-item {
        flex-direction: column;
        gap: 1rem;
    }

    .service-item__image {
        width: 100%;
        height: 180px;
    }

    .service-item__meta {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .service-pricing {
        flex-direction: row;
        align-items: baseline;
        gap: 0.75rem;
    }

    :deep(.btn-book) {
        width: 100%;
    }
}
</style>

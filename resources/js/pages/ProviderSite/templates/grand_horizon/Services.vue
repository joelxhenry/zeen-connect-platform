<script setup lang="ts">
import GrandHorizonLayout from './components/GrandHorizonLayout.vue';
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
    <GrandHorizonLayout title="Services">
        <div class="services-page">
            <!-- Page Header -->
            <section class="page-header">
                <div class="header-content">
                    <h4 class="header-label">Our Offerings</h4>
                    <h1>Services & Experiences</h1>
                    <p>{{ totalServices }} curated experiences to choose from</p>
                </div>
            </section>

            <!-- Services Content -->
            <section class="services-content">
                <div class="content-container">
                    <!-- Categories -->
                    <div
                        v-for="category in servicesByCategory"
                        :key="category.category.id"
                        class="category-section"
                    >
                        <div class="category-header">
                            <h2 class="category-title">{{ category.category.name }}</h2>
                            <span class="category-count">{{ category.services.length }} services</span>
                        </div>

                        <div class="services-grid">
                            <div
                                v-for="service in category.services"
                                :key="service.id"
                                class="service-card"
                            >
                                <div class="service-image" v-if="service.display_image">
                                    <img :src="service.display_image" :alt="service.name" />
                                </div>

                                <div class="service-content">
                                    <div class="service-info">
                                        <h3>{{ service.name }}</h3>
                                        <p v-if="service.description" class="service-description">
                                            {{ service.description }}
                                        </p>
                                    </div>

                                    <div class="service-footer">
                                        <div class="service-pricing">
                                            <span class="service-price">{{ service.price_display }}</span>
                                            <span class="service-duration">{{ service.duration_display }}</span>
                                        </div>
                                        <AppLink :href="getBookingUrl(service.id)">
                                            <Button label="Reserve" class="btn-reserve" />
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
                        <p>Check back soon for our offerings.</p>
                    </div>
                </div>
            </section>
        </div>
    </GrandHorizonLayout>
</template>

<style scoped>
.services-page {
    min-height: 100vh;
}

/* Page Header */
.page-header {
    padding: 6rem 2rem;
    text-align: center;
    background: var(--provider-dark, #1a1a1a);
}

.header-content {
    max-width: 700px;
    margin: 0 auto;
}

.header-label {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--provider-primary, #c9a87c);
    margin-bottom: 1rem;
}

.page-header h1 {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 500;
    color: #ffffff;
    margin: 0 0 1rem 0;
}

.page-header p {
    margin: 0;
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.7);
}

/* Services Content */
.services-content {
    padding: 5rem 0;
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Category Section */
.category-section {
    margin-bottom: 4rem;
}

.category-section:last-child {
    margin-bottom: 0;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--provider-dark, #1a1a1a);
}

.category-title {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.75rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    margin: 0;
}

.category-count {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--provider-secondary, #6a6a6a);
}

/* Services Grid */
.services-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

.service-card {
    display: flex;
    gap: 1.5rem;
    padding: 1.5rem;
    background: var(--provider-surface, #ffffff);
    border: 1px solid var(--provider-border, #e5e0d8);
    transition: box-shadow 0.3s ease;
}

.service-card:hover {
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
}

.service-image {
    width: 160px;
    height: 160px;
    flex-shrink: 0;
    overflow: hidden;
}

.service-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.service-card:hover .service-image img {
    transform: scale(1.05);
}

.service-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.service-info h3 {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.375rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    margin: 0 0 0.75rem 0;
}

.service-description {
    font-size: 0.9375rem;
    color: var(--provider-secondary, #6a6a6a);
    line-height: 1.7;
    margin: 0;
}

.service-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--provider-border, #e5e0d8);
}

.service-pricing {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.service-price {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.5rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
}

.service-duration {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--provider-secondary, #6a6a6a);
}

:deep(.btn-reserve) {
    font-family: var(--font-body, 'Montserrat', sans-serif) !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    background-color: var(--provider-dark, #1a1a1a) !important;
    border-color: var(--provider-dark, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 0.75rem 1.5rem;
}

:deep(.btn-reserve:hover) {
    background-color: var(--provider-primary, #c9a87c) !important;
    border-color: var(--provider-primary, #c9a87c) !important;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 5rem 2rem;
}

.empty-state i {
    font-size: 3.5rem;
    color: var(--provider-border, #e5e0d8);
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.5rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    color: var(--provider-secondary, #6a6a6a);
    margin: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .page-header {
        padding: 4rem 1.5rem;
    }

    .content-container {
        padding: 0 1.5rem;
    }

    .services-content {
        padding: 3rem 0;
    }

    .category-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .service-card {
        flex-direction: column;
        gap: 1rem;
        padding: 0;
        border: none;
        border-bottom: 1px solid var(--provider-border, #e5e0d8);
        padding-bottom: 1.5rem;
    }

    .service-card:hover {
        box-shadow: none;
    }

    .service-image {
        width: 100%;
        height: 200px;
    }

    .service-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .service-pricing {
        flex-direction: row;
        align-items: baseline;
        gap: 1rem;
    }

    :deep(.btn-reserve) {
        width: 100%;
        justify-content: center;
    }
}
</style>

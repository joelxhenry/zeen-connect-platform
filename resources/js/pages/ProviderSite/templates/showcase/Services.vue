<script setup lang="ts">
import ShowcaseLayout from './components/ShowcaseLayout.vue';
import StickyBookingWidget from './components/StickyBookingWidget.vue';
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

const lowestPrice = props.servicesByCategory
    .flatMap(cat => cat.services)
    .reduce((min, s) => Math.min(min, s.price), Infinity);

const startingPriceDisplay = lowestPrice !== Infinity
    ? `$${lowestPrice.toFixed(0)}`
    : undefined;

const totalServices = props.servicesByCategory.reduce((sum, cat) => sum + cat.services.length, 0);
</script>

<template>
    <ShowcaseLayout title="Services">
        <div class="services-page">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-container">
                    <span class="header-label">WHAT WE OFFER</span>
                    <h1>OUR SERVICES</h1>
                    <p class="header-count">{{ totalServices }} services available</p>
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
                        <div class="category-header">
                            <h2>{{ category.category.name }}</h2>
                            <span class="category-count">{{ category.services.length }} services</span>
                        </div>

                        <div class="services-grid">
                            <div
                                v-for="service in category.services"
                                :key="service.id"
                                class="service-card"
                            >
                                <!-- Service Image -->
                                <div class="service-card__image" v-if="service.display_image">
                                    <img :src="service.display_image" :alt="service.name" />
                                </div>

                                <!-- Service Body -->
                                <div class="service-card__body">
                                    <div class="service-card__info">
                                        <h3 class="service-name">{{ service.name }}</h3>
                                        <p v-if="service.description" class="service-description">
                                            {{ service.description }}
                                        </p>
                                    </div>

                                    <div class="service-card__footer">
                                        <div class="service-meta">
                                            <span class="service-price">{{ service.price_display }}</span>
                                            <span class="service-duration">
                                                <i class="pi pi-clock"></i>
                                                {{ service.duration_display }}
                                            </span>
                                        </div>
                                        <AppLink :href="getBookingUrl(service.id)" class="service-book-link">
                                            <Button label="BOOK NOW" class="btn-book" />
                                        </AppLink>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="servicesByCategory.length === 0" class="empty-state">
                        <i class="pi pi-inbox"></i>
                        <h3>NO SERVICES AVAILABLE</h3>
                        <p>Services coming soon. Check back later!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Booking Widget -->
        <StickyBookingWidget
            :bookingUrl="getBookingUrl()"
            :startingPrice="startingPriceDisplay"
        />
    </ShowcaseLayout>
</template>

<style scoped>
.services-page {
    min-height: 100vh;
    background: var(--provider-background, #fafafa);
}

/* Page Header */
.page-header {
    background: var(--provider-surface, #fff);
    padding: 4rem 0;
    border-bottom: 2px solid var(--provider-text, #1a1a1a);
}

.header-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 2rem;
}

.header-label {
    display: block;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--provider-secondary, #6b7280);
    letter-spacing: 0.15em;
    margin-bottom: 0.75rem;
}

.page-header h1 {
    margin: 0 0 0.75rem 0;
    font-size: clamp(2.5rem, 6vw, 4rem);
    color: var(--provider-text, #1a1a1a);
}

.header-count {
    margin: 0;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    letter-spacing: 0.02em;
}

/* Services Content */
.services-content {
    padding: 3rem 0 5rem;
}

.content-container {
    max-width: 1280px;
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

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--provider-border, #e5e5e5);
}

.category-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: var(--provider-text, #1a1a1a);
}

.category-count {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
    letter-spacing: 0.05em;
}

/* Services Grid */
.services-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

/* Service Card */
.service-card {
    display: flex;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #e5e5e5);
    overflow: hidden;
    transition: box-shadow 0.3s, border-color 0.3s;
}

.service-card:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border-color: var(--provider-primary, #1a1a1a);
}

.service-card__image {
    width: 200px;
    flex-shrink: 0;
    overflow: hidden;
}

.service-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.service-card:hover .service-card__image img {
    transform: scale(1.05);
}

.service-card__body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 1.5rem;
}

.service-card__info {
    flex: 1;
}

.service-name {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    color: var(--provider-text, #1a1a1a);
}

.service-description {
    margin: 0;
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.service-card__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--provider-border, #e5e5e5);
}

.service-meta {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.service-price {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.02em;
}

.service-duration {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
}

.service-duration i {
    font-size: 0.625rem;
}

.service-book-link {
    text-decoration: none;
}

:deep(.btn-book) {
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.625rem;
    letter-spacing: 0.1em;
    background-color: var(--provider-primary, #1a1a1a) !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 0.75rem 1.5rem;
}

:deep(.btn-book:hover) {
    background-color: var(--provider-primary-hover, #333) !important;
    border-color: var(--provider-primary-hover, #333) !important;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #e5e5e5);
}

.empty-state i {
    font-size: 3rem;
    color: var(--provider-secondary, #9ca3af);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.05em;
}

.empty-state p {
    margin: 0;
    color: var(--provider-secondary, #6b7280);
}

/* Responsive */
@media (max-width: 1024px) {
    .services-grid {
        grid-template-columns: 1fr;
    }

    .service-card {
        flex-direction: column;
    }

    .service-card__image {
        width: 100%;
        height: 200px;
    }
}

@media (max-width: 768px) {
    .page-header {
        padding: 3rem 0;
    }

    .header-container {
        padding: 0 1rem;
    }

    .content-container {
        padding: 0 1rem;
    }

    .services-content {
        padding: 2rem 0 6rem;
    }

    .service-card__body {
        padding: 1.25rem;
    }

    .service-card__footer {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .service-meta {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }

    :deep(.btn-book) {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .category-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

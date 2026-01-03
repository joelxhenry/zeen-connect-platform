<script setup lang="ts">
import ArchitectBoldLayout from './components/ArchitectBoldLayout.vue';
import Button from 'primevue/button';
import type { ServicesPageProps } from '@/types/providersite';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';

const props = defineProps<ServicesPageProps>();

const getServiceBookingUrl = (serviceId: number) => {
    return ProviderSiteBookingController.create({
        provider: props.provider.domain,
        service: serviceId,
    }).url;
};
</script>

<template>
    <ArchitectBoldLayout title="Services">
        <div class="services-page">
            <!-- Header -->
            <div class="page-header">
                <div class="header-container">
                    <h1>OUR SERVICES</h1>
                    <p>{{ provider.tagline || 'Explore our comprehensive range of professional services' }}</p>
                </div>
            </div>

            <!-- Services by Category -->
            <div class="services-content">
                <div class="content-container">
                    <div
                        v-for="(categoryGroup, index) in servicesByCategory"
                        :key="categoryGroup.category?.id ?? `uncategorized-${index}`"
                        class="category-section"
                    >
                        <div class="category-header">
                            <h2>{{ categoryGroup.category?.name ?? 'Other Services' }}</h2>
                            <span class="service-count">{{ categoryGroup.services.length }} services</span>
                        </div>

                        <div class="services-grid">
                            <div
                                v-for="service in categoryGroup.services"
                                :key="service.id"
                                class="service-card"
                            >
                                <div class="service-info">
                                    <h3>{{ service.name }}</h3>
                                    <p v-if="service.description" class="service-desc">{{ service.description }}</p>
                                    <div class="service-meta">
                                        <span class="service-duration">
                                            <i class="pi pi-clock"></i>
                                            {{ service.duration_display }}
                                        </span>
                                    </div>
                                </div>
                                <div class="service-action">
                                    <span class="service-price">{{ service.price_display }}</span>
                                    <AppLink :href="getServiceBookingUrl(service.id)">
                                        <Button label="BOOK" size="small" class="book-btn" />
                                    </AppLink>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="servicesByCategory.length === 0" class="empty-state">
                        <i class="pi pi-briefcase"></i>
                        <h3>NO SERVICES AVAILABLE</h3>
                        <p>Check back soon for our service offerings.</p>
                    </div>
                </div>
            </div>
        </div>
    </ArchitectBoldLayout>
</template>

<style scoped>
.services-page {
    min-height: 100vh;
    background: var(--provider-background, #f5f5f5);
}

/* Header */
.page-header {
    background: var(--provider-surface, #fff);
    padding: 3rem 0;
    border-bottom: 2px solid var(--provider-text, #1a1a1a);
}

.header-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    text-align: center;
}

.page-header h1 {
    margin: 0 0 0.75rem 0;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.05em;
}

.page-header p {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-secondary, #6b7280);
    max-width: 600px;
    margin: 0 auto;
}

/* Content */
.services-content {
    padding: 3rem 0 4rem;
}

.content-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
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
    align-items: baseline;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--provider-border, #e5e5e5);
}

.category-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.service-count {
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Services Grid */
.services-grid {
    display: flex;
    flex-direction: column;
    gap: 1px;
    background: var(--provider-border, #e5e5e5);
    border: 1px solid var(--provider-border, #e5e5e5);
}

.service-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: var(--provider-surface, #fff);
    gap: 2rem;
}

.service-info {
    flex: 1;
    min-width: 0;
}

.service-info h3 {
    margin: 0 0 0.375rem 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.service-desc {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.service-meta {
    display: flex;
    gap: 1rem;
}

.service-duration {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
}

.service-duration i {
    font-size: 0.875rem;
}

.service-action {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-shrink: 0;
}

.service-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
}

:deep(.book-btn) {
    background-color: var(--provider-primary, #1a1a1a) !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
    font-weight: 600;
    letter-spacing: 0.1em;
}

:deep(.book-btn:hover) {
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
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }

    .service-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .service-action {
        width: 100%;
        justify-content: space-between;
    }

    .category-header {
        flex-direction: column;
        gap: 0.25rem;
        align-items: flex-start;
    }
}
</style>

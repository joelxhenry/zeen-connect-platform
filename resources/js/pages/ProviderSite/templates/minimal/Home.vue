<script setup lang="ts">
import MinimalLayout from './components/MinimalLayout.vue';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Rating from 'primevue/rating';
import type { HomePageProps } from '@/types/providersite';
import { useProviderSiteHome } from '@/composables/providersite';

const props = defineProps<HomePageProps>();

const {
    stats,
    bookingUrl,
    servicesUrl,
    reviewsUrl,
    getServiceBookingUrl,
    hasServices,
    hasReviews,
} = useProviderSiteHome(props);

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <MinimalLayout title="Home">
        <div class="minimal-home">
            <!-- Clean Hero Section -->
            <section class="hero-section">
                <div class="hero-container">
                    <Avatar
                        v-if="provider.avatar"
                        :image="provider.avatar"
                        shape="circle"
                        class="hero-avatar"
                    />
                    <Avatar
                        v-else
                        :label="getInitials(provider.business_name)"
                        shape="circle"
                        class="hero-avatar avatar-fallback"
                    />

                    <h1 class="business-name">{{ provider.business_name }}</h1>
                    <p v-if="provider.tagline" class="tagline">{{ provider.tagline }}</p>

                    <div v-if="stats.reviewCount > 0" class="rating-badge">
                        <Rating :modelValue="stats.rating" readonly :cancel="false" />
                        <span class="rating-text">{{ provider.rating_display }} ({{ stats.reviewCount }})</span>
                    </div>

                    <AppLink :href="bookingUrl" class="cta-link">
                        <Button label="Book Now" icon="pi pi-calendar" size="large" class="cta-button" />
                    </AppLink>
                </div>
            </section>

            <!-- About Section (Simple) -->
            <section v-if="provider.bio" class="content-section">
                <div class="section-container">
                    <p class="bio-text">{{ provider.bio }}</p>
                </div>
            </section>

            <!-- Services Section (Simple List) -->
            <section v-if="hasServices" class="content-section services-section">
                <div class="section-container">
                    <div class="section-header">
                        <h2>Services</h2>
                        <AppLink :href="servicesUrl" class="view-all-link">
                            View all
                        </AppLink>
                    </div>

                    <div class="services-list">
                        <template v-for="(categoryGroup, index) in servicesByCategory" :key="categoryGroup.category?.id ?? `uncategorized-${index}`">
                            <AppLink
                                v-for="service in categoryGroup.services"
                                :key="service.id"
                                :href="getServiceBookingUrl(service.id)"
                                class="service-row"
                            >
                                <div class="service-info">
                                    <span class="service-name">{{ service.name }}</span>
                                    <span class="service-duration">{{ service.duration_display }}</span>
                                </div>
                                <span class="service-price">{{ service.price_display }}</span>
                            </AppLink>
                        </template>
                    </div>
                </div>
            </section>

            <!-- Reviews Section (Compact) -->
            <section v-if="hasReviews" class="content-section">
                <div class="section-container">
                    <div class="section-header">
                        <h2>Reviews</h2>
                        <AppLink :href="reviewsUrl" class="view-all-link">
                            View all
                        </AppLink>
                    </div>

                    <div class="reviews-compact">
                        <div
                            v-for="review in reviews.slice(0, 3)"
                            :key="review.id"
                            class="review-item"
                        >
                            <div class="review-header">
                                <Rating :modelValue="review.rating" readonly :cancel="false" class="review-rating" />
                                <span class="review-date">{{ review.time_ago }}</span>
                            </div>
                            <p v-if="review.comment" class="review-comment">{{ review.comment }}</p>
                            <span class="review-author">— {{ review.client.name }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Contact Section -->
            <section class="content-section contact-section">
                <div class="section-container">
                    <div v-if="provider.address" class="contact-item">
                        <i class="pi pi-map-marker"></i>
                        <span>{{ provider.address }}</span>
                    </div>
                    <div v-if="provider.website" class="contact-item">
                        <i class="pi pi-globe"></i>
                        <a :href="provider.website" target="_blank" rel="noopener">{{ provider.website }}</a>
                    </div>
                </div>
            </section>

            <!-- Simple CTA Footer -->
            <section class="cta-section">
                <div class="section-container text-center">
                    <AppLink :href="bookingUrl">
                        <Button label="Book an Appointment" size="large" class="cta-button" />
                    </AppLink>
                </div>
            </section>
        </div>
    </MinimalLayout>
</template>

<style scoped>
.minimal-home {
    min-height: 100%;
    background: #fff;
}

.hero-section {
    padding: 4rem 1.5rem 3rem;
    text-align: center;
    border-bottom: 1px solid #f3f4f6;
}

.hero-container {
    max-width: 600px;
    margin: 0 auto;
}

:deep(.hero-avatar) {
    width: 6rem !important;
    height: 6rem !important;
    font-size: 1.5rem !important;
    margin-bottom: 1.5rem;
}

:deep(.avatar-fallback) {
    background-color: var(--provider-primary) !important;
    color: white !important;
}

.business-name {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 600;
    color: var(--provider-text);
    letter-spacing: -0.025em;
}

.tagline {
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    color: #6b7280;
}

.rating-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.rating-text {
    font-size: 0.875rem;
    color: #6b7280;
}

.cta-link {
    text-decoration: none;
}

:deep(.cta-button) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.cta-button:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.content-section {
    padding: 3rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.section-container {
    max-width: 600px;
    margin: 0 auto;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text);
}

.view-all-link {
    font-size: 0.875rem;
    color: var(--provider-primary);
    text-decoration: none;
}

.view-all-link:hover {
    text-decoration: underline;
}

.bio-text {
    margin: 0;
    font-size: 1rem;
    line-height: 1.75;
    color: #4b5563;
}

.services-section {
    background: #fafafa;
}

.services-list {
    display: flex;
    flex-direction: column;
}

.service-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #e5e7eb;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.15s;
}

.service-row:last-child {
    border-bottom: none;
}

.service-row:hover {
    background-color: rgba(0, 0, 0, 0.02);
    margin: 0 -0.5rem;
    padding: 1rem 0.5rem;
    border-radius: 0.25rem;
}

.service-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.service-name {
    font-weight: 500;
    color: var(--provider-text);
}

.service-duration {
    font-size: 0.75rem;
    color: #9ca3af;
}

.service-price {
    font-weight: 600;
    color: var(--provider-primary);
}

.reviews-compact {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.review-item {
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.review-item:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

.review-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

:deep(.review-rating) {
    font-size: 0.875rem;
}

.review-date {
    font-size: 0.75rem;
    color: #9ca3af;
}

.review-comment {
    margin: 0 0 0.5rem 0;
    font-size: 0.9375rem;
    color: #4b5563;
    line-height: 1.6;
}

.review-author {
    font-size: 0.875rem;
    color: #9ca3af;
}

.contact-section {
    background: #fafafa;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    color: #6b7280;
}

.contact-item i {
    color: var(--provider-primary);
    font-size: 1rem;
}

.contact-item a {
    color: var(--provider-primary);
    text-decoration: none;
}

.contact-item a:hover {
    text-decoration: underline;
}

.cta-section {
    padding: 4rem 1.5rem;
    border-bottom: none;
}

.text-center {
    text-align: center;
}
</style>

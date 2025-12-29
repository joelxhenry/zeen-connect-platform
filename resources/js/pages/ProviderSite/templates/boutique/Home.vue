<script setup lang="ts">
import BoutiqueLayout from './components/BoutiqueLayout.vue';
import RoundedGallery from './components/RoundedGallery.vue';
import MinimalReviewCard from './components/MinimalReviewCard.vue';
import Button from 'primevue/button';
import Rating from 'primevue/rating';
import type { HomePageProps } from '@/types/providersite';
import { useProviderSiteHome } from '@/composables/providersite';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';
import { usePage } from '@inertiajs/vue3';
import site from '@/routes/providersite';

const props = defineProps<HomePageProps>();

const page = usePage();
const __provider = page.props.__provider as { domain: string } | null;

const { stats, bookingUrl, servicesUrl, reviewsUrl, getServiceBookingUrl, hasPortfolio, hasServices, hasReviews } = useProviderSiteHome(props);

const featuredServices = props.servicesByCategory
    .flatMap(cat => cat.services)
    .slice(0, 4);

const featuredReviews = props.reviews.slice(0, 3);
</script>

<template>
    <BoutiqueLayout>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-container">
                <!-- Centered Tagline -->
                <div class="hero-tagline">
                    <h1>{{ provider.tagline || provider.business_name }}</h1>
                    <p v-if="provider.bio" class="hero-bio">{{ provider.bio }}</p>
                </div>

                <!-- Framed Cover Image -->
                <div class="hero-image-frame">
                    <div class="image-wrapper">
                        <img
                            v-if="provider.cover_image"
                            :src="provider.cover_image"
                            :alt="provider.business_name"
                            class="hero-image"
                        />
                        <div v-else class="hero-placeholder">
                            <div class="placeholder-pattern"></div>
                        </div>
                    </div>
                </div>

                <!-- Stats & CTA -->
                <div class="hero-footer">
                    <div class="hero-stats" v-if="stats.reviewCount > 0">
                        <Rating :modelValue="stats.rating" readonly :cancel="false" class="hero-rating" />
                        <span class="stats-text">{{ stats.reviewCount }} happy clients</span>
                    </div>
                    <AppLink :href="bookingUrl">
                        <Button label="Book an Appointment" class="btn-primary" />
                    </AppLink>
                </div>
            </div>
        </section>

        <!-- Services Preview -->
        <section v-if="hasServices" class="services-section">
            <div class="section-container">
                <div class="section-header">
                    <h2>Our Services</h2>
                    <AppLink :href="servicesUrl" class="view-all-link">
                        View all services
                    </AppLink>
                </div>
                <div class="services-grid">
                    <div
                        v-for="service in featuredServices"
                        :key="service.id"
                        class="service-card"
                    >
                        <div class="service-image" v-if="service.display_image">
                            <img :src="service.display_image" :alt="service.name" />
                        </div>
                        <div class="service-content">
                            <h3>{{ service.name }}</h3>
                            <p v-if="service.description" class="service-description">
                                {{ service.description.substring(0, 80) }}{{ service.description.length > 80 ? '...' : '' }}
                            </p>
                            <div class="service-footer">
                                <span class="service-price">{{ service.price_display }}</span>
                                <span class="service-duration">{{ service.duration_display }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gallery -->
        <section v-if="hasPortfolio" class="gallery-section">
            <div class="section-container">
                <div class="section-header section-header--centered">
                    <h2>Our Work</h2>
                    <p class="section-subtitle">A glimpse into what we create</p>
                </div>
                <RoundedGallery
                    :images="provider.gallery?.slice(0, 6)"
                    :videos="provider.videos?.slice(0, 3)"
                    :columns="3"
                />
            </div>
        </section>

        <!-- Reviews -->
        <section v-if="hasReviews && featuredReviews.length > 0" class="reviews-section">
            <div class="section-container">
                <div class="section-header">
                    <h2>Client Experiences</h2>
                    <AppLink :href="reviewsUrl" class="view-all-link">
                        Read all reviews
                    </AppLink>
                </div>
                <div class="reviews-list">
                    <MinimalReviewCard
                        v-for="(review, index) in featuredReviews"
                        :key="review.id"
                        :review="review"
                        :showDivider="index < featuredReviews.length - 1"
                    />
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="cta-container">
                <h2>Ready to experience the difference?</h2>
                <p>Book your appointment today and let us take care of you.</p>
                <AppLink :href="bookingUrl">
                    <Button label="Book Now" class="btn-primary" />
                </AppLink>
            </div>
        </section>
    </BoutiqueLayout>
</template>

<style scoped>
/* Hero Section */
.hero-section {
    padding: 5rem 2rem 4rem;
    text-align: center;
}

.hero-container {
    max-width: 800px;
    margin: 0 auto;
}

.hero-tagline h1 {
    margin: 0 0 1rem 0;
    font-size: clamp(2.25rem, 5vw, 3.5rem);
    color: var(--provider-text, #3d3d3d);
    line-height: 1.2;
}

.hero-bio {
    max-width: 600px;
    margin: 0 auto 2.5rem;
    font-size: 1.0625rem;
    color: var(--provider-secondary, #8a8a8a);
    line-height: 1.8;
}

/* Framed Cover Image */
.hero-image-frame {
    max-width: 500px;
    margin: 0 auto 2.5rem;
    padding: 1.5rem;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #ebe8e4);
    border-radius: 1.5rem;
}

.image-wrapper {
    overflow: hidden;
    border-radius: 1rem;
}

.hero-image {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    display: block;
}

.hero-placeholder {
    width: 100%;
    aspect-ratio: 4 / 3;
    background: linear-gradient(135deg, #f8f6f4 0%, #ebe8e4 100%);
    position: relative;
}

.placeholder-pattern {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 2px 2px, rgba(0,0,0,0.03) 1px, transparent 0);
    background-size: 24px 24px;
}

/* Hero Footer */
.hero-footer {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
}

.hero-stats {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

:deep(.hero-rating .p-rating-icon) {
    font-size: 0.875rem;
    color: var(--provider-border, #ebe8e4);
}

:deep(.hero-rating .p-rating-icon.p-rating-icon-active) {
    color: var(--provider-warning, #e5b567);
}

.stats-text {
    font-size: 0.9375rem;
    color: var(--provider-secondary, #8a8a8a);
}

:deep(.btn-primary) {
    font-family: var(--font-body, 'Nunito Sans', sans-serif) !important;
    font-weight: 500;
    font-size: 0.9375rem;
    letter-spacing: 0.02em;
    background-color: var(--provider-primary, #8b7355) !important;
    border-color: var(--provider-primary, #8b7355) !important;
    border-radius: 2rem !important;
    padding: 0.75rem 2rem;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover, #6d5a43) !important;
    border-color: var(--provider-primary-hover, #6d5a43) !important;
}

/* Section Styles */
.section-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 2rem;
}

.section-header--centered {
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.section-header h2 {
    margin: 0;
    font-size: 1.75rem;
    color: var(--provider-text, #3d3d3d);
}

.section-subtitle {
    margin: 0.5rem 0 0;
    font-size: 1rem;
    color: var(--provider-secondary, #8a8a8a);
}

.view-all-link {
    color: var(--provider-primary, #8b7355);
    font-size: 0.9375rem;
    font-weight: 400;
    text-decoration: none;
    transition: opacity 0.2s;
}

.view-all-link:hover {
    opacity: 0.7;
}

/* Services Section */
.services-section {
    padding: 4rem 0;
    background: var(--provider-surface, #fff);
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.service-card {
    display: flex;
    gap: 1.25rem;
    padding: 1.25rem;
    background: var(--provider-background, #fdfcfb);
    border-radius: 1rem;
    transition: box-shadow 0.3s;
}

.service-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}

.service-image {
    width: 100px;
    height: 100px;
    flex-shrink: 0;
    border-radius: 0.75rem;
    overflow: hidden;
}

.service-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.service-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.service-content h3 {
    margin: 0 0 0.375rem 0;
    font-size: 1.125rem;
    color: var(--provider-text, #3d3d3d);
}

.service-description {
    margin: 0 0 auto 0;
    font-size: 0.8125rem;
    color: var(--provider-secondary, #8a8a8a);
    line-height: 1.6;
}

.service-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--provider-border, #ebe8e4);
}

.service-price {
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 1.25rem;
    font-weight: 500;
    color: var(--provider-text, #3d3d3d);
}

.service-duration {
    font-size: 0.75rem;
    color: var(--provider-secondary, #8a8a8a);
}

/* Gallery Section */
.gallery-section {
    padding: 4rem 0;
}

/* Reviews Section */
.reviews-section {
    padding: 4rem 0;
    background: var(--provider-surface, #fff);
}

.reviews-list {
    max-width: 700px;
}

/* CTA Section */
.cta-section {
    padding: 5rem 2rem;
    text-align: center;
    background: var(--provider-background, #fdfcfb);
}

.cta-container {
    max-width: 500px;
    margin: 0 auto;
}

.cta-section h2 {
    margin: 0 0 0.75rem 0;
    font-size: 1.75rem;
    color: var(--provider-text, #3d3d3d);
}

.cta-section p {
    margin: 0 0 2rem 0;
    font-size: 1rem;
    color: var(--provider-secondary, #8a8a8a);
}

/* Responsive */
@media (max-width: 768px) {
    .hero-section {
        padding: 3rem 1rem 3rem;
    }

    .hero-image-frame {
        padding: 1rem;
        margin-bottom: 2rem;
    }

    .section-container {
        padding: 0 1rem;
    }

    .services-grid {
        grid-template-columns: 1fr;
    }

    .service-card {
        flex-direction: column;
    }

    .service-image {
        width: 100%;
        height: 150px;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .services-section,
    .gallery-section,
    .reviews-section {
        padding: 3rem 0;
    }

    .cta-section {
        padding: 3rem 1rem;
    }
}
</style>

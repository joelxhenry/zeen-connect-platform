<script setup lang="ts">
import ShowcaseLayout from './components/ShowcaseLayout.vue';
import HighlightGallery from './components/HighlightGallery.vue';
import StickyBookingWidget from './components/StickyBookingWidget.vue';
import BubbleReviewCard from './components/BubbleReviewCard.vue';
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
    .slice(0, 3);

const featuredReviews = props.reviews.slice(0, 2);

const lowestPrice = featuredServices.length > 0
    ? Math.min(...featuredServices.map(s => s.price))
    : null;

const startingPriceDisplay = lowestPrice !== null
    ? `$${lowestPrice.toFixed(0)}`
    : undefined;
</script>

<template>
    <ShowcaseLayout transparentHeader>
        <!-- Hero Cover Section - 70% viewport -->
        <section class="hero-cover">
            <div class="hero-cover__image">
                <img
                    v-if="provider.cover_image"
                    :src="provider.cover_image"
                    :alt="provider.business_name"
                    class="cover-image"
                />
                <div v-else class="cover-placeholder">
                    <div class="cover-pattern"></div>
                </div>
                <div class="cover-overlay"></div>
            </div>

            <!-- Hero Content -->
            <div class="hero-cover__content">
                <div class="hero-badge" v-if="provider.verified_at">
                    <i class="pi pi-verified"></i>
                    <span>VERIFIED</span>
                </div>
                <div class="hero-stats" v-if="stats.reviewCount > 0">
                    <Rating :modelValue="stats.rating" readonly :cancel="false" class="hero-rating" />
                    <span class="hero-rating-text">{{ stats.reviewCount }} reviews</span>
                </div>
            </div>
        </section>

        <!-- Overlapping Tagline Section -->
        <section class="tagline-section">
            <div class="tagline-container">
                <h1 class="tagline-text">{{ provider.tagline || provider.business_name }}</h1>
                <p v-if="provider.bio" class="tagline-bio">{{ provider.bio }}</p>
                <div class="tagline-actions">
                    <AppLink :href="bookingUrl">
                        <Button label="BOOK NOW" class="btn-primary" />
                    </AppLink>
                    <AppLink :href="servicesUrl">
                        <Button label="VIEW SERVICES" severity="secondary" outlined class="btn-secondary" />
                    </AppLink>
                </div>
            </div>
        </section>

        <!-- Highlight Gallery -->
        <section v-if="hasPortfolio" class="gallery-section">
            <div class="section-container">
                <div class="section-header">
                    <h2>OUR WORK</h2>
                    <span class="section-count">{{ (provider.gallery?.length || 0) + (provider.videos?.length || 0) }} items</span>
                </div>
                <HighlightGallery
                    :images="provider.gallery"
                    :videos="provider.videos"
                    :maxDisplay="5"
                />
            </div>
        </section>

        <!-- Featured Services -->
        <section v-if="hasServices" class="services-section">
            <div class="section-container">
                <div class="section-header">
                    <h2>SERVICES</h2>
                    <AppLink :href="servicesUrl" class="view-all-link">
                        View all <i class="pi pi-arrow-right"></i>
                    </AppLink>
                </div>
                <div class="services-grid">
                    <div
                        v-for="service in featuredServices"
                        :key="service.id"
                        class="service-card"
                    >
                        <div class="service-card__image" v-if="service.display_image">
                            <img :src="service.display_image" :alt="service.name" />
                        </div>
                        <div class="service-card__body">
                            <h3 class="service-name">{{ service.name }}</h3>
                            <p v-if="service.description" class="service-description">
                                {{ service.description.substring(0, 100) }}{{ service.description.length > 100 ? '...' : '' }}
                            </p>
                            <div class="service-meta">
                                <span class="service-price">{{ service.price_display }}</span>
                                <span class="service-duration">{{ service.duration_display }}</span>
                            </div>
                            <AppLink :href="getServiceBookingUrl(service.id)" class="service-book-link">
                                <Button label="BOOK" size="small" class="btn-book" />
                            </AppLink>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Reviews -->
        <section v-if="hasReviews && featuredReviews.length > 0" class="reviews-section">
            <div class="section-container">
                <div class="section-header">
                    <h2>WHAT CLIENTS SAY</h2>
                    <AppLink :href="reviewsUrl" class="view-all-link">
                        All {{ stats.reviewCount }} reviews <i class="pi pi-arrow-right"></i>
                    </AppLink>
                </div>
                <div class="reviews-grid">
                    <BubbleReviewCard
                        v-for="review in featuredReviews"
                        :key="review.id"
                        :review="review"
                    />
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="cta-section">
            <div class="cta-container">
                <h2>READY TO BOOK?</h2>
                <p>Experience the difference. Book your appointment today.</p>
                <AppLink :href="bookingUrl">
                    <Button label="BOOK YOUR EXPERIENCE" class="btn-cta" />
                </AppLink>
            </div>
        </section>

        <!-- Sticky Booking Widget -->
        <StickyBookingWidget
            :bookingUrl="bookingUrl"
            :startingPrice="startingPriceDisplay"
        />
    </ShowcaseLayout>
</template>

<style scoped>
/* Hero Cover - 70% viewport height */
.hero-cover {
    position: relative;
    height: 70vh;
    min-height: 500px;
    max-height: 900px;
    overflow: hidden;
}

.hero-cover__image {
    position: absolute;
    inset: 0;
}

.cover-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cover-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #27272a 0%, #18181b 100%);
    position: relative;
}

.cover-pattern {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(30deg, transparent 40%, rgba(255,255,255,0.02) 40%, rgba(255,255,255,0.02) 60%, transparent 60%),
        linear-gradient(-30deg, transparent 40%, rgba(255,255,255,0.02) 40%, rgba(255,255,255,0.02) 60%, transparent 60%);
    background-size: 60px 60px;
}

.cover-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(0, 0, 0, 0.3) 0%,
        rgba(0, 0, 0, 0.1) 40%,
        rgba(0, 0, 0, 0.4) 100%
    );
}

.hero-cover__content {
    position: absolute;
    bottom: 2rem;
    left: 2rem;
    right: 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    z-index: 2;
}

.hero-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    padding: 0.5rem 1rem;
    color: white;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
}

.hero-badge i {
    color: var(--provider-success, #22C55E);
}

.hero-stats {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

:deep(.hero-rating .p-rating-icon) {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.875rem;
}

:deep(.hero-rating .p-rating-icon.p-rating-icon-active) {
    color: var(--provider-warning, #f59e0b);
}

.hero-rating-text {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.875rem;
}

/* Overlapping Tagline Section */
.tagline-section {
    position: relative;
    z-index: 10;
    margin-top: -5rem;
    padding: 0 2rem;
}

.tagline-container {
    max-width: 1000px;
    margin: 0 auto;
    background: var(--provider-surface, #fff);
    padding: 3rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.tagline-text {
    margin: 0 0 1rem 0;
    font-size: clamp(2.5rem, 6vw, 4.5rem);
    color: var(--provider-text, #1a1a1a);
    line-height: 1;
}

.tagline-bio {
    margin: 0 0 2rem 0;
    font-size: 1.125rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.7;
    max-width: 600px;
}

.tagline-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

:deep(.btn-primary) {
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    background-color: var(--provider-primary, #1a1a1a) !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 1rem 2rem;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover, #333) !important;
    border-color: var(--provider-primary-hover, #333) !important;
}

:deep(.btn-secondary) {
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    border-color: var(--provider-primary, #1a1a1a) !important;
    color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 1rem 2rem;
}

:deep(.btn-secondary:hover) {
    background-color: var(--provider-primary-10, rgba(26, 26, 26, 0.1)) !important;
}

/* Section Styles */
.section-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.section-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--provider-text, #1a1a1a);
}

.section-count {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
    letter-spacing: 0.05em;
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--provider-text, #1a1a1a);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    transition: opacity 0.2s;
}

.view-all-link:hover {
    opacity: 0.7;
}

.view-all-link i {
    font-size: 0.75rem;
}

/* Gallery Section */
.gallery-section {
    padding: 4rem 0;
    background: var(--provider-background, #fafafa);
}

/* Services Section */
.services-section {
    padding: 4rem 0;
    background: var(--provider-surface, #fff);
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.service-card {
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #e5e5e5);
    overflow: hidden;
    transition: box-shadow 0.3s;
}

.service-card:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.service-card__image {
    aspect-ratio: 16 / 10;
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
    padding: 1.5rem;
}

.service-name {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    color: var(--provider-text, #1a1a1a);
}

.service-description {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.6;
}

.service-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--provider-border, #e5e5e5);
}

.service-price {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.02em;
}

.service-duration {
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
}

.service-book-link {
    display: block;
    text-decoration: none;
}

:deep(.btn-book) {
    width: 100%;
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.625rem;
    letter-spacing: 0.1em;
    background-color: var(--provider-primary, #1a1a1a) !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
}

:deep(.btn-book:hover) {
    background-color: var(--provider-primary-hover, #333) !important;
    border-color: var(--provider-primary-hover, #333) !important;
}

/* Reviews Section */
.reviews-section {
    padding: 4rem 0;
    background: var(--provider-background, #fafafa);
}

.reviews-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

/* CTA Section */
.cta-section {
    padding: 5rem 0;
    background: var(--provider-text, #1a1a1a);
}

.cta-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 2rem;
    text-align: center;
}

.cta-section h2 {
    margin: 0 0 1rem 0;
    font-size: 2.5rem;
    color: #fff;
}

.cta-section p {
    margin: 0 0 2rem 0;
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.7);
}

:deep(.btn-cta) {
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    background-color: #fff !important;
    border-color: #fff !important;
    color: var(--provider-text, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 1rem 2.5rem;
}

:deep(.btn-cta:hover) {
    background-color: rgba(255, 255, 255, 0.9) !important;
    border-color: rgba(255, 255, 255, 0.9) !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .services-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .reviews-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .hero-cover {
        height: 60vh;
        min-height: 400px;
    }

    .hero-cover__content {
        bottom: 1.5rem;
        left: 1.5rem;
        right: 1.5rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .tagline-section {
        margin-top: -3rem;
        padding: 0 1rem;
    }

    .tagline-container {
        padding: 2rem 1.5rem;
    }

    .tagline-text {
        font-size: clamp(2rem, 8vw, 3rem);
    }

    .tagline-actions {
        flex-direction: column;
        width: 100%;
    }

    .tagline-actions :deep(button) {
        width: 100%;
    }

    .section-container {
        padding: 0 1rem;
    }

    .services-grid {
        grid-template-columns: 1fr;
    }

    .gallery-section,
    .services-section,
    .reviews-section {
        padding: 3rem 0;
    }

    .cta-section {
        padding: 4rem 0;
    }

    .cta-section h2 {
        font-size: 1.75rem;
    }

    /* Add bottom padding for mobile booking bar */
    .cta-section {
        padding-bottom: 6rem;
    }
}

@media (max-width: 480px) {
    .hero-cover {
        height: 50vh;
        min-height: 350px;
    }

    .tagline-container {
        padding: 1.5rem 1rem;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

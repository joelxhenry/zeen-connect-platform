<script setup lang="ts">
import GrandHorizonLayout from './components/GrandHorizonLayout.vue';
import HeroBookingBar from './components/HeroBookingBar.vue';
import MasonryGallery from './components/MasonryGallery.vue';
import TestimonialCard from './components/TestimonialCard.vue';
import Button from 'primevue/button';
import type { HomePageProps } from '@/types/providersite';
import { useProviderSiteHome } from '@/composables/providersite';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';
import { usePage } from '@inertiajs/vue3';
import site from '@/routes/providersite';

const props = defineProps<HomePageProps>();

const page = usePage();
const __provider = page.props.__provider as { domain: string } | null;

const { stats, bookingUrl, servicesUrl, reviewsUrl, eventsUrl, hasPortfolio, hasServices, hasReviews, hasEvents } = useProviderSiteHome(props);

const flatServices = props.servicesByCategory.flatMap(cat => cat.services);
const featuredServices = flatServices.slice(0, 6);
const featuredReviews = props.reviews.slice(0, 3);
</script>

<template>
    <GrandHorizonLayout :transparentHeader="true">
        <!-- Hero Section - Full Bleed -->
        <section class="hero-section">
            <!-- Cover Image -->
            <div class="hero-cover">
                <img
                    v-if="provider.cover_image"
                    :src="provider.cover_image"
                    :alt="provider.business_name"
                    class="hero-image"
                />
                <div v-else class="hero-placeholder"></div>
            </div>

            <!-- Dark Overlay -->
            <div class="hero-overlay"></div>

            <!-- Hero Content -->
            <div class="hero-content">
                <span v-if="provider.tagline" class="hero-subtitle">Welcome to</span>
                <h1 class="hero-title">{{ provider.tagline || provider.business_name }}</h1>
                <p v-if="provider.bio" class="hero-description">{{ provider.bio }}</p>
            </div>

            <!-- Booking Bar -->
            <HeroBookingBar
                v-if="flatServices.length > 0"
                :services="flatServices"
                :bookingUrl="bookingUrl"
                class="hero-booking-bar"
            />
        </section>

        <!-- Stats Bar -->
        <section v-if="stats.reviewCount > 0 || flatServices.length > 0" class="stats-section">
            <div class="stats-container">
                <div v-if="flatServices.length > 0" class="stat-item">
                    <span class="stat-number">{{ flatServices.length }}</span>
                    <span class="stat-label">Services</span>
                </div>
                <div v-if="stats.reviewCount > 0" class="stat-item">
                    <span class="stat-number">{{ stats.rating.toFixed(1) }}</span>
                    <span class="stat-label">Rating</span>
                </div>
                <div v-if="stats.reviewCount > 0" class="stat-item">
                    <span class="stat-number">{{ stats.reviewCount }}+</span>
                    <span class="stat-label">Happy Clients</span>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section v-if="provider.bio" class="about-section">
            <div class="section-container">
                <div class="about-content">
                    <h4 class="section-label">About Us</h4>
                    <h2 class="about-title">Experience the Extraordinary</h2>
                    <p class="about-text">{{ provider.bio }}</p>
                    <AppLink :href="bookingUrl">
                        <Button label="Make a Reservation" class="btn-primary" />
                    </AppLink>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section v-if="hasServices && featuredServices.length > 0" class="services-section">
            <div class="section-container">
                <div class="section-header">
                    <div>
                        <h4 class="section-label">Our Services</h4>
                        <h2 class="section-title">What We Offer</h2>
                    </div>
                    <AppLink :href="servicesUrl" class="view-all-link">
                        View All <i class="pi pi-arrow-right"></i>
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
                                {{ service.description.substring(0, 100) }}{{ service.description.length > 100 ? '...' : '' }}
                            </p>
                            <div class="service-meta">
                                <span class="service-price">{{ service.price_display }}</span>
                                <span class="service-duration">{{ service.duration_display }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section v-if="hasPortfolio" class="gallery-section">
            <div class="section-container">
                <div class="section-header section-header--centered">
                    <h4 class="section-label">Gallery</h4>
                    <h2 class="section-title">A Glimpse of Our World</h2>
                </div>
                <MasonryGallery
                    :images="provider.gallery?.slice(0, 8)"
                    :videos="provider.videos?.slice(0, 2)"
                />
            </div>
        </section>

        <!-- Events Section -->
        <section v-if="hasEvents" class="events-section">
            <div class="section-container">
                <div class="events-banner">
                    <div class="events-content">
                        <h4 class="section-label">Exclusive</h4>
                        <h2 class="events-title">Upcoming Events</h2>
                        <p class="events-description">Join us for exclusive events, workshops, and special experiences</p>
                    </div>
                    <AppLink :href="eventsUrl">
                        <Button label="Explore Events" class="btn-primary" />
                    </AppLink>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section v-if="hasReviews && featuredReviews.length > 0" class="testimonials-section">
            <div class="section-container">
                <div class="section-header section-header--centered">
                    <h4 class="section-label">Testimonials</h4>
                    <h2 class="section-title">What Our Guests Say</h2>
                </div>

                <div class="testimonials-list">
                    <TestimonialCard
                        v-for="(review, index) in featuredReviews"
                        :key="review.id"
                        :review="review"
                        :variant="index === 0 ? 'featured' : 'default'"
                    />
                </div>

                <div class="testimonials-cta">
                    <AppLink :href="reviewsUrl" class="view-all-link">
                        Read All Reviews <i class="pi pi-arrow-right"></i>
                    </AppLink>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="cta-background">
                <img
                    v-if="provider.cover_image"
                    :src="provider.cover_image"
                    :alt="provider.business_name"
                />
            </div>
            <div class="cta-overlay"></div>
            <div class="cta-content">
                <h4 class="section-label section-label--light">Ready to Begin?</h4>
                <h2 class="cta-title">Start Your Journey Today</h2>
                <p class="cta-text">Book your experience and discover what makes us exceptional.</p>
                <AppLink :href="bookingUrl">
                    <Button label="Reserve Your Experience" class="btn-cta" />
                </AppLink>
            </div>
        </section>
    </GrandHorizonLayout>
</template>

<style scoped>
/* Hero Section - Full Bleed 100vh */
.hero-section {
    position: relative;
    height: 100vh;
    min-height: 700px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.hero-cover {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.hero-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(0, 0, 0, 0.3) 0%,
        rgba(0, 0, 0, 0.4) 50%,
        rgba(0, 0, 0, 0.6) 100%
    );
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 0 2rem;
    max-width: 900px;
    margin-bottom: 6rem;
}

.hero-subtitle {
    display: block;
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.8125rem;
    font-weight: 500;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 1rem;
}

.hero-title {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: clamp(2.5rem, 7vw, 5rem);
    font-weight: 500;
    color: #ffffff;
    line-height: 1.1;
    margin: 0 0 1.5rem 0;
}

.hero-description {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 1.0625rem;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.8;
    max-width: 600px;
    margin: 0 auto;
}

.hero-booking-bar {
    z-index: 5;
}

/* Stats Section */
.stats-section {
    background: var(--provider-dark, #1a1a1a);
    padding: 3rem 2rem;
}

.stats-container {
    max-width: 1000px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    gap: 5rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.stat-number {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 2.5rem;
    font-weight: 500;
    color: var(--provider-primary, #c9a87c);
}

.stat-label {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.7);
}

/* Section Styles */
.section-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 3rem;
}

.section-header--centered {
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.section-label {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--provider-primary, #c9a87c);
    margin-bottom: 0.75rem;
}

.section-label--light {
    color: rgba(255, 255, 255, 0.8);
}

.section-title {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 2.25rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    margin: 0;
}

.view-all-link {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.8125rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--provider-text, #1a1a1a);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: color 0.2s;
}

.view-all-link:hover {
    color: var(--provider-primary, #c9a87c);
}

.view-all-link i {
    font-size: 0.75rem;
}

/* About Section */
.about-section {
    padding: 6rem 0;
    background: var(--provider-surface, #ffffff);
}

.about-content {
    max-width: 700px;
    margin: 0 auto;
    text-align: center;
}

.about-title {
    font-size: 2.5rem;
    margin: 0 0 1.5rem 0;
}

.about-text {
    font-size: 1.0625rem;
    color: var(--provider-secondary, #6a6a6a);
    margin-bottom: 2.5rem;
}

:deep(.btn-primary) {
    font-family: var(--font-body, 'Montserrat', sans-serif) !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    background-color: var(--provider-dark, #1a1a1a) !important;
    border-color: var(--provider-dark, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 1rem 2.5rem;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary, #c9a87c) !important;
    border-color: var(--provider-primary, #c9a87c) !important;
}

/* Services Section */
.services-section {
    padding: 6rem 0;
    background: var(--provider-background, #f8f6f3);
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.service-card {
    background: var(--provider-surface, #ffffff);
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.service-card:hover {
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
}

.service-image {
    height: 220px;
    overflow: hidden;
}

.service-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.service-card:hover .service-image img {
    transform: scale(1.05);
}

.service-content {
    padding: 1.5rem;
}

.service-content h3 {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.375rem;
    font-weight: 500;
    margin: 0 0 0.75rem 0;
    color: var(--provider-text, #1a1a1a);
}

.service-description {
    font-size: 0.9375rem;
    color: var(--provider-secondary, #6a6a6a);
    margin: 0 0 1rem 0;
    line-height: 1.6;
}

.service-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--provider-border, #e5e0d8);
}

.service-price {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.25rem;
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

/* Gallery Section */
.gallery-section {
    padding: 6rem 0;
    background: var(--provider-surface, #ffffff);
}

/* Events Section */
.events-section {
    padding: 5rem 0;
    background: var(--provider-surface, #ffffff);
}

.events-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 3rem;
    background: var(--provider-dark, #1a1a1a);
}

.events-content {
    color: white;
}

.events-title {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 2rem;
    font-weight: 500;
    color: #ffffff;
    margin: 0 0 0.75rem 0;
}

.events-description {
    margin: 0;
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.7);
}

/* Testimonials Section */
.testimonials-section {
    padding: 6rem 0;
    background: var(--provider-background, #f8f6f3);
}

.testimonials-list {
    margin-bottom: 2rem;
}

.testimonials-cta {
    text-align: center;
}

/* CTA Section */
.cta-section {
    position: relative;
    padding: 8rem 2rem;
    overflow: hidden;
}

.cta-background {
    position: absolute;
    inset: 0;
}

.cta-background img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cta-overlay {
    position: absolute;
    inset: 0;
    background: rgba(26, 26, 26, 0.85);
}

.cta-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.cta-title {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 2.5rem;
    font-weight: 500;
    color: #ffffff;
    margin: 0 0 1rem 0;
}

.cta-text {
    font-size: 1.0625rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0 0 2.5rem 0;
}

:deep(.btn-cta) {
    font-family: var(--font-body, 'Montserrat', sans-serif) !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    background-color: var(--provider-primary, #c9a87c) !important;
    border-color: var(--provider-primary, #c9a87c) !important;
    border-radius: 0 !important;
    padding: 1rem 2.5rem;
    color: var(--provider-dark, #1a1a1a) !important;
}

:deep(.btn-cta:hover) {
    background-color: #ffffff !important;
    border-color: #ffffff !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .services-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stats-container {
        gap: 3rem;
    }
}

@media (max-width: 768px) {
    .hero-section {
        min-height: 600px;
    }

    .hero-content {
        margin-bottom: 2rem;
    }

    .hero-booking-bar {
        position: relative;
        bottom: auto;
        width: 100%;
    }

    .stats-section {
        padding: 2rem 1rem;
    }

    .stats-container {
        gap: 2rem;
    }

    .stat-number {
        font-size: 2rem;
    }

    .section-container {
        padding: 0 1.5rem;
    }

    .about-section,
    .services-section,
    .gallery-section,
    .testimonials-section {
        padding: 4rem 0;
    }

    .section-title {
        font-size: 1.875rem;
    }

    .about-title {
        font-size: 2rem;
    }

    .services-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .service-image {
        height: 200px;
    }

    .cta-section {
        padding: 5rem 1.5rem;
    }

    .cta-title {
        font-size: 2rem;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>

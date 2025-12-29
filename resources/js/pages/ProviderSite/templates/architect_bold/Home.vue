<script setup lang="ts">
import ArchitectBoldLayout from './components/ArchitectBoldLayout.vue';
import StatsBar from './components/StatsBar.vue';
import Gallery from './components/Gallery.vue';
import BentoSection from './components/BentoSection.vue';
import InfoBlock from './components/InfoBlock.vue';
import Button from 'primevue/button';
import Rating from 'primevue/rating';
import type { HomePageProps } from '@/types/providersite';
import { useProviderSiteHome } from '@/composables/providersite';

// Shared provider components
import ServiceCard from '@/components/provider/ServiceCard.vue';
import ReviewCard from '@/components/provider/ReviewCard.vue';

const props = defineProps<HomePageProps>();

const {
    stats,
    bookingUrl,
    servicesUrl,
    reviewsUrl,
    getServiceBookingUrl,
    hasPortfolio,
    hasServices,
    hasAvailability,
    hasReviews,
} = useProviderSiteHome(props);

// Build hero stats from actual provider data
const heroStats = [
    { value: `${stats.bookings}+`, label: 'Bookings' },
    { value: Number(stats.rating || 0).toFixed(1), label: 'Rating', icon: 'pi pi-star-fill' },
    { value: `${stats.servicesCount}`, label: 'Services' },
    { value: `${props.teamMembers?.length || 1}`, label: 'Team' },
];

// Check if we have features
const hasFeatures = (props.features?.length ?? 0) > 0;
</script>

<template>
    <ArchitectBoldLayout title="Home">
        <div class="architect-bold-home">
            <!-- Bento Hero Section -->
            <section class="hero-section">
                <div class="hero-container">
                    <div class="bento-hero">
                        <!-- Left Side: Headline + Stats -->
                        <div class="hero-content">
                            <div class="hero-text">
                                <h1>{{ provider.business_name }}</h1>
                                <p v-if="provider.tagline" class="hero-tagline">{{ provider.tagline }}</p>
                                <p v-else class="hero-tagline">Professional services you can trust</p>
                            </div>

                            <!-- Stats Bar -->
                            <StatsBar :stats="heroStats" class="hero-stats" />

                            <!-- Primary CTA -->
                            <div class="hero-cta">
                                <AppLink :href="bookingUrl">
                                    <Button label="BOOK CONSULTATION" icon="pi pi-arrow-right" iconPos="right" size="large" class="cta-btn" />
                                </AppLink>
                            </div>
                        </div>

                        <!-- Right Side: Gallery -->
                        <div v-if="hasPortfolio" class="hero-gallery">
                            <Gallery
                                :images="provider.gallery || []"
                                :videos="provider.videos || []"
                                :maxDisplay="6"
                                title="OUR WORK"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Services Preview Section -->
            <section v-if="hasServices" class="services-section">
                <div class="section-container">
                    <BentoSection title="Our Services" :subtitle="provider.bio || 'Explore our range of professional services'" :columns="3" gap="md">
                        <template v-for="categoryGroup in servicesByCategory" :key="categoryGroup.category.id">
                            <ServiceCard
                                v-for="service in categoryGroup.services.slice(0, 6)"
                                :key="service.id"
                                :service="service"
                                :category="categoryGroup.category"
                                :bookingUrl="getServiceBookingUrl(service.id)"
                                class="bento-service-card"
                            />
                        </template>
                    </BentoSection>

                    <div class="section-footer">
                        <AppLink :href="servicesUrl">
                            <Button label="VIEW ALL SERVICES" severity="secondary" outlined class="view-all-btn" />
                        </AppLink>
                    </div>
                </div>
            </section>

            <!-- Why Choose Us Section -->
            <section v-if="hasFeatures" class="features-section">
                <div class="section-container">
                    <BentoSection title="Why Choose Us" :columns="4" gap="sm">
                        <InfoBlock
                            v-for="(feature, index) in features!.slice(0, 4)"
                            :key="index"
                            :icon="feature.icon"
                            :title="feature.title"
                            :description="feature.description"
                            variant="default"
                        />
                    </BentoSection>
                </div>
            </section>

            <!-- About Section -->
            <section v-if="provider.bio" class="about-section">
                <div class="section-container">
                    <div class="about-grid">
                        <div class="about-content">
                            <h2>ABOUT US</h2>
                            <p>{{ provider.bio }}</p>
                            <AppLink :href="bookingUrl">
                                <Button label="SCHEDULE A CALL" outlined class="about-btn" />
                            </AppLink>
                        </div>
                        <div v-if="provider.cover_image" class="about-image">
                            <img :src="provider.cover_image" :alt="provider.business_name" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Testimonials Section -->
            <section v-if="hasReviews" class="reviews-section">
                <div class="section-container">
                    <div class="section-header">
                        <div>
                            <h2>CLIENT TESTIMONIALS</h2>
                            <div class="review-summary">
                                <Rating :modelValue="reviewStats.average" readonly :cancel="false" />
                                <span>{{ reviewStats.average_display }} average from {{ reviewStats.total }} reviews</span>
                            </div>
                        </div>
                        <AppLink :href="reviewsUrl" class="view-all-link">
                            VIEW ALL
                            <i class="pi pi-arrow-right"></i>
                        </AppLink>
                    </div>

                    <div class="reviews-grid">
                        <ReviewCard
                            v-for="review in reviews.slice(0, 3)"
                            :key="review.id"
                            :review="review"
                            class="bento-review-card"
                        />
                    </div>
                </div>
            </section>

            <!-- Contact Section -->
            <section v-if="provider.address || hasAvailability" class="contact-section">
                <div class="section-container">
                    <div class="contact-grid">
                        <div class="contact-info">
                            <h2>CONTACT US</h2>
                            <div v-if="provider.address" class="contact-item">
                                <i class="pi pi-map-marker"></i>
                                <div>
                                    <strong>Location</strong>
                                    <p>{{ provider.address }}</p>
                                </div>
                            </div>
                            <div v-if="hasAvailability" class="contact-item">
                                <i class="pi pi-clock"></i>
                                <div>
                                    <strong>Business Hours</strong>
                                    <div class="hours-list">
                                        <div v-for="day in availability" :key="day.day_of_week" class="hours-item">
                                            <span>{{ day.day }}</span>
                                            <span>{{ day.start_time }} - {{ day.end_time }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="contact-cta">
                            <h3>Ready to Get Started?</h3>
                            <p>Book a consultation today and let us help you achieve your goals.</p>
                            <AppLink :href="bookingUrl">
                                <Button label="BOOK NOW" icon="pi pi-calendar" size="large" class="contact-btn" />
                            </AppLink>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bottom CTA -->
            <section class="cta-section">
                <div class="cta-content">
                    <h2>LET'S WORK TOGETHER</h2>
                    <p>{{ provider.tagline || 'Schedule your consultation today' }}</p>
                    <AppLink :href="bookingUrl">
                        <Button label="GET STARTED" icon="pi pi-arrow-right" iconPos="right" size="large" class="final-cta-btn" />
                    </AppLink>
                </div>
            </section>
        </div>
    </ArchitectBoldLayout>
</template>

<style scoped>
.architect-bold-home {
    min-height: 100%;
    background: var(--provider-background, #f5f5f5);
}

/* Section Utilities */
.section-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Hero Section */
.hero-section {
    background: var(--provider-surface, #fff);
    padding: 4rem 0;
    border-bottom: 2px solid var(--provider-text, #1a1a1a);
}

.hero-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.bento-hero {
    display: grid;
    grid-template-columns: 1fr 480px;
    gap: 3rem;
    align-items: start;
}

.hero-gallery {
    background: var(--provider-surface, #fff);
}

.hero-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.hero-text h1 {
    margin: 0 0 0.75rem 0;
    font-size: 3rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: -0.02em;
    line-height: 1.1;
}

.hero-tagline {
    margin: 0;
    font-size: 1.125rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.6;
}

.hero-stats {
    margin-top: 1rem;
}

.hero-cta {
    margin-top: 0.5rem;
}

:deep(.cta-btn) {
    background-color: var(--provider-primary, #1a1a1a) !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
    font-weight: 600;
    letter-spacing: 0.05em;
    padding: 1rem 2rem;
}

:deep(.cta-btn:hover) {
    background-color: var(--provider-primary-hover, #333) !important;
    border-color: var(--provider-primary-hover, #333) !important;
}

/* Services Section */
.services-section {
    padding: 4rem 0;
    background: var(--provider-background, #f5f5f5);
}

.section-footer {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
}

:deep(.view-all-btn) {
    border-radius: 0 !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    color: var(--provider-primary, #1a1a1a) !important;
    font-weight: 600;
    letter-spacing: 0.05em;
}

:deep(.view-all-btn:hover) {
    background-color: var(--provider-primary-10, rgba(26, 26, 26, 0.1)) !important;
}

:deep(.bento-service-card) {
    border-radius: 0 !important;
}

/* Features Section */
.features-section {
    padding: 4rem 0;
    background: var(--provider-surface, #fff);
}

/* About Section */
.about-section {
    padding: 4rem 0;
    background: var(--provider-background, #f5f5f5);
}

.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.about-content h2 {
    margin: 0 0 1.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.05em;
}

.about-content p {
    margin: 0 0 2rem 0;
    font-size: 1rem;
    line-height: 1.8;
    color: var(--provider-secondary, #6b7280);
}

:deep(.about-btn) {
    border-radius: 0 !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    color: var(--provider-primary, #1a1a1a) !important;
    font-weight: 600;
    letter-spacing: 0.05em;
}

.about-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

/* Reviews Section */
.reviews-section {
    padding: 4rem 0;
    background: var(--provider-surface, #fff);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.section-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.05em;
}

.review-summary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--provider-text, #1a1a1a);
    text-decoration: none;
    font-size: 0.8125rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    transition: opacity 0.15s;
}

.view-all-link:hover {
    opacity: 0.7;
}

.reviews-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

:deep(.bento-review-card) {
    border-radius: 0 !important;
}

/* Contact Section */
.contact-section {
    padding: 4rem 0;
    background: var(--provider-background, #f5f5f5);
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
}

.contact-info h2 {
    margin: 0 0 2rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.05em;
}

.contact-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.contact-item i {
    font-size: 1.25rem;
    color: var(--provider-primary, #1a1a1a);
    margin-top: 0.125rem;
}

.contact-item strong {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--provider-text, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.375rem;
}

.contact-item p {
    margin: 0;
    color: var(--provider-secondary, #6b7280);
    font-size: 0.9375rem;
}

.hours-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.hours-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    max-width: 280px;
}

.contact-cta {
    background: var(--provider-surface, #fff);
    padding: 2rem;
    border: 2px solid var(--provider-text, #1a1a1a);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.contact-cta h3 {
    margin: 0 0 0.75rem 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.contact-cta p {
    margin: 0 0 1.5rem 0;
    color: var(--provider-secondary, #6b7280);
}

:deep(.contact-btn) {
    background-color: var(--provider-primary, #1a1a1a) !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
    font-weight: 600;
    letter-spacing: 0.05em;
}

/* CTA Section */
.cta-section {
    background: var(--provider-text, #1a1a1a);
    padding: 5rem 1.5rem;
}

.cta-content {
    max-width: 700px;
    margin: 0 auto;
    text-align: center;
}

.cta-content h2 {
    margin: 0 0 1rem 0;
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.05em;
}

.cta-content p {
    margin: 0 0 2rem 0;
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.7);
}

:deep(.final-cta-btn) {
    background-color: var(--provider-primary, #fff) !important;
    border-color: var(--provider-primary, #fff) !important;
    color: var(--provider-text, #1a1a1a) !important;
    border-radius: 0 !important;
    font-weight: 600;
    letter-spacing: 0.05em;
    padding: 1rem 2rem;
}

:deep(.final-cta-btn:hover) {
    background-color: rgba(255, 255, 255, 0.9) !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .bento-hero {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .hero-gallery {
        max-width: 100%;
    }

    .hero-text h1 {
        font-size: 2.5rem;
    }

    .about-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .about-image {
        order: -1;
    }

    .reviews-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .contact-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
}

@media (max-width: 768px) {
    .hero-section {
        padding: 2rem 0;
    }

    .hero-text h1 {
        font-size: 2rem;
    }

    .reviews-grid {
        grid-template-columns: 1fr;
    }

    .section-header {
        flex-direction: column;
        gap: 1rem;
    }

    .cta-content h2 {
        font-size: 1.75rem;
    }
}
</style>

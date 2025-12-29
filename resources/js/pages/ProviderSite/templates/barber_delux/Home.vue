<script setup lang="ts">
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
import Button from 'primevue/button';
import Rating from 'primevue/rating';
import type { HomePageProps } from '@/types/providersite';
import { useProviderSiteHome } from '@/composables/providersite';

// Template-specific components
import SplitHero from './components/SplitHero.vue';
import ServicePills from './components/ServicePills.vue';
import FeatureCards from './components/FeatureCards.vue';
import TeamShowcase from './components/TeamShowcase.vue';
import BusinessHoursCard from './components/BusinessHoursCard.vue';

// Shared provider components
import ServiceCard from '@/components/provider/ServiceCard.vue';
import ReviewCard from '@/components/provider/ReviewCard.vue';
import ProviderPortfolio from '@/components/provider/ProviderPortfolio.vue';

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
    { value: Number(stats.rating || 0).toFixed(1), label: 'Rating' },
    { value: `${stats.servicesCount}`, label: 'Services' },
];

// Get first video URL for hero
const firstVideoUrl = props.provider.videos?.[0]?.watch_url;

// Get services for pills (flatten from categories)
const highlightServices = props.servicesByCategory
    .flatMap(cat => cat.services)
    .slice(0, 5)
    .map(s => ({
        id: s.id,
        name: s.name,
    }));

// Check if we have team members
const hasTeamMembers = (props.teamMembers?.length ?? 0) > 0;

// Check if we have features
const hasFeatures = (props.features?.length ?? 0) > 0;
</script>

<template>
    <ProviderSiteLayout title="Home">
        <div class="barber-delux-home">
            <!-- Split Hero Section -->
            <SplitHero
                :provider="provider"
                :stats="heroStats"
                :ctaUrl="bookingUrl"
                :showVideoButton="!!firstVideoUrl"
                :videoUrl="firstVideoUrl"
            />

            <!-- Service Pills -->
            <section v-if="hasServices" class="pills-section">
                <div class="section-container">
                    <ServicePills :services="highlightServices" :maxDisplay="5" />
                </div>
            </section>

            <!-- About / Portfolio Section -->
            <section v-if="provider.bio || hasPortfolio" class="about-section">
                <div class="section-container">
                    <div class="about-grid">
                        <div v-if="hasPortfolio" class="about-media">
                            <ProviderPortfolio
                                :images="provider.gallery || []"
                                :videos="provider.videos || []"
                                :maxDisplay="4"
                            />
                        </div>
                        <div class="about-content">
                            <h2 class="about-title">{{ provider.business_name }}</h2>
                            <p v-if="provider.bio" class="about-text">{{ provider.bio }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Feature Cards Section -->
            <FeatureCards
                v-if="hasFeatures"
                :features="features!"
                :columns="4"
            />

            <!-- Team Showcase Section -->
            <TeamShowcase
                v-if="hasTeamMembers"
                :teamMembers="teamMembers!"
                :showSocials="true"
            />

            <!-- Services Preview Section -->
            <section v-if="hasServices" class="services-section">
                <div class="section-container">
                    <div class="section-header">
                        <h2 class="section-title">{{ provider.business_name }}</h2>
                        <AppLink :href="servicesUrl" class="view-all-link">
                            <i class="pi pi-arrow-right"></i>
                        </AppLink>
                    </div>
                    <div class="services-grid">
                        <template v-for="categoryGroup in servicesByCategory" :key="categoryGroup.category.id">
                            <ServiceCard
                                v-for="service in categoryGroup.services.slice(0, 6)"
                                :key="service.id"
                                :service="service"
                                :category="categoryGroup.category"
                                :bookingUrl="getServiceBookingUrl(service.id)"
                            />
                        </template>
                    </div>
                </div>
            </section>

            <!-- Reviews Section -->
            <section v-if="hasReviews" class="reviews-section">
                <div class="section-container">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">{{ provider.business_name }}</h2>
                            <div class="review-summary">
                                <Rating :modelValue="reviewStats.average" readonly :cancel="false" />
                                <span>{{ reviewStats.average_display }} ({{ reviewStats.total }})</span>
                            </div>
                        </div>
                        <AppLink :href="reviewsUrl" class="view-all-link">
                            <i class="pi pi-arrow-right"></i>
                        </AppLink>
                    </div>
                    <div class="reviews-grid">
                        <ReviewCard
                            v-for="review in reviews.slice(0, 3)"
                            :key="review.id"
                            :review="review"
                        />
                    </div>
                </div>
            </section>

            <!-- Business Hours & Contact Section -->
            <section v-if="hasAvailability" class="hours-section">
                <div class="section-container">
                    <div class="hours-grid">
                        <BusinessHoursCard :hours="availability" :bookingUrl="bookingUrl" />
                        <div class="contact-info">
                            <h3 class="contact-title">{{ provider.business_name }}</h3>
                            <div v-if="provider.address" class="contact-item">
                                <i class="pi pi-map-marker"></i>
                                <span>{{ provider.address }}</span>
                            </div>
                            <AppLink :href="bookingUrl" class="contact-cta">
                                <Button label="Book Now" icon="pi pi-calendar" class="cta-button" />
                            </AppLink>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="cta-section">
                <div class="cta-content">
                    <h2>{{ provider.business_name }}</h2>
                    <p v-if="provider.tagline">{{ provider.tagline }}</p>
                    <AppLink :href="bookingUrl">
                        <Button label="Book Now" icon="pi pi-calendar" size="large" class="cta-button" />
                    </AppLink>
                </div>
            </section>
        </div>
    </ProviderSiteLayout>
</template>

<style scoped>
.barber-delux-home {
    min-height: 100%;
    background: var(--provider-background, #f9fafb);
}

/* Section Utilities */
.section-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.section-title {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--provider-text, #1f2937);
}

.view-all-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--provider-primary, #3b82f6);
    color: #fff;
    text-decoration: none;
    transition: all 0.2s;
}

.view-all-link:hover {
    transform: scale(1.05);
}

/* Pills Section */
.pills-section {
    padding: 2rem 0;
    background: var(--provider-surface, #fff);
    border-bottom: 1px solid var(--provider-border, #e5e7eb);
}

/* About Section */
.about-section {
    padding: 4rem 0;
    background: var(--provider-surface, #fff);
}

.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.about-media {
    border-radius: 0.75rem;
    overflow: hidden;
}

.about-title {
    margin: 0 0 1rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--provider-text, #1f2937);
}

.about-text {
    margin: 0;
    font-size: 1rem;
    line-height: 1.7;
    color: var(--provider-text-muted, #6b7280);
}

/* Services Section */
.services-section {
    padding: 4rem 0;
    background: var(--provider-background, #f9fafb);
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
}

/* Reviews Section */
.reviews-section {
    padding: 4rem 0;
    background: var(--provider-surface, #fff);
}

.review-summary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--provider-text-muted, #6b7280);
}

.reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1rem;
}

/* Hours Section */
.hours-section {
    padding: 4rem 0;
    background: var(--provider-background, #f9fafb);
}

.hours-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    max-width: 900px;
    margin: 0 auto;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.contact-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: var(--provider-text-muted, #6b7280);
}

.contact-item i {
    color: var(--provider-primary, #3b82f6);
    margin-top: 0.125rem;
}

.contact-cta {
    text-decoration: none;
    margin-top: auto;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, var(--provider-primary, #3b82f6) 0%, var(--provider-primary-hover, #2563eb) 100%);
    padding: 4rem 1.5rem;
}

.cta-content {
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
}

.cta-content h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #fff;
}

.cta-content p {
    margin: 0 0 1.5rem 0;
    color: rgba(255, 255, 255, 0.9);
}

:deep(.cta-button) {
    background: var(--provider-primary, #3b82f6) !important;
    border-color: var(--provider-primary, #3b82f6) !important;
}

:deep(.cta-button:hover) {
    background: var(--provider-primary-hover, #2563eb) !important;
    border-color: var(--provider-primary-hover, #2563eb) !important;
}

.cta-section :deep(.cta-button) {
    background: #fff !important;
    border-color: #fff !important;
    color: var(--provider-primary, #3b82f6) !important;
}

.cta-section :deep(.cta-button:hover) {
    background: rgba(255, 255, 255, 0.9) !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .about-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .about-media {
        order: -1;
    }
}

@media (max-width: 768px) {
    .services-grid,
    .reviews-grid {
        grid-template-columns: 1fr;
    }

    .hours-grid {
        grid-template-columns: 1fr;
    }

    .section-header {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

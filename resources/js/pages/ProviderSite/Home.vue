<script setup lang="ts">
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
import Button from 'primevue/button';
import Rating from 'primevue/rating';

// Provider components
import ProviderHero from '@/components/provider/ProviderHero.vue';
import ProviderStats from '@/components/provider/ProviderStats.vue';
import ServiceCard from '@/components/provider/ServiceCard.vue';
import ReviewCard from '@/components/provider/ReviewCard.vue';
import BusinessHours from '@/components/provider/BusinessHours.vue';
import ProviderPortfolio from '@/components/provider/ProviderPortfolio.vue';

interface Service {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    display_image?: string;
}

interface GalleryImage {
    id: number;
    uuid: string;
    url: string;
    thumbnail: string;
    medium: string;
    large: string;
    filename: string;
}

interface Video {
    id: number;
    uuid: string;
    platform: 'youtube' | 'vimeo';
    video_id: string;
    url: string;
    embed_url: string;
    watch_url: string;
    title?: string;
    thumbnail_url?: string;
    human_duration?: string;
}

interface ServiceCategory {
    category: {
        id: number;
        name: string;
        icon?: string;
        slug: string;
    };
    services: Service[];
}

interface Review {
    id: number;
    uuid: string;
    client: {
        name: string;
        avatar?: string;
    };
    service_name: string;
    rating: number;
    comment?: string;
    provider_response?: string;
    formatted_date: string;
    time_ago: string;
}

interface Availability {
    day: string;
    day_of_week: number;
    start_time: string;
    end_time: string;
}

interface Props {
    provider: {
        id: number;
        uuid: string;
        slug: string;
        business_name: string;
        tagline?: string;
        bio?: string;
        avatar?: string;
        cover_image?: string;
        website?: string;
        social_links?: Record<string, string>;
        location?: string;
        address?: string;
        rating_avg: number;
        rating_count: number;
        rating_display: string;
        total_bookings: number;
        is_featured: boolean;
        verified_at?: string;
        services_count: number;
        gallery?: GalleryImage[];
        videos?: Video[];
    };
    servicesByCategory: ServiceCategory[];
    availability: Availability[];
    reviews: Review[];
    reviewStats: {
        total: number;
        average: number;
        average_display: string;
        distribution: Record<number, number>;
    };
}

const props = defineProps<Props>();

// Use relative paths since we're already on the provider's subdomain
const bookingUrl = '/book';
const servicesUrl = '/services';
const reviewsUrl = '/reviews';

const stats = {
    bookings: props.provider.total_bookings,
    rating: props.provider.rating_avg,
    reviewCount: props.provider.rating_count,
    servicesCount: props.provider.services_count,
};

const getServiceBookingUrl = (serviceId: number) => `/book?service=${serviceId}`;
</script>

<template>
    <ProviderSiteLayout title="Home">
        <template #hero>
            <ProviderHero :provider="provider" :bookingUrl="bookingUrl" />
        </template>

        <div class="provider-home">
            <!-- Stats Section -->
            <ProviderStats :stats="stats" />

            <!-- About Section -->
            <section v-if="provider.bio" class="about-section">
                <div class="section-container">
                    <h2>About</h2>
                    <p class="bio">{{ provider.bio }}</p>
                </div>
            </section>

            <!-- Portfolio Section (Gallery + Videos) -->
            <section
                v-if="(provider.gallery && provider.gallery.length > 0) || (provider.videos && provider.videos.length > 0)"
                class="portfolio-section">
                <div class="section-container">
                    <h2>Portfolio</h2>
                    <ProviderPortfolio :images="provider.gallery || []" :videos="provider.videos || []"
                        :maxDisplay="6" />
                </div>
            </section>

            <!-- Services Preview -->
            <section v-if="servicesByCategory.length > 0" class="services-section">
                <div class="section-container">
                    <div class="section-header">
                        <h2>Services</h2>
                        <AppLink :href="servicesUrl" class="view-all">
                            View All <i class="pi pi-arrow-right"></i>
                        </AppLink>
                    </div>
                    <div class="services-grid">
                        <template v-for="categoryGroup in servicesByCategory" :key="categoryGroup.category.id">
                            <ServiceCard v-for="service in categoryGroup.services" :key="service.id" :service="service"
                                :category="categoryGroup.category" :bookingUrl="getServiceBookingUrl(service.id)" />
                        </template>
                    </div>
                </div>
            </section>


            <!-- Availability Section -->
            <section v-if="availability.length > 0" class="availability-section">
                <div class="section-container">
                    <h2>Business Hours</h2>
                    <BusinessHours :hours="availability" :bookingUrl="bookingUrl" />
                </div>
            </section>

            <!-- Reviews Preview -->
            <section v-if="reviews.length > 0" class="reviews-section">
                <div class="section-container">
                    <div class="section-header">
                        <div>
                            <h2>Reviews</h2>
                            <div class="review-summary">
                                <Rating :modelValue="reviewStats.average" readonly :cancel="false" />
                                <span>{{ reviewStats.average_display }} out of 5 ({{ reviewStats.total }}
                                    reviews)</span>
                            </div>
                        </div>
                        <AppLink :href="reviewsUrl" class="view-all">
                            View All <i class="pi pi-arrow-right"></i>
                        </AppLink>
                    </div>
                    <div class="reviews-grid">
                        <ReviewCard v-for="review in reviews" :key="review.id" :review="review" />
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="cta-section">
                <div class="cta-content">
                    <h2>Ready to book?</h2>
                    <p>Choose a service and find a time that works for you.</p>
                    <AppLink :href="bookingUrl">
                        <Button label="Book an Appointment" icon="pi pi-calendar" size="large"
                            class="cta-button" />
                    </AppLink>
                </div>
            </section>
        </div>
    </ProviderSiteLayout>
</template>

<style scoped>
.provider-home {
    min-height: 100%;
}

.section-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.about-section {
    padding: 3rem 0;
    background: white;
}

.about-section h2 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text);
}

.bio {
    margin: 0;
    color: #4b5563;
    line-height: 1.7;
    max-width: 800px;
}

.services-section {
    padding: 3rem 0;
    background: #f9fafb;
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

.view-all {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--provider-primary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.view-all:hover {
    text-decoration: underline;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
}

.portfolio-section {
    padding: 3rem 0;
    background: white;
}

.portfolio-section h2 {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text);
}

.availability-section {
    padding: 3rem 0;
    background: white;
}

.availability-section h2 {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text);
}

.reviews-section {
    padding: 3rem 0;
    background: #f9fafb;
}

.reviews-section h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text);
}

.review-summary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1rem;
}

.cta-section {
    background: linear-gradient(135deg, var(--provider-primary) 0%, var(--provider-primary-hover) 100%);
    padding: 4rem 1.5rem;
}

/* CTA button styling */
:deep(.cta-button) {
    background-color: white !important;
    color: var(--provider-primary) !important;
    border-color: white !important;
}

:deep(.cta-button:hover) {
    background-color: #f9fafb !important;
}

.cta-content {
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
}

.cta-content h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: white;
}

.cta-content p {
    margin: 0 0 1.5rem 0;
    color: rgba(255, 255, 255, 0.8);
}

@media (max-width: 768px) {

    .services-grid,
    .reviews-grid {
        grid-template-columns: 1fr;
    }
}
</style>

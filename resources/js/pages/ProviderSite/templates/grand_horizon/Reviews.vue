<script setup lang="ts">
import GrandHorizonLayout from './components/GrandHorizonLayout.vue';
import TestimonialCard from './components/TestimonialCard.vue';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Rating from 'primevue/rating';
import type { ReviewsPageProps } from '@/types/providersite';
import { useProviderSiteReviews } from '@/composables/providersite';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';
import { usePage } from '@inertiajs/vue3';

const props = defineProps<ReviewsPageProps>();

const page = usePage();
const __provider = page.props.__provider as { domain: string } | null;

const { getDistributionPercentage, hasMorePages, loadMore } = useProviderSiteReviews(props);

const getBookingUrl = () => {
    return ProviderSiteBookingController.create({ provider: __provider?.domain ?? '' }).url;
};
</script>

<template>
    <GrandHorizonLayout title="Reviews">
        <div class="reviews-page">
            <!-- Page Header -->
            <section class="page-header">
                <div class="header-content">
                    <h4 class="header-label">Testimonials</h4>
                    <h1>Guest Experiences</h1>
                    <p v-if="reviewStats.total > 0">{{ reviewStats.total }} stories from our valued guests</p>
                    <p v-else>Be the first to share your experience</p>
                </div>
            </section>

            <!-- Reviews Content -->
            <section class="reviews-content">
                <div class="content-container">
                    <!-- Rating Summary -->
                    <div v-if="reviewStats.total > 0" class="rating-summary">
                        <div class="summary-score">
                            <span class="score-number">{{ reviewStats.average_display }}</span>
                            <Rating :modelValue="reviewStats.average" readonly :cancel="false" class="score-rating" />
                            <span class="score-label">Overall Rating</span>
                        </div>
                        <div class="summary-distribution">
                            <div
                                v-for="stars in [5, 4, 3, 2, 1]"
                                :key="stars"
                                class="distribution-row"
                            >
                                <span class="star-label">{{ stars }}</span>
                                <i class="pi pi-star-fill star-icon"></i>
                                <ProgressBar
                                    :value="getDistributionPercentage(reviewStats.distribution[stars] || 0)"
                                    :showValue="false"
                                    class="distribution-bar"
                                />
                                <span class="distribution-count">{{ reviewStats.distribution[stars] || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews List -->
                    <div v-if="reviews.data.length > 0" class="reviews-list">
                        <TestimonialCard
                            v-for="(review, index) in reviews.data"
                            :key="review.id"
                            :review="review"
                            :variant="index === 0 ? 'featured' : 'default'"
                        />

                        <!-- Load More -->
                        <div v-if="hasMorePages" class="load-more">
                            <Button
                                label="Load More Reviews"
                                severity="secondary"
                                outlined
                                @click="loadMore"
                                class="load-more-btn"
                            />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="empty-state">
                        <i class="pi pi-star"></i>
                        <h3>No reviews yet</h3>
                        <p>Be the first to share your experience with us!</p>
                        <AppLink :href="getBookingUrl()">
                            <Button label="Book Your Experience" class="btn-primary" />
                        </AppLink>
                    </div>
                </div>
            </section>
        </div>
    </GrandHorizonLayout>
</template>

<style scoped>
.reviews-page {
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

/* Reviews Content */
.reviews-content {
    padding: 5rem 0;
}

.content-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Rating Summary */
.rating-summary {
    display: flex;
    gap: 4rem;
    padding: 3rem;
    background: var(--provider-surface, #ffffff);
    border: 1px solid var(--provider-border, #e5e0d8);
    margin-bottom: 4rem;
}

.summary-score {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 150px;
}

.score-number {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 4rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    line-height: 1;
}

:deep(.score-rating .p-rating-icon) {
    font-size: 1rem;
    color: var(--provider-border, #e5e0d8);
}

:deep(.score-rating .p-rating-icon.p-rating-icon-active) {
    color: var(--provider-primary, #c9a87c);
}

.score-label {
    margin-top: 0.75rem;
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--provider-secondary, #6a6a6a);
}

.summary-distribution {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.75rem;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.star-label {
    width: 16px;
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    text-align: right;
}

.star-icon {
    color: var(--provider-primary, #c9a87c);
    font-size: 0.75rem;
}

.distribution-bar {
    flex: 1;
    height: 8px;
}

:deep(.distribution-bar .p-progressbar-value) {
    background: var(--provider-dark, #1a1a1a);
    border-radius: 0;
}

:deep(.distribution-bar .p-progressbar) {
    background: var(--provider-border, #e5e0d8);
    border-radius: 0;
}

.distribution-count {
    width: 28px;
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--provider-secondary, #6a6a6a);
    text-align: right;
}

/* Reviews List */
.reviews-list {
    display: flex;
    flex-direction: column;
}

/* Load More */
.load-more {
    display: flex;
    justify-content: center;
    padding-top: 3rem;
}

:deep(.load-more-btn) {
    font-family: var(--font-body, 'Montserrat', sans-serif) !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    border-radius: 0 !important;
    border-color: var(--provider-dark, #1a1a1a) !important;
    color: var(--provider-dark, #1a1a1a) !important;
    padding: 1rem 2rem;
}

:deep(.load-more-btn:hover) {
    background-color: var(--provider-dark, #1a1a1a) !important;
    color: #ffffff !important;
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
    margin: 0 0 2rem 0;
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

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        padding: 4rem 1.5rem;
    }

    .content-container {
        padding: 0 1.5rem;
    }

    .reviews-content {
        padding: 3rem 0;
    }

    .rating-summary {
        flex-direction: column;
        gap: 2rem;
        padding: 2rem;
    }

    .summary-score {
        padding-bottom: 2rem;
        border-bottom: 1px solid var(--provider-border, #e5e0d8);
    }

    .score-number {
        font-size: 3.5rem;
    }
}
</style>

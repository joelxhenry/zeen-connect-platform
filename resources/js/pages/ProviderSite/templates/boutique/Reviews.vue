<script setup lang="ts">
import BoutiqueLayout from './components/BoutiqueLayout.vue';
import MinimalReviewCard from './components/MinimalReviewCard.vue';
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
    <BoutiqueLayout title="Reviews">
        <div class="reviews-page">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-container">
                    <h1>Client Reviews</h1>
                    <p v-if="reviewStats.total > 0">{{ reviewStats.total }} reviews from our valued clients</p>
                    <p v-else>No reviews yet</p>
                </div>
            </div>

            <!-- Reviews Content -->
            <div class="reviews-content">
                <div class="content-container">
                    <!-- Rating Summary -->
                    <div v-if="reviewStats.total > 0" class="rating-summary">
                        <div class="summary-score">
                            <span class="score-number">{{ reviewStats.average_display }}</span>
                            <Rating :modelValue="reviewStats.average" readonly :cancel="false" class="score-rating" />
                            <span class="score-label">out of 5</span>
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
                        <MinimalReviewCard
                            v-for="(review, index) in reviews.data"
                            :key="review.id"
                            :review="review"
                            :showDivider="index < reviews.data.length - 1"
                        />

                        <!-- Load More -->
                        <div v-if="hasMorePages" class="load-more">
                            <Button
                                label="Load more reviews"
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
                        <p>Be the first to share your experience!</p>
                        <AppLink :href="getBookingUrl()">
                            <Button label="Book an Appointment" class="btn-primary" />
                        </AppLink>
                    </div>
                </div>
            </div>
        </div>
    </BoutiqueLayout>
</template>

<style scoped>
.reviews-page {
    min-height: 100vh;
}

/* Page Header */
.page-header {
    padding: 4rem 2rem;
    text-align: center;
    background: var(--provider-surface, #fff);
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

.header-container {
    max-width: 600px;
    margin: 0 auto;
}

.page-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: clamp(2rem, 4vw, 2.75rem);
    color: var(--provider-text, #3d3d3d);
}

.page-header p {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-secondary, #8a8a8a);
}

/* Reviews Content */
.reviews-content {
    padding: 3rem 0 5rem;
}

.content-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Rating Summary */
.rating-summary {
    display: flex;
    gap: 3rem;
    padding: 2rem;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #ebe8e4);
    border-radius: 1rem;
    margin-bottom: 2.5rem;
}

.summary-score {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 120px;
}

.score-number {
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 3.5rem;
    font-weight: 500;
    color: var(--provider-text, #3d3d3d);
    line-height: 1;
}

:deep(.score-rating .p-rating-icon) {
    font-size: 0.875rem;
    color: var(--provider-border, #ebe8e4);
}

:deep(.score-rating .p-rating-icon.p-rating-icon-active) {
    color: var(--provider-warning, #e5b567);
}

.score-label {
    margin-top: 0.5rem;
    font-size: 0.8125rem;
    color: var(--provider-secondary, #8a8a8a);
}

.summary-distribution {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.5rem;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.star-label {
    width: 14px;
    font-size: 0.875rem;
    font-weight: 400;
    color: var(--provider-text, #3d3d3d);
    text-align: right;
}

.star-icon {
    color: var(--provider-warning, #e5b567);
    font-size: 0.625rem;
}

.distribution-bar {
    flex: 1;
    height: 6px;
}

:deep(.distribution-bar .p-progressbar-value) {
    background: var(--provider-primary, #8b7355);
    border-radius: 3px;
}

:deep(.distribution-bar .p-progressbar) {
    background: var(--provider-border, #ebe8e4);
    border-radius: 3px;
}

.distribution-count {
    width: 24px;
    font-size: 0.75rem;
    color: var(--provider-secondary, #8a8a8a);
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
    padding-top: 2rem;
}

:deep(.load-more-btn) {
    font-family: var(--font-body, 'Nunito Sans', sans-serif) !important;
    font-weight: 400;
    font-size: 0.9375rem;
    border-radius: 2rem !important;
    border-color: var(--provider-border, #ebe8e4) !important;
    color: var(--provider-text, #3d3d3d) !important;
    padding: 0.625rem 1.5rem;
}

:deep(.load-more-btn:hover) {
    background-color: var(--provider-primary-05, rgba(139, 115, 85, 0.05)) !important;
    border-color: var(--provider-primary, #8b7355) !important;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state i {
    font-size: 3rem;
    color: var(--provider-border, #ebe8e4);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--provider-text, #3d3d3d);
}

.empty-state p {
    margin: 0 0 2rem 0;
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

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        padding: 3rem 1rem;
    }

    .content-container {
        padding: 0 1rem;
    }

    .reviews-content {
        padding: 2rem 0 4rem;
    }

    .rating-summary {
        flex-direction: column;
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .summary-score {
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--provider-border, #ebe8e4);
    }

    .score-number {
        font-size: 3rem;
    }
}
</style>

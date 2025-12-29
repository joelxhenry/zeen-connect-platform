<script setup lang="ts">
import ShowcaseLayout from './components/ShowcaseLayout.vue';
import StickyBookingWidget from './components/StickyBookingWidget.vue';
import BubbleReviewCard from './components/BubbleReviewCard.vue';
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
    <ShowcaseLayout title="Reviews">
        <div class="reviews-page">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-container">
                    <span class="header-label">WHAT CLIENTS SAY</span>
                    <h1>REVIEWS</h1>
                    <p v-if="reviewStats.total > 0" class="header-count">
                        {{ reviewStats.total }} verified reviews
                    </p>
                    <p v-else class="header-count">No reviews yet</p>
                </div>
            </div>

            <!-- Reviews Content -->
            <div class="reviews-content">
                <div class="content-container">
                    <!-- Rating Summary -->
                    <div v-if="reviewStats.total > 0" class="rating-summary">
                        <div class="summary-main">
                            <div class="summary-score">
                                <span class="score-number">{{ reviewStats.average_display }}</span>
                                <Rating :modelValue="reviewStats.average" readonly :cancel="false" class="score-stars" />
                                <span class="score-label">AVERAGE RATING</span>
                            </div>
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
                        <BubbleReviewCard
                            v-for="review in reviews.data"
                            :key="review.id"
                            :review="review"
                        />

                        <!-- Load More -->
                        <div v-if="hasMorePages" class="load-more">
                            <Button
                                label="LOAD MORE REVIEWS"
                                severity="secondary"
                                outlined
                                @click="loadMore"
                                class="load-more-btn"
                            />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="empty-state">
                        <div class="empty-icon">
                            <i class="pi pi-star"></i>
                        </div>
                        <h3>NO REVIEWS YET</h3>
                        <p>Be the first to share your experience!</p>
                        <AppLink :href="getBookingUrl()">
                            <Button label="BOOK NOW" class="btn-primary" />
                        </AppLink>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Booking Widget -->
        <StickyBookingWidget :bookingUrl="getBookingUrl()" />
    </ShowcaseLayout>
</template>

<style scoped>
.reviews-page {
    min-height: 100vh;
    background: var(--provider-background, #fafafa);
}

/* Page Header */
.page-header {
    background: var(--provider-surface, #fff);
    padding: 4rem 0;
    border-bottom: 2px solid var(--provider-text, #1a1a1a);
}

.header-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 2rem;
}

.header-label {
    display: block;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--provider-secondary, #6b7280);
    letter-spacing: 0.15em;
    margin-bottom: 0.75rem;
}

.page-header h1 {
    margin: 0 0 0.75rem 0;
    font-size: clamp(2.5rem, 6vw, 4rem);
    color: var(--provider-text, #1a1a1a);
}

.header-count {
    margin: 0;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    letter-spacing: 0.02em;
}

/* Reviews Content */
.reviews-content {
    padding: 3rem 0 5rem;
}

.content-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Rating Summary */
.rating-summary {
    display: grid;
    grid-template-columns: 240px 1fr;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #e5e5e5);
    margin-bottom: 3rem;
}

.summary-main {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem;
    border-right: 1px solid var(--provider-border, #e5e5e5);
}

.summary-score {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.score-number {
    font-family: var(--font-heading, 'Oswald', sans-serif);
    font-size: 4.5rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    line-height: 1;
    margin-bottom: 0.5rem;
}

:deep(.score-stars .p-rating-icon) {
    font-size: 1.125rem;
    color: var(--provider-border, #d1d5db);
}

:deep(.score-stars .p-rating-icon.p-rating-icon-active) {
    color: var(--provider-warning, #f59e0b);
}

.score-label {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.625rem;
    font-weight: 700;
    color: var(--provider-secondary, #6b7280);
    letter-spacing: 0.1em;
    margin-top: 0.75rem;
}

.summary-distribution {
    padding: 2rem 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.75rem;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.star-label {
    width: 16px;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    text-align: right;
}

.star-icon {
    color: var(--provider-warning, #f59e0b);
    font-size: 0.75rem;
}

.distribution-bar {
    flex: 1;
    height: 8px;
}

:deep(.distribution-bar .p-progressbar-value) {
    background: var(--provider-primary, #1a1a1a);
    border-radius: 0;
}

:deep(.distribution-bar .p-progressbar) {
    background: var(--provider-background, #f5f5f5);
    border-radius: 0;
}

.distribution-count {
    width: 30px;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
    text-align: right;
}

/* Reviews List */
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Load More */
.load-more {
    display: flex;
    justify-content: center;
    padding-top: 1rem;
}

:deep(.load-more-btn) {
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    border-radius: 0 !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    color: var(--provider-primary, #1a1a1a) !important;
    padding: 0.875rem 2rem;
}

:deep(.load-more-btn:hover) {
    background-color: var(--provider-primary-10, rgba(26, 26, 26, 0.1)) !important;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 5rem 2rem;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #e5e5e5);
}

.empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--provider-background, #f5f5f5);
}

.empty-icon i {
    font-size: 2rem;
    color: var(--provider-secondary, #9ca3af);
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.05em;
}

.empty-state p {
    margin: 0 0 2rem 0;
    font-size: 1rem;
    color: var(--provider-secondary, #6b7280);
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

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        padding: 3rem 0;
    }

    .header-container {
        padding: 0 1rem;
    }

    .content-container {
        padding: 0 1rem;
    }

    .reviews-content {
        padding: 2rem 0 6rem;
    }

    .rating-summary {
        grid-template-columns: 1fr;
    }

    .summary-main {
        padding: 2rem;
        border-right: none;
        border-bottom: 1px solid var(--provider-border, #e5e5e5);
    }

    .score-number {
        font-size: 3.5rem;
    }

    .summary-distribution {
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .empty-state {
        padding: 3rem 1.5rem;
    }
}
</style>

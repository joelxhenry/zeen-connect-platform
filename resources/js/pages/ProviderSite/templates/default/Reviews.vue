<script setup lang="ts">
import DefaultLayout from './components/DefaultLayout.vue';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Rating from 'primevue/rating';
import ReviewCard from '@/components/provider/ReviewCard.vue';
import type { ReviewsPageProps } from '@/types/providersite';
import { useProviderSiteReviews } from '@/composables/providersite';

const props = defineProps<ReviewsPageProps>();

const { getDistributionPercentage, hasMorePages, loadMore } = useProviderSiteReviews(props);
</script>

<template>
    <DefaultLayout title="Reviews">
        <div class="reviews-page">
            <div class="page-container">
                <!-- Header with Stats -->
                <div class="page-header">
                    <div class="header-content">
                        <h1>Customer Reviews</h1>
                        <p v-if="reviewStats.total > 0">{{ reviewStats.total }} reviews</p>
                        <p v-else>No reviews yet</p>
                    </div>

                    <!-- Rating Summary Card -->
                    <div v-if="reviewStats.total > 0" class="rating-summary-card">
                        <div class="average-rating">
                            <span class="rating-number">{{ reviewStats.average_display }}</span>
                            <Rating :modelValue="reviewStats.average" readonly :cancel="false" />
                            <span class="rating-count">Based on {{ reviewStats.total }} reviews</span>
                        </div>
                        <div class="rating-distribution">
                            <div v-for="stars in [5, 4, 3, 2, 1]" :key="stars" class="distribution-row">
                                <span class="star-label">{{ stars }} star</span>
                                <ProgressBar :value="getDistributionPercentage(reviewStats.distribution[stars] || 0)"
                                    :showValue="false" class="distribution-bar" />
                                <span class="count">{{ reviewStats.distribution[stars] || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reviews List -->
                <div v-if="reviews.data.length > 0" class="reviews-list">
                    <ReviewCard v-for="review in reviews.data" :key="review.id" :review="review" />

                    <!-- Load More -->
                    <div v-if="hasMorePages" class="load-more">
                        <Button label="Load More Reviews" severity="secondary" outlined @click="loadMore" />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <i class="pi pi-star"></i>
                    <h3>No reviews yet</h3>
                    <p>Be the first to leave a review after your appointment!</p>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

<style scoped>
.reviews-page {
    padding: 2rem 0 4rem;
}

.page-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.page-header {
    margin-bottom: 2rem;
}

.header-content {
    margin-bottom: 1.5rem;
}

.header-content h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--provider-text);
}

.header-content p {
    margin: 0;
    color: #6b7280;
}

.rating-summary-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    display: flex;
    gap: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.average-rating {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding-right: 2rem;
    border-right: 1px solid #e5e7eb;
}

.rating-number {
    font-size: 3rem;
    font-weight: 700;
    color: var(--provider-text);
    line-height: 1;
}

.rating-count {
    font-size: 0.75rem;
    color: #9ca3af;
    text-align: center;
}

.rating-distribution {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.star-label {
    width: 50px;
    font-size: 0.75rem;
    color: #6b7280;
}

.distribution-bar {
    flex: 1;
    height: 8px;
}

.distribution-bar :deep(.p-progressbar-value) {
    background: var(--provider-primary);
}

.distribution-row .count {
    width: 30px;
    text-align: right;
    font-size: 0.75rem;
    color: #9ca3af;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.load-more {
    display: flex;
    justify-content: center;
    padding-top: 1rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-state i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--provider-text);
}

.empty-state p {
    margin: 0;
    color: #6b7280;
}

@media (max-width: 640px) {
    .rating-summary-card {
        flex-direction: column;
    }

    .average-rating {
        padding-right: 0;
        padding-bottom: 1.5rem;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
}
</style>

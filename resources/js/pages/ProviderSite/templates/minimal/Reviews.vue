<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import MinimalLayout from './components/MinimalLayout.vue';
import Button from 'primevue/button';
import Rating from 'primevue/rating';
import type { ReviewsPageProps } from '@/types/providersite';
import { useProviderSiteReviews } from '@/composables/providersite';

const props = defineProps<ReviewsPageProps>();

const { getDistributionPercentage, hasMorePages, loadMore } = useProviderSiteReviews(props);
</script>

<template>
    <MinimalLayout title="Reviews">
        <div class="minimal-reviews">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>Reviews</h1>
                    <p v-if="reviewStats.total > 0">{{ reviewStats.total }} reviews</p>
                    <p v-else>No reviews yet</p>
                </div>

                <!-- Rating Summary -->
                <div v-if="reviewStats.total > 0" class="rating-summary">
                    <div class="rating-main">
                        <span class="rating-number">{{ reviewStats.average_display }}</span>
                        <Rating :modelValue="reviewStats.average" readonly :cancel="false" />
                    </div>
                    <div class="rating-bars">
                        <div v-for="stars in [5, 4, 3, 2, 1]" :key="stars" class="rating-bar-row">
                            <span class="star-label">{{ stars }}</span>
                            <div class="bar-track">
                                <div
                                    class="bar-fill"
                                    :style="{ width: getDistributionPercentage(reviewStats.distribution[stars] || 0) + '%' }"
                                ></div>
                            </div>
                            <span class="star-count">{{ reviewStats.distribution[stars] || 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Reviews List -->
                <div v-if="reviews.data.length > 0" class="reviews-list">
                    <div
                        v-for="review in reviews.data"
                        :key="review.id"
                        class="review-item"
                    >
                        <div class="review-header">
                            <div class="reviewer-info">
                                <span class="reviewer-name">{{ review.client.name }}</span>
                                <span class="review-service">{{ review.service_name }}</span>
                            </div>
                            <div class="review-meta">
                                <Rating :modelValue="review.rating" readonly :cancel="false" class="review-rating" />
                                <span class="review-date">{{ review.time_ago }}</span>
                            </div>
                        </div>

                        <p v-if="review.comment" class="review-comment">{{ review.comment }}</p>

                        <div v-if="review.provider_response" class="provider-response">
                            <span class="response-label">Response from {{ provider.business_name }}</span>
                            <p class="response-text">{{ review.provider_response }}</p>
                        </div>
                    </div>

                    <!-- Load More -->
                    <div v-if="hasMorePages" class="load-more">
                        <Button
                            label="Load More"
                            severity="secondary"
                            outlined
                            @click="loadMore"
                        />
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
    </MinimalLayout>
</template>

<style scoped>
.minimal-reviews {
    min-height: 100%;
    background: #fff;
}

.page-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--provider-text);
}

.page-header p {
    margin: 0;
    color: #6b7280;
}

.rating-summary {
    display: flex;
    gap: 2rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    background: #fafafa;
    border-radius: 0.5rem;
}

.rating-main {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-right: 2rem;
    border-right: 1px solid #e5e7eb;
}

.rating-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--provider-text);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.rating-bars {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.rating-bar-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.star-label {
    width: 1rem;
    font-size: 0.75rem;
    color: #6b7280;
    text-align: right;
}

.bar-track {
    flex: 1;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: var(--provider-primary);
    transition: width 0.3s ease;
}

.star-count {
    width: 1.5rem;
    font-size: 0.75rem;
    color: #9ca3af;
    text-align: right;
}

.reviews-list {
    display: flex;
    flex-direction: column;
}

.review-item {
    padding: 1.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.review-item:first-child {
    padding-top: 0;
}

.review-item:last-child {
    border-bottom: none;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.reviewer-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.reviewer-name {
    font-weight: 500;
    color: var(--provider-text);
}

.review-service {
    font-size: 0.75rem;
    color: #9ca3af;
}

.review-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

:deep(.review-rating) {
    font-size: 0.75rem;
}

.review-date {
    font-size: 0.75rem;
    color: #9ca3af;
}

.review-comment {
    margin: 0;
    font-size: 0.9375rem;
    color: #4b5563;
    line-height: 1.6;
}

.provider-response {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--provider-primary-05);
    border-radius: 0.375rem;
    border-left: 3px solid var(--provider-primary);
}

.response-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--provider-primary);
    margin-bottom: 0.5rem;
}

.response-text {
    margin: 0;
    font-size: 0.875rem;
    color: #4b5563;
    line-height: 1.5;
}

.load-more {
    display: flex;
    justify-content: center;
    padding-top: 1.5rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
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
}

@media (max-width: 640px) {
    .rating-summary {
        flex-direction: column;
    }

    .rating-main {
        padding-right: 0;
        padding-bottom: 1.5rem;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }

    .review-header {
        flex-direction: column;
        gap: 0.5rem;
    }

    .review-meta {
        align-items: flex-start;
        flex-direction: row;
        gap: 0.75rem;
    }
}
</style>

<script setup lang="ts">
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Rating from 'primevue/rating';
import Avatar from 'primevue/avatar';
import type { ReviewsPageProps } from '@/types/providersite';
import { useProviderSiteReviews } from '@/composables/providersite';

const props = defineProps<ReviewsPageProps>();

const { getDistributionPercentage, hasMorePages, loadMore } = useProviderSiteReviews(props);

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <ProviderSiteLayout title="Reviews">
        <div class="reviews-page">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>{{ provider.business_name }}</h1>
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
                        <div
                            v-for="stars in [5, 4, 3, 2, 1]"
                            :key="stars"
                            class="distribution-row"
                        >
                            <span class="star-label">{{ stars }} star</span>
                            <ProgressBar
                                :value="getDistributionPercentage(reviewStats.distribution[stars] || 0)"
                                :showValue="false"
                                class="distribution-bar"
                            />
                            <span class="count">{{ reviewStats.distribution[stars] || 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Reviews List -->
                <div v-if="reviews.data.length > 0" class="reviews-list">
                    <div
                        v-for="review in reviews.data"
                        :key="review.id"
                        class="review-card"
                    >
                        <div class="review-header">
                            <div class="reviewer-info">
                                <Avatar
                                    v-if="review.client.avatar"
                                    :image="review.client.avatar"
                                    shape="circle"
                                    class="reviewer-avatar"
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(review.client.name)"
                                    shape="circle"
                                    class="reviewer-avatar reviewer-avatar--fallback"
                                />
                                <div>
                                    <h4 class="reviewer-name">{{ review.client.name }}</h4>
                                    <span class="review-service">{{ review.service_name }}</span>
                                </div>
                            </div>
                            <div class="review-meta">
                                <Rating :modelValue="review.rating" readonly :cancel="false" />
                                <span class="review-date">{{ review.time_ago }}</span>
                            </div>
                        </div>
                        <p v-if="review.comment" class="review-comment">{{ review.comment }}</p>
                        <div v-if="review.provider_response" class="provider-response">
                            <span class="response-label">Response from business:</span>
                            <p>{{ review.provider_response }}</p>
                        </div>
                    </div>

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
                    <p>Be the first to leave a review after your appointment!</p>
                </div>
            </div>
        </div>
    </ProviderSiteLayout>
</template>

<style scoped>
.reviews-page {
    padding: 2rem 0 4rem;
    background: var(--provider-background, #f9fafb);
    min-height: 100vh;
}

.page-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-text, #1f2937);
}

.page-header p {
    margin: 0;
    color: var(--provider-text-muted, #6b7280);
}

.rating-summary-card {
    background: var(--provider-surface, #fff);
    border-radius: 0.75rem;
    padding: 1.5rem;
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.average-rating {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding-right: 2rem;
    border-right: 1px solid var(--provider-primary, #3b82f6);
}

.rating-number {
    font-size: 3rem;
    font-weight: 700;
    color: var(--provider-primary, #3b82f6);
    line-height: 1;
}

.rating-count {
    font-size: 0.75rem;
    color: var(--provider-text-muted, #6b7280);
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
    color: var(--provider-text-muted, #6b7280);
}

.distribution-bar {
    flex: 1;
    height: 8px;
}

.distribution-bar :deep(.p-progressbar-value) {
    background: var(--provider-primary, #3b82f6);
}

.distribution-bar :deep(.p-progressbar) {
    background: var(--provider-background, #f3f4f6);
}

.distribution-row .count {
    width: 30px;
    text-align: right;
    font-size: 0.75rem;
    color: var(--provider-text-muted, #6b7280);
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.review-card {
    background: var(--provider-surface, #fff);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.reviewer-info {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

:deep(.reviewer-avatar) {
    width: 48px !important;
    height: 48px !important;
}

:deep(.reviewer-avatar--fallback) {
    background: var(--provider-primary, #3b82f6) !important;
    color: #fff !important;
}

.reviewer-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.review-service {
    font-size: 0.75rem;
    color: var(--provider-text-muted, #6b7280);
}

.review-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.review-date {
    font-size: 0.75rem;
    color: var(--provider-text-muted, #6b7280);
}

.review-comment {
    margin: 0;
    font-size: 0.9375rem;
    color: var(--provider-text-muted, #6b7280);
    line-height: 1.6;
}

.provider-response {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--provider-background, #f9fafb);
    border-radius: 0.5rem;
    border-left: 3px solid var(--provider-primary, #3b82f6);
}

.response-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--provider-primary, #3b82f6);
    display: block;
    margin-bottom: 0.5rem;
}

.provider-response p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--provider-text-muted, #6b7280);
    line-height: 1.5;
}

.load-more {
    display: flex;
    justify-content: center;
    padding-top: 1rem;
}

:deep(.load-more-btn) {
    border-color: var(--provider-primary, #3b82f6) !important;
    color: var(--provider-primary, #3b82f6) !important;
}

:deep(.load-more-btn:hover) {
    background: var(--provider-primary-10, rgba(59, 130, 246, 0.1)) !important;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--provider-surface, #fff);
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-state i {
    font-size: 3rem;
    color: var(--provider-text-muted, #9ca3af);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--provider-text, #1f2937);
}

.empty-state p {
    margin: 0;
    color: var(--provider-text-muted, #6b7280);
}

@media (max-width: 640px) {
    .rating-summary-card {
        flex-direction: column;
    }

    .average-rating {
        padding-right: 0;
        padding-bottom: 1.5rem;
        border-right: none;
        border-bottom: 1px solid var(--provider-primary, #3b82f6);
    }

    .review-header {
        flex-direction: column;
        gap: 1rem;
    }

    .review-meta {
        align-items: flex-start;
    }
}
</style>

<script setup lang="ts">
import ArchitectBoldLayout from './components/ArchitectBoldLayout.vue';
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
    <ArchitectBoldLayout title="Reviews">
        <div class="reviews-page">
            <!-- Header -->
            <div class="page-header">
                <div class="header-container">
                    <h1>CLIENT TESTIMONIALS</h1>
                    <p v-if="reviewStats.total > 0">{{ reviewStats.total }} reviews from satisfied clients</p>
                    <p v-else>No reviews yet</p>
                </div>
            </div>

            <!-- Content -->
            <div class="reviews-content">
                <div class="content-container">
                    <!-- Rating Summary Card -->
                    <div v-if="reviewStats.total > 0" class="rating-summary">
                        <div class="summary-grid">
                            <div class="average-block">
                                <span class="rating-number">{{ reviewStats.average_display }}</span>
                                <Rating :modelValue="reviewStats.average" readonly :cancel="false" />
                                <span class="rating-label">AVERAGE RATING</span>
                            </div>
                            <div class="distribution-block">
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
                                    <span class="count">{{ reviewStats.distribution[stars] || 0 }}</span>
                                </div>
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
                                <span class="response-label">RESPONSE FROM BUSINESS:</span>
                                <p>{{ review.provider_response }}</p>
                            </div>
                        </div>

                        <!-- Load More -->
                        <div v-if="hasMorePages" class="load-more">
                            <Button
                                label="LOAD MORE"
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
                        <h3>NO REVIEWS YET</h3>
                        <p>Be the first to leave a review after your appointment!</p>
                    </div>
                </div>
            </div>
        </div>
    </ArchitectBoldLayout>
</template>

<style scoped>
.reviews-page {
    min-height: 100vh;
    background: var(--provider-background, #f5f5f5);
}

/* Header */
.page-header {
    background: var(--provider-surface, #fff);
    padding: 3rem 0;
    border-bottom: 2px solid var(--provider-text, #1a1a1a);
}

.header-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    text-align: center;
}

.page-header h1 {
    margin: 0 0 0.75rem 0;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.05em;
}

.page-header p {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-secondary, #6b7280);
}

/* Content */
.reviews-content {
    padding: 3rem 0 4rem;
}

.content-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Rating Summary */
.rating-summary {
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #e5e5e5);
    margin-bottom: 2rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
}

.average-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 2rem;
    border-right: 1px solid var(--provider-border, #e5e5e5);
}

.rating-number {
    font-size: 4rem;
    font-weight: 700;
    color: var(--provider-primary, #1a1a1a);
    line-height: 1;
}

.rating-label {
    font-size: 0.625rem;
    font-weight: 600;
    color: var(--provider-secondary, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-top: 0.5rem;
}

.distribution-block {
    padding: 1.5rem 2rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.625rem;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.star-label {
    width: 16px;
    font-size: 0.875rem;
    font-weight: 600;
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

.distribution-row .count {
    width: 30px;
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
    text-align: right;
}

/* Reviews List */
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1px;
    background: var(--provider-border, #e5e5e5);
    border: 1px solid var(--provider-border, #e5e5e5);
}

.review-card {
    background: var(--provider-surface, #fff);
    padding: 1.5rem;
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
    border-radius: 0 !important;
}

:deep(.reviewer-avatar--fallback) {
    background: var(--provider-primary, #1a1a1a) !important;
    color: #fff !important;
}

.reviewer-name {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.review-service {
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
}

.review-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.review-date {
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
}

.review-comment {
    margin: 0;
    font-size: 0.9375rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.7;
}

.provider-response {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--provider-background, #f5f5f5);
    border-left: 3px solid var(--provider-primary, #1a1a1a);
}

.response-label {
    font-size: 0.625rem;
    font-weight: 700;
    color: var(--provider-primary, #1a1a1a);
    display: block;
    margin-bottom: 0.5rem;
    letter-spacing: 0.1em;
}

.provider-response p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.6;
}

/* Load More */
.load-more {
    background: var(--provider-surface, #fff);
    display: flex;
    justify-content: center;
    padding: 1.5rem;
}

:deep(.load-more-btn) {
    border-radius: 0 !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    color: var(--provider-primary, #1a1a1a) !important;
    font-weight: 600;
    letter-spacing: 0.1em;
}

:deep(.load-more-btn:hover) {
    background: var(--provider-primary-10, rgba(26, 26, 26, 0.1)) !important;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #e5e5e5);
}

.empty-state i {
    font-size: 3rem;
    color: var(--provider-secondary, #9ca3af);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.05em;
}

.empty-state p {
    margin: 0;
    color: var(--provider-secondary, #6b7280);
}

/* Responsive */
@media (max-width: 640px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }

    .average-block {
        border-right: none;
        border-bottom: 1px solid var(--provider-border, #e5e5e5);
        padding: 1.5rem;
    }

    .rating-number {
        font-size: 3rem;
    }

    .distribution-block {
        padding: 1.5rem;
    }

    .review-header {
        flex-direction: column;
        gap: 1rem;
    }

    .review-meta {
        align-items: flex-start;
    }

    .page-header h1 {
        font-size: 2rem;
    }
}
</style>

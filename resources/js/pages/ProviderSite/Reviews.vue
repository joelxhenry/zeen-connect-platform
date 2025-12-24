<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Rating from 'primevue/rating';
import ReviewCard from '@/components/provider/ReviewCard.vue';

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

interface Props {
    provider: {
        id: number;
        business_name: string;
        slug: string;
        rating_avg: number;
        rating_count: number;
    };
    reviews: {
        data: Review[];
        current_page: number;
        last_page: number;
        total: number;
    };
    reviewStats: {
        total: number;
        average: number;
        average_display: string;
        distribution: Record<number, number>;
    };
}

const props = defineProps<Props>();

const getDistributionPercentage = (count: number) => {
    if (props.reviewStats.total === 0) return 0;
    return Math.round((count / props.reviewStats.total) * 100);
};

const loadMore = () => {
    if (props.reviews.current_page < props.reviews.last_page) {
        router.get('/reviews', { page: props.reviews.current_page + 1 }, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <ProviderSiteLayout title="Reviews">
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
                    <ReviewCard
                        v-for="review in reviews.data"
                        :key="review.id"
                        :review="review"
                    />

                    <!-- Load More -->
                    <div v-if="reviews.current_page < reviews.last_page" class="load-more">
                        <Button
                            label="Load More Reviews"
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
    </ProviderSiteLayout>
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
    color: #0D1F1B;
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
    color: #0D1F1B;
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
    background: #106B4F;
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

.review-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.review-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.reviewer-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.reviewer-info .name {
    font-weight: 500;
    color: #0D1F1B;
}

.reviewer-info .date {
    font-size: 0.875rem;
    color: #9ca3af;
}

.review-rating {
    flex-shrink: 0;
}

.review-comment {
    margin: 0 0 1rem 0;
    color: #4b5563;
    line-height: 1.6;
}

.review-footer {
    display: flex;
    gap: 0.5rem;
}

.service-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    background: #f3f4f6;
    border-radius: 9999px;
    font-size: 0.75rem;
    color: #6b7280;
}

.provider-response {
    margin-top: 1rem;
    padding: 1rem;
    background: #f0fdf4;
    border-radius: 0.5rem;
    border-left: 3px solid #106B4F;
}

.response-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: #166534;
    font-size: 0.875rem;
}

.provider-response p {
    margin: 0;
    color: #166534;
    font-size: 0.875rem;
    line-height: 1.5;
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
    color: #0D1F1B;
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

    .review-header {
        flex-wrap: wrap;
    }

    .review-rating {
        width: 100%;
        margin-top: 0.5rem;
    }
}
</style>

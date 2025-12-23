<script setup lang="ts">
import StarRating from './StarRating.vue';
import Avatar from 'primevue/avatar';

interface Review {
    id: number;
    uuid: string;
    client?: {
        name: string;
        avatar?: string;
    };
    provider?: {
        name: string;
        slug: string;
    };
    service_name: string;
    booking_date: string;
    rating: number;
    comment?: string;
    provider_response?: string;
    provider_responded_at?: string;
    formatted_date: string;
    time_ago: string;
}

defineProps<{
    review: Review;
    showProvider?: boolean;
    showClient?: boolean;
}>();

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <div class="review-card">
        <div class="review-header">
            <div class="reviewer-info" v-if="showClient && review.client">
                <Avatar
                    v-if="review.client.avatar"
                    :image="review.client.avatar"
                    shape="circle"
                    size="normal"
                />
                <Avatar
                    v-else
                    :label="getInitials(review.client.name)"
                    shape="circle"
                    size="normal"
                    class="avatar-placeholder"
                />
                <div class="reviewer-details">
                    <span class="reviewer-name">{{ review.client.name }}</span>
                    <span class="review-date">{{ review.time_ago }}</span>
                </div>
            </div>
            <div class="reviewer-info" v-else-if="showProvider && review.provider">
                <div class="reviewer-details">
                    <span class="reviewer-name">{{ review.provider.name }}</span>
                    <span class="review-meta">{{ review.service_name }} · {{ review.booking_date }}</span>
                </div>
            </div>
            <div class="rating-section">
                <StarRating :model-value="review.rating" readonly size="small" />
            </div>
        </div>

        <div class="review-content" v-if="review.comment">
            <p class="comment">{{ review.comment }}</p>
        </div>

        <div class="provider-response" v-if="review.provider_response">
            <div class="response-header">
                <i class="pi pi-reply"></i>
                <span>Provider Response</span>
                <span class="response-date" v-if="review.provider_responded_at">{{ review.provider_responded_at }}</span>
            </div>
            <p class="response-text">{{ review.provider_response }}</p>
        </div>
    </div>
</template>

<style scoped>
.review-card {
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    padding: 1.25rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.avatar-placeholder {
    background: linear-gradient(135deg, var(--p-primary-100), var(--p-primary-200));
    color: var(--p-primary-600);
}

.reviewer-details {
    display: flex;
    flex-direction: column;
}

.reviewer-name {
    font-weight: 600;
    color: var(--p-surface-900);
}

.review-date,
.review-meta {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.review-content {
    margin-bottom: 1rem;
}

.comment {
    color: var(--p-surface-700);
    line-height: 1.6;
    margin: 0;
}

.provider-response {
    background: var(--p-surface-50);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.response-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--p-surface-600);
    margin-bottom: 0.5rem;
}

.response-header i {
    font-size: 0.75rem;
}

.response-date {
    margin-left: auto;
    font-weight: 400;
    color: var(--p-surface-400);
}

.response-text {
    color: var(--p-surface-600);
    font-size: 0.9375rem;
    line-height: 1.5;
    margin: 0;
}
</style>

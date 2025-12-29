<script setup lang="ts">
import Rating from 'primevue/rating';
import Avatar from 'primevue/avatar';

defineProps<{
    review: {
        id: number;
        client: {
            name: string;
            avatar?: string;
        };
        service_name: string;
        rating: number;
        time_ago: string;
        comment?: string;
        provider_response?: string;
    };
    showDivider?: boolean;
}>();

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <div class="minimal-review" :class="{ 'has-divider': showDivider }">
        <!-- Review Header -->
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
                <div class="reviewer-details">
                    <span class="reviewer-name">{{ review.client.name }}</span>
                    <span class="review-service">{{ review.service_name }}</span>
                </div>
            </div>
            <div class="review-meta">
                <Rating :modelValue="review.rating" readonly :cancel="false" class="review-rating" />
                <span class="review-date">{{ review.time_ago }}</span>
            </div>
        </div>

        <!-- Review Comment -->
        <p v-if="review.comment" class="review-comment">{{ review.comment }}</p>

        <!-- Provider Response -->
        <div v-if="review.provider_response" class="provider-response">
            <span class="response-label">Response from the business</span>
            <p>{{ review.provider_response }}</p>
        </div>
    </div>
</template>

<style scoped>
.minimal-review {
    padding: 2rem 0;
}

.minimal-review.has-divider {
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

/* Review Header */
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

:deep(.reviewer-avatar) {
    width: 44px !important;
    height: 44px !important;
}

:deep(.reviewer-avatar--fallback) {
    background: var(--provider-primary, #8b7355) !important;
    color: #fff !important;
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-weight: 500;
}

.reviewer-details {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.reviewer-name {
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 1.125rem;
    font-weight: 500;
    color: var(--provider-text, #3d3d3d);
}

.review-service {
    font-size: 0.8125rem;
    font-weight: 300;
    color: var(--provider-secondary, #8a8a8a);
}

.review-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.375rem;
}

:deep(.review-rating .p-rating-icon) {
    font-size: 0.75rem;
    color: var(--provider-border, #ebe8e4);
}

:deep(.review-rating .p-rating-icon.p-rating-icon-active) {
    color: var(--provider-warning, #e5b567);
}

.review-date {
    font-size: 0.75rem;
    font-weight: 300;
    color: var(--provider-secondary, #8a8a8a);
}

/* Review Comment */
.review-comment {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 300;
    color: var(--provider-text, #3d3d3d);
    line-height: 1.8;
}

/* Provider Response */
.provider-response {
    margin-top: 1.25rem;
    padding-left: 1.25rem;
    border-left: 2px solid var(--provider-border, #ebe8e4);
}

.response-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--provider-secondary, #8a8a8a);
    margin-bottom: 0.375rem;
    text-transform: lowercase;
    font-style: italic;
}

.provider-response p {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 300;
    color: var(--provider-secondary, #6b6b6b);
    line-height: 1.7;
}

/* Responsive */
@media (max-width: 600px) {
    .minimal-review {
        padding: 1.5rem 0;
    }

    .review-header {
        flex-direction: column;
        gap: 0.75rem;
    }

    .review-meta {
        flex-direction: row;
        align-items: center;
        gap: 1rem;
    }

    :deep(.reviewer-avatar) {
        width: 40px !important;
        height: 40px !important;
    }

    .reviewer-name {
        font-size: 1rem;
    }
}
</style>

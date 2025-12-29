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
}>();

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <div class="bubble-review-card">
        <!-- Quote Bubble -->
        <div class="quote-bubble">
            <div class="quote-mark">"</div>
            <p v-if="review.comment" class="quote-text">"{{ review.comment }}"</p>
            <p v-else class="quote-text quote-text--empty">"Great experience!"</p>
        </div>

        <!-- Bubble Tail -->
        <div class="bubble-tail"></div>

        <!-- Reviewer Info -->
        <div class="reviewer-section">
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
                <div class="review-meta">
                    <Rating :modelValue="review.rating" readonly :cancel="false" class="review-rating" />
                    <span class="review-date">{{ review.time_ago }}</span>
                </div>
            </div>
        </div>

        <!-- Provider Response -->
        <div v-if="review.provider_response" class="provider-response">
            <div class="response-header">
                <i class="pi pi-reply"></i>
                <span>Business Response</span>
            </div>
            <p>{{ review.provider_response }}</p>
        </div>
    </div>
</template>

<style scoped>
.bubble-review-card {
    position: relative;
}

/* Quote Bubble */
.quote-bubble {
    position: relative;
    background: var(--provider-surface, #fff);
    border-radius: 1.5rem;
    padding: 2rem 2rem 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--provider-border, #e5e5e5);
}

/* Large decorative quote mark */
.quote-mark {
    position: absolute;
    top: -0.5rem;
    left: 1.5rem;
    font-family: var(--font-heading, 'Oswald', sans-serif);
    font-size: 5rem;
    font-weight: 700;
    line-height: 1;
    color: var(--provider-primary, #1a1a1a);
    opacity: 0.15;
    user-select: none;
    pointer-events: none;
}

.quote-text {
    margin: 0;
    font-size: 1.125rem;
    font-style: italic;
    color: var(--provider-text, #1a1a1a);
    line-height: 1.7;
    position: relative;
    z-index: 1;
}

.quote-text--empty {
    color: var(--provider-secondary, #6b7280);
}

/* Speech bubble tail (triangle) */
.bubble-tail {
    position: relative;
    width: 0;
    height: 0;
    margin-left: 2.5rem;
    margin-top: -1px;
    border-left: 12px solid transparent;
    border-right: 12px solid transparent;
    border-top: 16px solid var(--provider-surface, #fff);
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.04));
}

.bubble-tail::before {
    content: '';
    position: absolute;
    top: -18px;
    left: -13px;
    width: 0;
    height: 0;
    border-left: 13px solid transparent;
    border-right: 13px solid transparent;
    border-top: 17px solid var(--provider-border, #e5e5e5);
    z-index: -1;
}

/* Reviewer Section */
.reviewer-section {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-top: 1rem;
    padding-left: 1rem;
}

:deep(.reviewer-avatar) {
    width: 56px !important;
    height: 56px !important;
    flex-shrink: 0;
    border: 2px solid var(--provider-border, #e5e5e5);
}

:deep(.reviewer-avatar--fallback) {
    background: var(--provider-primary, #1a1a1a) !important;
    color: #fff !important;
    font-family: var(--font-heading, 'Oswald', sans-serif);
    font-weight: 600;
}

.reviewer-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.reviewer-name {
    font-family: var(--font-heading, 'Oswald', sans-serif);
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.review-service {
    font-size: 0.8125rem;
    color: var(--provider-secondary, #6b7280);
}

.review-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.25rem;
}

:deep(.review-rating .p-rating-icon) {
    font-size: 0.875rem;
    color: var(--provider-secondary, #d1d5db);
}

:deep(.review-rating .p-rating-icon.p-rating-icon-active) {
    color: var(--provider-warning, #f59e0b);
}

.review-date {
    font-size: 0.75rem;
    color: var(--provider-secondary, #9ca3af);
}

/* Provider Response */
.provider-response {
    margin-top: 1.5rem;
    margin-left: 1rem;
    padding: 1rem 1.25rem;
    background: var(--provider-background, #f9fafb);
    border-radius: 0.75rem;
    border-left: 3px solid var(--provider-primary, #1a1a1a);
}

.response-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--provider-primary, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.response-header i {
    font-size: 0.75rem;
}

.provider-response p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 640px) {
    .quote-bubble {
        padding: 1.5rem 1.5rem 1.25rem;
        border-radius: 1.25rem;
    }

    .quote-mark {
        font-size: 4rem;
        top: -0.25rem;
        left: 1rem;
    }

    .quote-text {
        font-size: 1rem;
    }

    .reviewer-section {
        padding-left: 0.5rem;
    }

    :deep(.reviewer-avatar) {
        width: 48px !important;
        height: 48px !important;
    }

    .provider-response {
        margin-left: 0.5rem;
    }
}
</style>

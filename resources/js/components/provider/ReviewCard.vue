<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Rating from 'primevue/rating';

interface Client {
    name: string;
    avatar?: string;
}

interface Review {
    id: number;
    uuid: string;
    client: Client;
    service_name: string;
    rating: number;
    comment?: string;
    provider_response?: string;
    time_ago: string;
}

interface Props {
    review: Review;
    showResponse?: boolean;
}

withDefaults(defineProps<Props>(), {
    showResponse: true,
});

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <div class="review-card">
        <div class="review-card__header">
            <Avatar
                v-if="review.client.avatar"
                :image="review.client.avatar"
                shape="circle"
                class="!w-10 !h-10"
            />
            <Avatar
                v-else
                :label="getInitials(review.client.name)"
                shape="circle"
                class="!w-10 !h-10 !bg-gray-200 !text-gray-600"
            />
            <div class="review-card__reviewer">
                <span class="review-card__name">{{ review.client.name }}</span>
                <span class="review-card__date">{{ review.time_ago }}</span>
            </div>
            <Rating :modelValue="review.rating" readonly :cancel="false" class="!ml-auto" />
        </div>

        <p v-if="review.comment" class="review-card__comment">
            {{ review.comment }}
        </p>

        <p class="review-card__service">
            <i class="pi pi-tag"></i>
            {{ review.service_name }}
        </p>

        <div v-if="showResponse && review.provider_response" class="review-card__response">
            <div class="review-card__response-header">
                <i class="pi pi-reply"></i>
                <span>Provider Response</span>
            </div>
            <p>{{ review.provider_response }}</p>
        </div>
    </div>
</template>

<style scoped>
.review-card {
    background: var(--provider-surface, #ffffff);
    padding: 1.25rem;
    border-radius: 0.75rem;
    box-shadow: var(--provider-shadow-sm, 0 1px 3px rgba(0, 0, 0, 0.08));
}

.review-card__header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.review-card__reviewer {
    display: flex;
    flex-direction: column;
}

.review-card__name {
    font-weight: 500;
    color: var(--provider-text, #0D1F1B);
}

.review-card__date {
    font-size: 0.75rem;
    color: var(--provider-text-subtle, #9ca3af);
}

.review-card__comment {
    margin: 0 0 0.75rem 0;
    color: var(--provider-text-body, #4b5563);
    font-size: 0.9375rem;
    line-height: 1.6;
}

.review-card__service {
    margin: 0;
    font-size: 0.75rem;
    color: var(--provider-text-subtle, #9ca3af);
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.review-card__service i {
    font-size: 0.6875rem;
}

.review-card__response {
    margin-top: 1rem;
    padding: 0.875rem;
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border-radius: 0.5rem;
    border-left: 3px solid var(--provider-primary, #106B4F);
}

.review-card__response-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--provider-primary, #106B4F);
    margin-bottom: 0.5rem;
}

.review-card__response-header i {
    font-size: 0.75rem;
}

.review-card__response p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--provider-text, #0D1F1B);
    line-height: 1.5;
}
</style>

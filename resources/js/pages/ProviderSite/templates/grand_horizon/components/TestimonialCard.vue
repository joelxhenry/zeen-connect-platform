<script setup lang="ts">
import Rating from 'primevue/rating';

interface Review {
    id: number;
    rating: number;
    comment: string | null;
    created_at: string;
    client?: {
        name: string;
        avatar?: string;
    };
    service?: {
        name: string;
    };
}

defineProps<{
    review: Review;
    variant?: 'default' | 'featured';
}>();

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <div class="testimonial" :class="{ 'testimonial--featured': variant === 'featured' }">
        <!-- Decorative Quote -->
        <div class="testimonial-quote-mark">"</div>

        <!-- Quote Text -->
        <blockquote v-if="review.comment" class="testimonial-quote">
            {{ review.comment }}
        </blockquote>

        <!-- Rating -->
        <div class="testimonial-rating">
            <Rating :modelValue="review.rating" readonly :cancel="false" />
        </div>

        <!-- Author Info -->
        <div class="testimonial-author">
            <div v-if="review.client?.avatar" class="author-avatar">
                <img :src="review.client.avatar" :alt="review.client?.name" />
            </div>
            <div class="author-details">
                <span class="author-name">{{ review.client?.name || 'Guest' }}</span>
                <span class="author-meta">
                    <template v-if="review.service">{{ review.service.name }} &middot; </template>
                    {{ formatDate(review.created_at) }}
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.testimonial {
    text-align: center;
    padding: 3rem 2rem;
    max-width: 800px;
    margin: 0 auto;
}

.testimonial--featured {
    padding: 5rem 2rem;
}

.testimonial-quote-mark {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 6rem;
    line-height: 1;
    color: var(--provider-primary, #c9a87c);
    opacity: 0.3;
    margin-bottom: -2rem;
}

.testimonial--featured .testimonial-quote-mark {
    font-size: 8rem;
    margin-bottom: -3rem;
}

.testimonial-quote {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.5rem;
    font-style: italic;
    font-weight: 400;
    line-height: 1.7;
    color: var(--provider-text, #1a1a1a);
    margin: 0 0 2rem 0;
}

.testimonial--featured .testimonial-quote {
    font-size: 1.875rem;
}

.testimonial-rating {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}

:deep(.testimonial-rating .p-rating-icon) {
    font-size: 0.875rem;
    color: var(--provider-border, #e5e0d8);
}

:deep(.testimonial-rating .p-rating-icon.p-rating-icon-active) {
    color: var(--provider-primary, #c9a87c);
}

.testimonial-author {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

.author-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    overflow: hidden;
}

.author-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.author-name {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.8125rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--provider-text, #1a1a1a);
}

.author-meta {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 400;
    letter-spacing: 0.05em;
    color: var(--provider-secondary, #6a6a6a);
}

/* Responsive */
@media (max-width: 768px) {
    .testimonial {
        padding: 2rem 1rem;
    }

    .testimonial--featured {
        padding: 3rem 1rem;
    }

    .testimonial-quote-mark {
        font-size: 4rem;
        margin-bottom: -1.5rem;
    }

    .testimonial--featured .testimonial-quote-mark {
        font-size: 5rem;
        margin-bottom: -2rem;
    }

    .testimonial-quote {
        font-size: 1.25rem;
    }

    .testimonial--featured .testimonial-quote {
        font-size: 1.375rem;
    }

    .author-avatar {
        width: 48px;
        height: 48px;
    }
}
</style>

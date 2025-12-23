<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/components/layout/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import StarRating from '@/components/reviews/StarRating.vue';

interface Booking {
    uuid: string;
    provider: {
        name: string;
        slug: string;
    };
    service_name: string;
    formatted_date: string;
    formatted_time: string;
}

const props = defineProps<{
    booking: Booking;
}>();

const form = useForm({
    rating: 0,
    comment: '',
});

const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

const submit = () => {
    form.post(route('client.reviews.store', props.booking.uuid));
};
</script>

<template>
    <DashboardLayout>
        <Head title="Write a Review" />

        <div class="review-page">
            <div class="page-header">
                <Link :href="route('client.bookings.show', booking.uuid)" class="back-link">
                    <i class="pi pi-arrow-left"></i>
                    Back to booking
                </Link>
                <h1 class="page-title">Write a Review</h1>
            </div>

            <div class="review-container">
                <!-- Booking Info -->
                <Card class="booking-info-card">
                    <template #content>
                        <div class="booking-info">
                            <div class="provider-name">{{ booking.provider.name }}</div>
                            <div class="service-name">{{ booking.service_name }}</div>
                            <div class="booking-date">
                                <i class="pi pi-calendar"></i>
                                {{ booking.formatted_date }} at {{ booking.formatted_time }}
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Review Form -->
                <Card class="review-form-card">
                    <template #content>
                        <form @submit.prevent="submit" class="review-form">
                            <div class="rating-section">
                                <label class="section-label">How was your experience?</label>
                                <div class="rating-input">
                                    <StarRating v-model="form.rating" size="large" />
                                    <span class="rating-label" v-if="form.rating > 0">
                                        {{ ratingLabels[form.rating] }}
                                    </span>
                                </div>
                                <small class="error" v-if="form.errors.rating">{{ form.errors.rating }}</small>
                            </div>

                            <div class="comment-section">
                                <label class="section-label">Share your experience (optional)</label>
                                <Textarea
                                    v-model="form.comment"
                                    rows="5"
                                    placeholder="Tell others about your experience with this provider..."
                                    class="comment-input"
                                    :class="{ 'p-invalid': form.errors.comment }"
                                />
                                <div class="textarea-footer">
                                    <small class="error" v-if="form.errors.comment">{{ form.errors.comment }}</small>
                                    <small class="char-count">{{ form.comment.length }}/2000</small>
                                </div>
                            </div>

                            <div class="form-actions">
                                <Link :href="route('client.bookings.show', booking.uuid)">
                                    <Button
                                        type="button"
                                        label="Cancel"
                                        severity="secondary"
                                        outlined
                                    />
                                </Link>
                                <Button
                                    type="submit"
                                    label="Submit Review"
                                    icon="pi pi-check"
                                    :loading="form.processing"
                                    :disabled="form.rating === 0"
                                />
                            </div>
                        </form>
                    </template>
                </Card>

                <!-- Review Guidelines -->
                <Card class="guidelines-card">
                    <template #content>
                        <div class="guidelines">
                            <h3><i class="pi pi-info-circle"></i> Review Guidelines</h3>
                            <ul>
                                <li>Be honest and helpful to other customers</li>
                                <li>Focus on your actual experience with the service</li>
                                <li>Avoid personal attacks or inappropriate language</li>
                                <li>Your review will be visible on the provider's profile</li>
                            </ul>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.review-page {
    max-width: 600px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 1.5rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--p-surface-600);
    text-decoration: none;
    font-size: 0.875rem;
    margin-bottom: 1rem;
    transition: color 0.2s;
}

.back-link:hover {
    color: var(--p-primary-color);
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.review-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.booking-info-card,
.review-form-card,
.guidelines-card {
    border-radius: 12px;
}

.booking-info {
    text-align: center;
}

.provider-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin-bottom: 0.25rem;
}

.service-name {
    color: var(--p-surface-600);
    margin-bottom: 0.75rem;
}

.booking-date {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--p-surface-500);
}

.review-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.section-label {
    display: block;
    font-weight: 600;
    color: var(--p-surface-900);
    margin-bottom: 0.75rem;
}

.rating-section {
    text-align: center;
}

.rating-input {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.rating-label {
    font-size: 1rem;
    font-weight: 500;
    color: var(--p-primary-color);
}

.comment-input {
    width: 100%;
}

.textarea-footer {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
}

.char-count {
    color: var(--p-surface-400);
}

.error {
    color: var(--p-red-500);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--p-surface-200);
}

.guidelines {
    color: var(--p-surface-600);
}

.guidelines h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--p-surface-700);
    margin: 0 0 0.75rem 0;
}

.guidelines ul {
    margin: 0;
    padding-left: 1.25rem;
}

.guidelines li {
    font-size: 0.875rem;
    padding: 0.25rem 0;
}
</style>

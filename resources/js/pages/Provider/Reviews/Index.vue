<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Paginator from 'primevue/paginator';
import StarRating from '@/components/reviews/StarRating.vue';
import ReviewCard from '@/components/reviews/ReviewCard.vue';

interface Review {
    id: number;
    uuid: string;
    client: {
        name: string;
        avatar?: string;
    };
    service_name: string;
    booking_uuid: string;
    booking_date: string;
    rating: number;
    rating_stars: string;
    comment?: string;
    provider_response?: string;
    provider_responded_at?: string;
    can_respond: boolean;
    is_visible: boolean;
    formatted_date: string;
    time_ago: string;
}

interface Stats {
    total: number;
    average: number;
    average_display: string;
    distribution: Record<number, number>;
}

interface PaginatedReviews {
    data: Review[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    reviews: PaginatedReviews;
    stats: Stats;
    currentFilter: string;
}>();

const filterOptions = [
    { label: 'All Reviews', value: 'all' },
    { label: '5 Stars', value: '5' },
    { label: '4 Stars', value: '4' },
    { label: '3 Stars', value: '3' },
    { label: '2 Stars', value: '2' },
    { label: '1 Star', value: '1' },
];

const selectedFilter = ref(props.currentFilter);
const showResponseDialog = ref(false);
const selectedReview = ref<Review | null>(null);

const responseForm = useForm({
    response: '',
});

const applyFilter = () => {
    router.get(route('provider.reviews.index'), { filter: selectedFilter.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const openResponseDialog = (review: Review) => {
    selectedReview.value = review;
    responseForm.response = '';
    showResponseDialog.value = true;
};

const submitResponse = () => {
    if (!selectedReview.value) return;

    responseForm.post(route('provider.reviews.respond', selectedReview.value.uuid), {
        onSuccess: () => {
            showResponseDialog.value = false;
            selectedReview.value = null;
        },
    });
};

const getDistributionPercentage = (count: number): number => {
    if (props.stats.total === 0) return 0;
    return (count / props.stats.total) * 100;
};
</script>

<template>
    <ConsoleLayout>
        <Head title="Reviews" />

        <div class="reviews-page">
            <div class="page-header">
                <h1 class="page-title">Reviews</h1>
            </div>

            <div class="reviews-layout">
                <!-- Stats Sidebar -->
                <aside class="stats-sidebar">
                    <Card class="stats-card">
                        <template #content>
                            <div class="overall-rating">
                                <div class="rating-value">{{ stats.average_display }}</div>
                                <StarRating :model-value="Math.round(stats.average)" readonly />
                                <div class="total-reviews">{{ stats.total }} {{ stats.total === 1 ? 'review' : 'reviews' }}</div>
                            </div>

                            <div class="rating-distribution">
                                <div
                                    v-for="(count, rating) in stats.distribution"
                                    :key="rating"
                                    class="distribution-row"
                                >
                                    <span class="star-label">{{ rating }}</span>
                                    <i class="pi pi-star-fill star-icon"></i>
                                    <div class="bar-container">
                                        <div
                                            class="bar-fill"
                                            :style="{ width: `${getDistributionPercentage(count)}%` }"
                                        ></div>
                                    </div>
                                    <span class="count">{{ count }}</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="filter-card">
                        <template #content>
                            <label class="filter-label">Filter by Rating</label>
                            <Select
                                v-model="selectedFilter"
                                :options="filterOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                                @change="applyFilter"
                            />
                        </template>
                    </Card>
                </aside>

                <!-- Reviews List -->
                <div class="reviews-main">
                    <div v-if="reviews.data.length === 0" class="empty-state">
                        <i class="pi pi-star"></i>
                        <h3>No reviews yet</h3>
                        <p>Reviews from your clients will appear here</p>
                    </div>

                    <div v-else class="reviews-list">
                        <div v-for="review in reviews.data" :key="review.id" class="review-item">
                            <ReviewCard :review="review" show-client />
                            <div class="review-actions" v-if="review.can_respond">
                                <Button
                                    label="Respond"
                                    icon="pi pi-reply"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    @click="openResponseDialog(review)"
                                />
                            </div>
                        </div>
                    </div>

                    <Paginator
                        v-if="reviews.last_page > 1"
                        :rows="reviews.per_page"
                        :totalRecords="reviews.total"
                        :first="(reviews.current_page - 1) * reviews.per_page"
                        class="mt-4"
                    />
                </div>
            </div>
        </div>

        <!-- Response Dialog -->
        <Dialog
            v-model:visible="showResponseDialog"
            modal
            header="Respond to Review"
            :style="{ width: '500px' }"
        >
            <div class="response-dialog-content" v-if="selectedReview">
                <div class="original-review">
                    <div class="review-meta">
                        <span class="client-name">{{ selectedReview.client.name }}</span>
                        <StarRating :model-value="selectedReview.rating" readonly size="small" />
                    </div>
                    <p class="review-comment" v-if="selectedReview.comment">{{ selectedReview.comment }}</p>
                </div>

                <div class="response-form">
                    <label>Your Response</label>
                    <Textarea
                        v-model="responseForm.response"
                        rows="4"
                        placeholder="Write a professional response..."
                        class="w-full"
                        :class="{ 'p-invalid': responseForm.errors.response }"
                    />
                    <small class="error" v-if="responseForm.errors.response">
                        {{ responseForm.errors.response }}
                    </small>
                    <small class="hint">Your response will be visible publicly on your profile.</small>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    outlined
                    @click="showResponseDialog = false"
                />
                <Button
                    label="Submit Response"
                    icon="pi pi-check"
                    :loading="responseForm.processing"
                    :disabled="!responseForm.response.trim()"
                    @click="submitResponse"
                />
            </template>
        </Dialog>
    </ConsoleLayout>
</template>

<style scoped>
.reviews-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.reviews-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.5rem;
}

.stats-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stats-card,
.filter-card {
    border-radius: 12px;
}

.overall-rating {
    text-align: center;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--p-surface-200);
    margin-bottom: 1.25rem;
}

.rating-value {
    font-size: 3rem;
    font-weight: 700;
    color: var(--p-surface-900);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.total-reviews {
    font-size: 0.875rem;
    color: var(--p-surface-500);
    margin-top: 0.5rem;
}

.rating-distribution {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.star-label {
    width: 12px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-700);
}

.star-icon {
    color: #fbbf24;
    font-size: 0.75rem;
}

.bar-container {
    flex: 1;
    height: 8px;
    background: var(--p-surface-200);
    border-radius: 4px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: #fbbf24;
    border-radius: 4px;
    transition: width 0.3s;
}

.count {
    width: 24px;
    text-align: right;
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.filter-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-700);
    margin-bottom: 0.5rem;
}

.reviews-main {
    min-height: 400px;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    background: var(--p-surface-50);
    border-radius: 12px;
}

.empty-state i {
    font-size: 3rem;
    color: var(--p-surface-300);
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: var(--p-surface-700);
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    color: var(--p-surface-500);
    margin: 0;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.review-item {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.review-actions {
    display: flex;
    justify-content: flex-end;
}

.response-dialog-content {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.original-review {
    background: var(--p-surface-50);
    padding: 1rem;
    border-radius: 8px;
}

.review-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.client-name {
    font-weight: 600;
    color: var(--p-surface-900);
}

.review-comment {
    color: var(--p-surface-600);
    font-size: 0.9375rem;
    margin: 0;
}

.response-form label {
    display: block;
    font-weight: 500;
    color: var(--p-surface-700);
    margin-bottom: 0.5rem;
}

.error {
    color: var(--p-red-500);
    display: block;
    margin-top: 0.25rem;
}

.hint {
    color: var(--p-surface-400);
    display: block;
    margin-top: 0.5rem;
}

@media (max-width: 900px) {
    .reviews-layout {
        grid-template-columns: 1fr;
    }

    .stats-sidebar {
        flex-direction: row;
    }

    .stats-card,
    .filter-card {
        flex: 1;
    }
}

@media (max-width: 640px) {
    .stats-sidebar {
        flex-direction: column;
    }
}
</style>

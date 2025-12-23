<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

interface Review {
    id: number;
    uuid: string;
    client: {
        name: string;
        email: string;
        avatar: string | null;
    };
    provider: {
        business_name: string;
    };
    service: {
        name: string;
    };
    rating: number;
    comment: string;
    has_response: boolean;
    is_visible: boolean;
    is_flagged: boolean;
    flag_reason: string | null;
    created_at: string;
}

interface PaginatedReviews {
    data: Review[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    reviews: PaginatedReviews;
    stats: {
        total: number;
        average_rating: number;
        flagged_count: number;
        hidden_count: number;
    };
    filters: {
        search: string | null;
        rating: number | null;
        visibility: string | null;
        flagged: string | null;
        sort: string;
        dir: string;
    };
}>();

const confirm = useConfirm();

const search = ref(props.filters.search || '');
const selectedRating = ref(props.filters.rating?.toString() || '');
const selectedVisibility = ref(props.filters.visibility || '');
const selectedFlagged = ref(props.filters.flagged || '');

const showFlagDialog = ref(false);
const selectedReview = ref<Review | null>(null);
const flagReason = ref('');

const ratingOptions = [
    { value: '', label: 'All Ratings' },
    { value: '5', label: '5 Stars' },
    { value: '4', label: '4 Stars' },
    { value: '3', label: '3 Stars' },
    { value: '2', label: '2 Stars' },
    { value: '1', label: '1 Star' },
];

const visibilityOptions = [
    { value: '', label: 'All' },
    { value: 'visible', label: 'Visible' },
    { value: 'hidden', label: 'Hidden' },
];

const flaggedOptions = [
    { value: '', label: 'All' },
    { value: 'yes', label: 'Flagged' },
    { value: 'no', label: 'Not Flagged' },
];

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('admin.reviews.index'), {
            search: search.value || undefined,
            rating: selectedRating.value || undefined,
            visibility: selectedVisibility.value || undefined,
            flagged: selectedFlagged.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch([search, selectedRating, selectedVisibility, selectedFlagged], applyFilters);

const getStars = (rating: number): string => {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const toggleVisibility = (review: Review) => {
    const action = review.is_visible ? 'hide' : 'show';
    confirm.require({
        message: `Are you sure you want to ${action} this review?`,
        header: `${action.charAt(0).toUpperCase() + action.slice(1)} Review`,
        icon: review.is_visible ? 'pi pi-eye-slash' : 'pi pi-eye',
        accept: () => {
            router.post(route('admin.reviews.toggle-visibility', review.uuid), {}, {
                preserveScroll: true,
            });
        },
    });
};

const openFlagDialog = (review: Review) => {
    selectedReview.value = review;
    flagReason.value = '';
    showFlagDialog.value = true;
};

const flagReview = () => {
    if (selectedReview.value && flagReason.value) {
        router.post(route('admin.reviews.flag', selectedReview.value.uuid), {
            reason: flagReason.value,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showFlagDialog.value = false;
                selectedReview.value = null;
            },
        });
    }
};

const unflagReview = (review: Review) => {
    confirm.require({
        message: 'Are you sure you want to unflag this review?',
        header: 'Unflag Review',
        icon: 'pi pi-flag',
        accept: () => {
            router.post(route('admin.reviews.unflag', review.uuid), {}, {
                preserveScroll: true,
            });
        },
    });
};

const deleteReview = (review: Review) => {
    confirm.require({
        message: 'Are you sure you want to delete this review? This action cannot be undone.',
        header: 'Delete Review',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.reviews.destroy', review.uuid), {
                preserveScroll: true,
            });
        },
    });
};
</script>

<template>
    <AdminLayout title="Reviews">
        <Head title="Review Management" />

        <ConfirmDialog />

        <Dialog
            v-model:visible="showFlagDialog"
            modal
            header="Flag Review"
            :style="{ width: '450px' }"
        >
            <div class="flag-dialog">
                <p>Flag this review for moderation</p>
                <div class="form-field">
                    <label>Reason *</label>
                    <Textarea
                        v-model="flagReason"
                        placeholder="Enter reason for flagging this review..."
                        rows="4"
                        class="w-full"
                    />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showFlagDialog = false" />
                <Button label="Flag Review" severity="warning" @click="flagReview" :disabled="!flagReason" />
            </template>
        </Dialog>

        <div class="reviews-page">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon primary">
                                <i class="pi pi-star-fill"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.average_rating }}</span>
                                <span class="stat-label">Average Rating</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon info">
                                <i class="pi pi-comments"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.total }}</span>
                                <span class="stat-label">Total Reviews</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon warning">
                                <i class="pi pi-flag"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.flagged_count }}</span>
                                <span class="stat-label">Flagged Reviews</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon secondary">
                                <i class="pi pi-eye-slash"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.hidden_count }}</span>
                                <span class="stat-label">Hidden Reviews</span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <Card>
                <template #title>
                    <div class="card-header">
                        <div class="header-left">
                            <h2>Review Management</h2>
                            <Tag :value="`${reviews.total} reviews`" severity="secondary" />
                        </div>
                    </div>
                </template>

                <template #content>
                    <!-- Filters -->
                    <div class="filters">
                        <div class="search-box">
                            <i class="pi pi-search"></i>
                            <InputText
                                v-model="search"
                                placeholder="Search by client, provider, or comment..."
                                class="search-input"
                            />
                        </div>

                        <Select
                            v-model="selectedRating"
                            :options="ratingOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Rating"
                            class="filter-select"
                        />

                        <Select
                            v-model="selectedVisibility"
                            :options="visibilityOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Visibility"
                            class="filter-select"
                        />

                        <Select
                            v-model="selectedFlagged"
                            :options="flaggedOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Flagged"
                            class="filter-select"
                        />
                    </div>

                    <!-- Reviews Table -->
                    <DataTable
                        :value="reviews.data"
                        :paginator="false"
                        :rows="20"
                        class="reviews-table"
                        stripedRows
                    >
                        <Column header="Client" style="min-width: 180px">
                            <template #body="{ data }">
                                <div class="client-cell">
                                    <Avatar
                                        v-if="data.client.avatar"
                                        :image="data.client.avatar"
                                        shape="circle"
                                        size="normal"
                                    />
                                    <Avatar
                                        v-else
                                        :label="getInitials(data.client.name)"
                                        shape="circle"
                                        size="normal"
                                        class="client-avatar"
                                    />
                                    <div class="client-info">
                                        <span class="client-name">{{ data.client.name }}</span>
                                        <span class="client-email">{{ data.client.email }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Provider">
                            <template #body="{ data }">
                                {{ data.provider.business_name }}
                            </template>
                        </Column>

                        <Column header="Service">
                            <template #body="{ data }">
                                {{ data.service.name }}
                            </template>
                        </Column>

                        <Column header="Rating">
                            <template #body="{ data }">
                                <span class="rating">{{ getStars(data.rating) }}</span>
                            </template>
                        </Column>

                        <Column header="Comment" style="max-width: 250px">
                            <template #body="{ data }">
                                <span class="comment">{{ data.comment }}</span>
                            </template>
                        </Column>

                        <Column header="Status">
                            <template #body="{ data }">
                                <div class="status-badges">
                                    <Tag v-if="data.is_flagged" severity="warning" value="Flagged" />
                                    <Tag v-if="!data.is_visible" severity="secondary" value="Hidden" />
                                    <Tag v-if="data.has_response" severity="info" value="Has Response" />
                                </div>
                            </template>
                        </Column>

                        <Column header="Date">
                            <template #body="{ data }">
                                {{ data.created_at }}
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 150px">
                            <template #body="{ data }">
                                <div class="action-buttons">
                                    <Link :href="route('admin.reviews.show', data.uuid)">
                                        <Button
                                            icon="pi pi-eye"
                                            text
                                            rounded
                                            size="small"
                                            v-tooltip.top="'View'"
                                        />
                                    </Link>
                                    <Button
                                        :icon="data.is_visible ? 'pi pi-eye-slash' : 'pi pi-eye'"
                                        text
                                        rounded
                                        size="small"
                                        @click="toggleVisibility(data)"
                                        v-tooltip.top="data.is_visible ? 'Hide' : 'Show'"
                                    />
                                    <Button
                                        v-if="!data.is_flagged"
                                        icon="pi pi-flag"
                                        text
                                        rounded
                                        size="small"
                                        severity="warning"
                                        @click="openFlagDialog(data)"
                                        v-tooltip.top="'Flag'"
                                    />
                                    <Button
                                        v-else
                                        icon="pi pi-flag-fill"
                                        text
                                        rounded
                                        size="small"
                                        severity="warning"
                                        @click="unflagReview(data)"
                                        v-tooltip.top="'Unflag'"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        text
                                        rounded
                                        size="small"
                                        severity="danger"
                                        @click="deleteReview(data)"
                                        v-tooltip.top="'Delete'"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="pagination" v-if="reviews.last_page > 1">
                        <Link
                            v-for="link in reviews.links"
                            :key="link.label"
                            :href="link.url || ''"
                            :class="['page-link', { active: link.active, disabled: !link.url }]"
                            v-html="link.label"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </AdminLayout>
</template>

<style scoped>
.reviews-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.stat-card :deep(.p-card-body) {
    padding: 1rem;
}

.stat-card :deep(.p-card-content) {
    padding: 0;
}

.stat-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.stat-icon.primary {
    background-color: var(--p-yellow-100);
    color: var(--p-yellow-600);
}

.stat-icon.info {
    background-color: var(--p-blue-100);
    color: var(--p-blue-600);
}

.stat-icon.warning {
    background-color: var(--p-orange-100);
    color: var(--p-orange-600);
}

.stat-icon.secondary {
    background-color: var(--p-surface-100);
    color: var(--p-surface-600);
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--p-surface-900);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-left h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--p-surface-400);
}

.search-input {
    width: 100%;
    padding-left: 2.5rem;
}

.filter-select {
    min-width: 130px;
}

.client-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.client-avatar {
    background-color: var(--p-primary-color);
    color: white;
}

.client-info {
    display: flex;
    flex-direction: column;
}

.client-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.client-email {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.rating {
    color: var(--p-yellow-500);
    font-size: 1rem;
    letter-spacing: 2px;
}

.comment {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: 0.875rem;
    color: var(--p-surface-600);
}

.status-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.flag-dialog {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.flag-dialog p {
    margin: 0;
    color: var(--p-surface-600);
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-field label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-700);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
    margin-top: 1.5rem;
}

.page-link {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--p-surface-200);
    border-radius: 6px;
    text-decoration: none;
    color: var(--p-surface-700);
    font-size: 0.875rem;
    transition: all 0.2s;
}

.page-link:hover:not(.disabled) {
    background-color: var(--p-surface-100);
}

.page-link.active {
    background-color: var(--p-red-500);
    border-color: var(--p-red-500);
    color: white;
}

.page-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .filters {
        flex-direction: column;
    }

    .search-box,
    .filter-select {
        width: 100%;
    }
}
</style>

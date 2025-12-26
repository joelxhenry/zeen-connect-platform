<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import StatCard from '@/components/admin/StatCard.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

interface Subscriber {
    id: number;
    email: string;
    name?: string;
    source?: string;
    is_founding_member: boolean;
    subscribed_at?: string;
}

interface PaginatedSubscribers {
    data: Subscriber[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Props {
    subscribers: PaginatedSubscribers;
    stats: {
        total: number;
        founding_members: number;
        this_week: number;
        sources: Record<string, number>;
    };
    filters: {
        search?: string;
        source?: string;
        founding_member?: string;
        sort: string;
        direction: string;
    };
}

const props = defineProps<Props>();
const toast = useToast();
const confirm = useConfirm();

// Search with debounce
const searchQuery = ref(props.filters.search || '');
let searchTimeout: ReturnType<typeof setTimeout>;

watch(searchQuery, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters({ search: value });
    }, 300);
});

// Source filter options
const sourceOptions = computed(() => {
    const sources = Object.keys(props.stats.sources || {}).map(source => ({
        label: source || 'Direct',
        value: source,
    }));
    return [{ label: 'All Sources', value: null }, ...sources];
});

const selectedSource = ref(props.filters.source || null);

const applyFilters = (newFilters: Record<string, unknown>) => {
    router.get('/admin/waitlist', {
        ...props.filters,
        ...newFilters,
        page: 1, // Reset to first page
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleSourceChange = () => {
    applyFilters({ source: selectedSource.value });
};

const handlePageChange = (event: { page: number }) => {
    router.get('/admin/waitlist', {
        ...props.filters,
        page: event.page + 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleSort = (event: { sortField?: string | ((item: unknown) => string); sortOrder?: number | null }) => {
    if (typeof event.sortField === 'string') {
        applyFilters({
            sort: event.sortField,
            direction: event.sortOrder === 1 ? 'asc' : 'desc',
        });
    }
};

const sendInvite = (subscriber: Subscriber) => {
    confirm.require({
        message: `Send invite to ${subscriber.email}?`,
        header: 'Send Invite',
        icon: 'pi pi-envelope',
        acceptClass: '!bg-[#106B4F] !border-[#106B4F]',
        accept: () => {
            router.post(`/admin/waitlist/${subscriber.id}/invite`, {}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Invite Sent',
                        detail: `Invitation sent to ${subscriber.email}`,
                        life: 3000,
                    });
                },
            });
        },
    });
};

const removeSubscriber = (subscriber: Subscriber) => {
    confirm.require({
        message: `Remove ${subscriber.email} from waitlist?`,
        header: 'Remove Subscriber',
        icon: 'pi pi-trash',
        acceptClass: '!bg-red-500 !border-red-500',
        accept: () => {
            router.delete(`/admin/waitlist/${subscriber.id}`, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Removed',
                        detail: `${subscriber.email} removed from waitlist`,
                        life: 3000,
                    });
                },
            });
        },
    });
};

const exportWaitlist = () => {
    window.location.href = '/admin/waitlist/export';
};

const formatDate = (dateString?: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <AdminLayout title="Waitlist">
        <div class="waitlist-page">
            <!-- Stats -->
            <div class="stats-grid">
                <StatCard
                    title="Total Subscribers"
                    :value="stats.total"
                    icon="pi pi-users"
                    icon-bg="#106B4F"
                />
                <StatCard
                    title="Founding Members"
                    :value="stats.founding_members"
                    icon="pi pi-star"
                    icon-bg="#8B5CF6"
                />
                <StatCard
                    title="This Week"
                    :value="stats.this_week"
                    icon="pi pi-calendar-plus"
                    icon-bg="#3B82F6"
                    :trend="{ value: 15, direction: 'up', label: 'new signups' }"
                />
            </div>

            <!-- Filters & Actions -->
            <div class="filters-bar">
                <div class="filters-left">
                    <div class="search-wrapper">
                        <i class="pi pi-search search-icon"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search by name or email..."
                            class="search-input"
                        />
                    </div>
                    <Select
                        v-model="selectedSource"
                        :options="sourceOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Filter by source"
                        class="source-filter"
                        @change="handleSourceChange"
                    />
                </div>
                <div class="filters-right">
                    <Button
                        label="Export CSV"
                        icon="pi pi-download"
                        severity="secondary"
                        @click="exportWaitlist"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <DataTable
                    :value="subscribers.data"
                    :rows="subscribers.per_page"
                    :totalRecords="subscribers.total"
                    :lazy="true"
                    :paginator="true"
                    :rowsPerPageOptions="[10, 20, 50]"
                    :sortField="filters.sort"
                    :sortOrder="filters.direction === 'asc' ? 1 : -1"
                    @page="handlePageChange"
                    @sort="handleSort"
                    stripedRows
                    class="waitlist-table"
                >
                    <template #empty>
                        <div class="empty-state">
                            <i class="pi pi-inbox"></i>
                            <p>No subscribers found</p>
                        </div>
                    </template>

                    <Column field="email" header="Email" sortable style="min-width: 250px">
                        <template #body="{ data }">
                            <div class="email-cell">
                                <span class="email">{{ data.email }}</span>
                                <Tag
                                    v-if="data.is_founding_member"
                                    value="Founding"
                                    severity="warn"
                                    class="founding-tag"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column field="name" header="Name" sortable style="min-width: 150px">
                        <template #body="{ data }">
                            <span>{{ data.name || '-' }}</span>
                        </template>
                    </Column>

                    <Column field="source" header="Source" sortable style="width: 120px">
                        <template #body="{ data }">
                            <span class="source-badge">{{ data.source || 'Direct' }}</span>
                        </template>
                    </Column>

                    <Column field="subscribed_at" header="Subscribed" sortable style="width: 140px">
                        <template #body="{ data }">
                            <span class="date">{{ formatDate(data.subscribed_at) }}</span>
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 160px">
                        <template #body="{ data }">
                            <div class="actions">
                                <Button
                                    icon="pi pi-envelope"
                                    text
                                    rounded
                                    severity="info"
                                    v-tooltip.top="'Send Invite'"
                                    @click="sendInvite(data)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    text
                                    rounded
                                    severity="danger"
                                    v-tooltip.top="'Remove'"
                                    @click="removeSubscriber(data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Pagination info -->
            <div class="pagination-info">
                Showing {{ subscribers.from }}-{{ subscribers.to }} of {{ subscribers.total }} subscribers
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.waitlist-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}

@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

.filters-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    border: 1px solid #E2E8F0;
}

.filters-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.search-wrapper {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.search-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    font-size: 0.875rem;
}

.search-input {
    width: 100%;
    padding-left: 2.5rem !important;
}

.source-filter {
    width: 180px;
}

.table-container {
    background: white;
    border-radius: 12px;
    border: 1px solid #E2E8F0;
    overflow: hidden;
}

.email-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.email {
    font-weight: 500;
    color: #0F172A;
}

.founding-tag {
    font-size: 0.6875rem;
    padding: 0.125rem 0.375rem;
}

.source-badge {
    font-size: 0.8125rem;
    color: #64748B;
    text-transform: capitalize;
}

.date {
    font-size: 0.8125rem;
    color: #64748B;
}

.actions {
    display: flex;
    gap: 0.25rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #94A3B8;
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

.empty-state p {
    margin: 0;
    font-size: 0.875rem;
}

.pagination-info {
    text-align: center;
    font-size: 0.8125rem;
    color: #64748B;
}
</style>

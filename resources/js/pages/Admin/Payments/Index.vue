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
import DatePicker from 'primevue/datepicker';

interface Payment {
    id: number;
    uuid: string;
    client: {
        name: string;
        email: string;
    };
    provider: {
        business_name: string;
    };
    booking_uuid: string;
    amount: string;
    platform_fee: string;
    provider_amount: string;
    gateway: string;
    card_display: string | null;
    status: string;
    status_label: string;
    paid_at: string | null;
    created_at: string;
}

interface PaginatedPayments {
    data: Payment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Status {
    value: string;
    label: string;
}

const props = defineProps<{
    payments: PaginatedPayments;
    totals: {
        total_amount: number;
        total_fees: number;
        total_provider: number;
        pending_count: number;
    };
    filters: {
        search: string | null;
        status: string | null;
        date_from: string | null;
        date_to: string | null;
        sort: string;
        dir: string;
    };
    statuses: Status[];
}>();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const dateFrom = ref(props.filters.date_from ? new Date(props.filters.date_from) : null);
const dateTo = ref(props.filters.date_to ? new Date(props.filters.date_to) : null);

const statusOptions = [
    { value: '', label: 'All Status' },
    ...props.statuses,
];

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('admin.payments.index'), {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
            date_from: dateFrom.value ? formatDate(dateFrom.value) : undefined,
            date_to: dateTo.value ? formatDate(dateTo.value) : undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

const formatDate = (date: Date): string => {
    return date.toISOString().split('T')[0];
};

const formatCurrency = (value: number): string => {
    return '$' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

watch([search, selectedStatus, dateFrom, dateTo], applyFilters);

const getStatusSeverity = (status: string) => {
    const severities: Record<string, string> = {
        pending: 'warning',
        processing: 'info',
        completed: 'success',
        failed: 'danger',
        refunded: 'secondary',
        partially_refunded: 'secondary',
    };
    return severities[status] || 'secondary';
};

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    dateFrom.value = null;
    dateTo.value = null;
};
</script>

<template>
    <AdminLayout title="Payments">
        <Head title="Payment Management" />

        <div class="payments-page">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon success">
                                <i class="pi pi-wallet"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ formatCurrency(totals.total_amount) }}</span>
                                <span class="stat-label">Total Revenue</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon primary">
                                <i class="pi pi-percentage"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ formatCurrency(totals.total_fees) }}</span>
                                <span class="stat-label">Platform Fees</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon info">
                                <i class="pi pi-briefcase"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ formatCurrency(totals.total_provider) }}</span>
                                <span class="stat-label">Provider Earnings</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon warning">
                                <i class="pi pi-clock"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ totals.pending_count }}</span>
                                <span class="stat-label">Pending Payments</span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <Card>
                <template #title>
                    <div class="card-header">
                        <div class="header-left">
                            <h2>Payment Management</h2>
                            <Tag :value="`${payments.total} payments`" severity="secondary" />
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
                                placeholder="Search by client, provider, or transaction ID..."
                                class="search-input"
                            />
                        </div>

                        <Select
                            v-model="selectedStatus"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filter by status"
                            class="filter-select"
                        />

                        <DatePicker
                            v-model="dateFrom"
                            placeholder="From date"
                            dateFormat="M d, yy"
                            showIcon
                            class="date-picker"
                        />

                        <DatePicker
                            v-model="dateTo"
                            placeholder="To date"
                            dateFormat="M d, yy"
                            showIcon
                            class="date-picker"
                        />

                        <Button
                            v-if="search || selectedStatus || dateFrom || dateTo"
                            icon="pi pi-times"
                            severity="secondary"
                            text
                            rounded
                            @click="clearFilters"
                            v-tooltip.top="'Clear filters'"
                        />
                    </div>

                    <!-- Payments Table -->
                    <DataTable
                        :value="payments.data"
                        :paginator="false"
                        :rows="20"
                        class="payments-table"
                        stripedRows
                    >
                        <Column header="Client" style="min-width: 180px">
                            <template #body="{ data }">
                                <div class="client-cell">
                                    <span class="client-name">{{ data.client.name }}</span>
                                    <span class="client-email">{{ data.client.email }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Provider">
                            <template #body="{ data }">
                                {{ data.provider.business_name }}
                            </template>
                        </Column>

                        <Column header="Amount">
                            <template #body="{ data }">
                                <span class="amount">{{ data.amount }}</span>
                            </template>
                        </Column>

                        <Column header="Fee">
                            <template #body="{ data }">
                                {{ data.platform_fee }}
                            </template>
                        </Column>

                        <Column header="Provider Gets">
                            <template #body="{ data }">
                                {{ data.provider_amount }}
                            </template>
                        </Column>

                        <Column header="Payment">
                            <template #body="{ data }">
                                <div class="payment-method">
                                    <span>{{ data.gateway }}</span>
                                    <span v-if="data.card_display" class="card-info">{{ data.card_display }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Status">
                            <template #body="{ data }">
                                <Tag :severity="getStatusSeverity(data.status)" :value="data.status_label" />
                            </template>
                        </Column>

                        <Column header="Date">
                            <template #body="{ data }">
                                {{ data.paid_at || data.created_at }}
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 80px">
                            <template #body="{ data }">
                                <Link :href="route('admin.payments.show', data.uuid)">
                                    <Button
                                        icon="pi pi-eye"
                                        text
                                        rounded
                                        size="small"
                                        v-tooltip.top="'View Details'"
                                    />
                                </Link>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="pagination" v-if="payments.last_page > 1">
                        <Link
                            v-for="link in payments.links"
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
.payments-page {
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

.stat-icon.success {
    background-color: var(--p-green-100);
    color: var(--p-green-600);
}

.stat-icon.primary {
    background-color: var(--p-red-100);
    color: var(--p-red-600);
}

.stat-icon.info {
    background-color: var(--p-blue-100);
    color: var(--p-blue-600);
}

.stat-icon.warning {
    background-color: var(--p-yellow-100);
    color: var(--p-yellow-600);
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
    align-items: center;
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
    min-width: 150px;
}

.date-picker {
    min-width: 150px;
}

.client-cell {
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

.amount {
    font-weight: 600;
    color: var(--p-surface-900);
}

.payment-method {
    display: flex;
    flex-direction: column;
}

.card-info {
    font-size: 0.75rem;
    color: var(--p-surface-500);
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
    .filter-select,
    .date-picker {
        width: 100%;
    }
}
</style>

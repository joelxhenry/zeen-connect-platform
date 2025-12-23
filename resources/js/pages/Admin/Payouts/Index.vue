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
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';

interface Payout {
    id: number;
    uuid: string;
    provider: {
        business_name: string;
    };
    amount: string;
    period: string;
    payout_method: string;
    bank_account: string | null;
    reference_number: string | null;
    status: string;
    status_label: string;
    processed_by: string | null;
    processed_at: string | null;
    created_at: string;
}

interface PaginatedPayouts {
    data: Payout[];
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
    payouts: PaginatedPayouts;
    totals: {
        pending_amount: number;
        pending_count: number;
        completed_amount: number;
        completed_count: number;
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

const showProcessDialog = ref(false);
const selectedPayout = ref<Payout | null>(null);
const newStatus = ref('');
const referenceNumber = ref('');
const notes = ref('');

const statusOptions = [
    { value: '', label: 'All Status' },
    ...props.statuses,
];

const processStatusOptions = [
    { value: 'processing', label: 'Mark as Processing' },
    { value: 'completed', label: 'Mark as Completed' },
    { value: 'failed', label: 'Mark as Failed' },
];

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('admin.payouts.index'), {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

const formatCurrency = (value: number): string => {
    return '$' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

watch([search, selectedStatus], applyFilters);

const getStatusSeverity = (status: string) => {
    const severities: Record<string, string> = {
        pending: 'warning',
        approved: 'info',
        processing: 'info',
        completed: 'success',
        failed: 'danger',
        cancelled: 'secondary',
    };
    return severities[status] || 'secondary';
};

const openProcessDialog = (payout: Payout) => {
    selectedPayout.value = payout;
    newStatus.value = '';
    referenceNumber.value = '';
    notes.value = '';
    showProcessDialog.value = true;
};

const processPayout = () => {
    if (selectedPayout.value && newStatus.value) {
        router.put(route('admin.payouts.status', selectedPayout.value.uuid), {
            status: newStatus.value,
            reference_number: referenceNumber.value || undefined,
            notes: notes.value || undefined,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showProcessDialog.value = false;
                selectedPayout.value = null;
            },
        });
    }
};

const canProcess = (payout: Payout): boolean => {
    return ['pending', 'approved', 'processing'].includes(payout.status);
};
</script>

<template>
    <AdminLayout title="Payouts">
        <Head title="Payout Management" />

        <Dialog
            v-model:visible="showProcessDialog"
            modal
            header="Process Payout"
            :style="{ width: '450px' }"
        >
            <div class="process-dialog">
                <p>Process payout for <strong>{{ selectedPayout?.provider.business_name }}</strong></p>
                <p class="payout-amount">Amount: <strong>{{ selectedPayout?.amount }}</strong></p>

                <div class="form-field">
                    <label>Status</label>
                    <Select
                        v-model="newStatus"
                        :options="processStatusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select action"
                        class="w-full"
                    />
                </div>

                <div v-if="newStatus === 'completed'" class="form-field">
                    <label>Reference Number *</label>
                    <InputText
                        v-model="referenceNumber"
                        placeholder="Bank transfer reference"
                        class="w-full"
                    />
                </div>

                <div v-if="newStatus === 'failed'" class="form-field">
                    <label>Failure Notes</label>
                    <Textarea
                        v-model="notes"
                        placeholder="Reason for failure..."
                        rows="3"
                        class="w-full"
                    />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showProcessDialog = false" />
                <Button
                    label="Process"
                    @click="processPayout"
                    :disabled="!newStatus || (newStatus === 'completed' && !referenceNumber)"
                />
            </template>
        </Dialog>

        <div class="payouts-page">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon warning">
                                <i class="pi pi-clock"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ formatCurrency(totals.pending_amount) }}</span>
                                <span class="stat-label">Pending Payouts ({{ totals.pending_count }})</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon success">
                                <i class="pi pi-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ formatCurrency(totals.completed_amount) }}</span>
                                <span class="stat-label">Completed Payouts ({{ totals.completed_count }})</span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <Card>
                <template #title>
                    <div class="card-header">
                        <div class="header-left">
                            <h2>Payout Management</h2>
                            <Tag :value="`${payouts.total} payouts`" severity="secondary" />
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
                                placeholder="Search by provider or reference number..."
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
                    </div>

                    <!-- Payouts Table -->
                    <DataTable
                        :value="payouts.data"
                        :paginator="false"
                        :rows="20"
                        class="payouts-table"
                        stripedRows
                    >
                        <Column header="Provider" style="min-width: 200px">
                            <template #body="{ data }">
                                {{ data.provider.business_name }}
                            </template>
                        </Column>

                        <Column header="Amount">
                            <template #body="{ data }">
                                <span class="amount">{{ data.amount }}</span>
                            </template>
                        </Column>

                        <Column header="Period">
                            <template #body="{ data }">
                                {{ data.period }}
                            </template>
                        </Column>

                        <Column header="Method">
                            <template #body="{ data }">
                                <div class="method-cell">
                                    <span>{{ data.payout_method }}</span>
                                    <span v-if="data.bank_account" class="bank-info">{{ data.bank_account }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Status">
                            <template #body="{ data }">
                                <Tag :severity="getStatusSeverity(data.status)" :value="data.status_label" />
                            </template>
                        </Column>

                        <Column header="Reference">
                            <template #body="{ data }">
                                <span v-if="data.reference_number">{{ data.reference_number }}</span>
                                <span v-else class="no-data">-</span>
                            </template>
                        </Column>

                        <Column header="Processed">
                            <template #body="{ data }">
                                <div v-if="data.processed_at" class="processed-cell">
                                    <span>{{ data.processed_at }}</span>
                                    <span v-if="data.processed_by" class="processed-by">by {{ data.processed_by }}</span>
                                </div>
                                <span v-else class="no-data">-</span>
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 120px">
                            <template #body="{ data }">
                                <div class="action-buttons">
                                    <Link :href="route('admin.payouts.show', data.uuid)">
                                        <Button
                                            icon="pi pi-eye"
                                            text
                                            rounded
                                            size="small"
                                            v-tooltip.top="'View'"
                                        />
                                    </Link>
                                    <Button
                                        v-if="canProcess(data)"
                                        icon="pi pi-money-bill"
                                        text
                                        rounded
                                        size="small"
                                        @click="openProcessDialog(data)"
                                        v-tooltip.top="'Process'"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="pagination" v-if="payouts.last_page > 1">
                        <Link
                            v-for="link in payouts.links"
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
.payouts-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
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

.stat-icon.warning {
    background-color: var(--p-yellow-100);
    color: var(--p-yellow-600);
}

.stat-icon.success {
    background-color: var(--p-green-100);
    color: var(--p-green-600);
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
    min-width: 150px;
}

.amount {
    font-weight: 600;
    color: var(--p-surface-900);
}

.method-cell {
    display: flex;
    flex-direction: column;
}

.bank-info {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.processed-cell {
    display: flex;
    flex-direction: column;
}

.processed-by {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.no-data {
    color: var(--p-surface-400);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.process-dialog {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.process-dialog p {
    margin: 0;
    color: var(--p-surface-600);
}

.payout-amount {
    font-size: 1.125rem;
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

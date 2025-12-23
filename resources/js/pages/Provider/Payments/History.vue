<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import TabMenu from 'primevue/tabmenu';

interface Payment {
    id: number;
    uuid: string;
    booking_uuid: string;
    client_name: string;
    service_name: string;
    booking_date: string;
    amount: number;
    amount_display: string;
    platform_fee: number;
    platform_fee_display: string;
    provider_amount: number;
    provider_amount_display: string;
    status: string;
    status_label: string;
    status_color: string;
    card_display: string | null;
    paid_at: string | null;
    created_at: string;
}

interface StatusOption {
    value: string;
    label: string;
}

interface Counts {
    all: number;
    completed: number;
    pending: number;
    failed: number;
}

interface PaginatedPayments {
    data: Payment[];
    links: any;
    meta?: any;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    payments: PaginatedPayments;
    currentStatus: string;
    counts: Counts;
    statusOptions: StatusOption[];
}>();

const statusTabs = [
    { label: `All (${props.counts.all})`, value: 'all' },
    { label: `Completed (${props.counts.completed})`, value: 'completed' },
    { label: `Pending (${props.counts.pending})`, value: 'pending' },
    { label: `Failed (${props.counts.failed})`, value: 'failed' },
];

const activeTabIndex = statusTabs.findIndex(tab => tab.value === props.currentStatus);

const changeStatus = (event: any) => {
    const status = statusTabs[event.index].value;
    router.get(route('provider.payments.history'), { status }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <ConsoleLayout>
        <Head title="Payment History" />

        <div class="history-page">
            <div class="page-header">
                <div class="header-left">
                    <Link :href="route('provider.payments.index')" class="back-link">
                        <i class="pi pi-arrow-left"></i>
                    </Link>
                    <h1 class="page-title">Payment History</h1>
                </div>
            </div>

            <Card class="main-card">
                <template #content>
                    <TabMenu
                        :model="statusTabs"
                        :activeIndex="activeTabIndex"
                        @tab-change="changeStatus"
                        class="status-tabs"
                    />

                    <div v-if="payments.data.length === 0" class="empty-state">
                        <i class="pi pi-inbox"></i>
                        <p>No payments found</p>
                    </div>

                    <DataTable
                        v-else
                        :value="payments.data"
                        :paginator="payments.last_page > 1"
                        :rows="payments.per_page"
                        :totalRecords="payments.total"
                        :rowsPerPageOptions="[10, 20, 50]"
                        responsiveLayout="scroll"
                        class="payments-table"
                    >
                        <Column header="Transaction">
                            <template #body="{ data }">
                                <div class="transaction-cell">
                                    <span class="service-name">{{ data.service_name }}</span>
                                    <span class="client-name">{{ data.client_name }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column header="Booking Date">
                            <template #body="{ data }">
                                <span class="booking-date">{{ data.booking_date }}</span>
                            </template>
                        </Column>
                        <Column header="Total">
                            <template #body="{ data }">
                                <span class="amount">{{ data.amount_display }}</span>
                            </template>
                        </Column>
                        <Column header="Fee">
                            <template #body="{ data }">
                                <span class="fee">-{{ data.platform_fee_display }}</span>
                            </template>
                        </Column>
                        <Column header="Your Earnings">
                            <template #body="{ data }">
                                <span class="earnings">{{ data.provider_amount_display }}</span>
                            </template>
                        </Column>
                        <Column header="Status">
                            <template #body="{ data }">
                                <Tag :severity="data.status_color" :value="data.status_label" />
                            </template>
                        </Column>
                        <Column header="Date">
                            <template #body="{ data }">
                                <span class="payment-date">{{ data.paid_at || data.created_at }}</span>
                            </template>
                        </Column>
                        <Column header="">
                            <template #body="{ data }">
                                <Link :href="route('provider.bookings.show', data.booking_uuid)">
                                    <Button icon="pi pi-eye" text rounded size="small" />
                                </Link>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.history-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.back-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--p-surface-100);
    color: var(--p-surface-600);
    transition: all 0.2s;
}

.back-link:hover {
    background: var(--p-surface-200);
    color: var(--p-surface-900);
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.main-card {
    border-radius: 12px;
}

.status-tabs {
    margin-bottom: 1.5rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: var(--p-surface-400);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.empty-state p {
    margin: 0;
    font-weight: 500;
    color: var(--p-surface-600);
}

.payments-table :deep(.p-datatable-tbody > tr > td) {
    padding: 0.875rem 0.75rem;
}

.transaction-cell {
    display: flex;
    flex-direction: column;
}

.service-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.client-name {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.booking-date {
    font-size: 0.875rem;
    color: var(--p-surface-700);
}

.amount {
    font-weight: 500;
    color: var(--p-surface-900);
}

.fee {
    font-size: 0.875rem;
    color: var(--p-red-500);
}

.earnings {
    font-weight: 600;
    color: var(--p-green-600);
}

.payment-date {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

@media (max-width: 768px) {
    .payments-table :deep(.p-datatable) {
        font-size: 0.875rem;
    }
}
</style>

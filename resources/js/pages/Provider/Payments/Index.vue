<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

interface Summary {
    total_earnings: number;
    total_earnings_display: string;
    pending_payout: number;
    pending_payout_display: string;
    total_paid_out: number;
    total_paid_out_display: string;
}

interface Payment {
    id: number;
    uuid: string;
    booking_uuid: string;
    service_name: string;
    booking_date: string;
    amount: number;
    platform_fee: number;
    provider_amount: number;
    provider_amount_display: string;
    paid_at: string;
}

interface Payout {
    id: number;
    uuid: string;
    amount: number;
    amount_display: string;
    period_display: string;
    status: string;
    status_label: string;
    status_color: string;
    bank_account_display: string | null;
    reference_number: string | null;
    processed_at: string | null;
    created_at: string;
}

interface MonthlyEarning {
    month: string;
    month_label: string;
    total: number;
    total_display: string;
    transaction_count: number;
}

defineProps<{
    summary: Summary;
    recentPayments: Payment[];
    payouts: Payout[];
    monthlyEarnings: MonthlyEarning[];
}>();
</script>

<template>
    <ConsoleLayout>
        <Head title="Earnings" />

        <div class="earnings-page">
            <div class="page-header">
                <h1 class="page-title">Earnings</h1>
                <Link :href="route('provider.payments.history')">
                    <Button label="View All Transactions" icon="pi pi-list" severity="secondary" outlined size="small" />
                </Link>
            </div>

            <!-- Summary Cards -->
            <div class="summary-grid">
                <Card class="summary-card earnings">
                    <template #content>
                        <div class="summary-content">
                            <div class="summary-icon">
                                <i class="pi pi-dollar"></i>
                            </div>
                            <div class="summary-info">
                                <span class="summary-label">Total Earnings</span>
                                <span class="summary-value">{{ summary.total_earnings_display }}</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="summary-card pending">
                    <template #content>
                        <div class="summary-content">
                            <div class="summary-icon">
                                <i class="pi pi-clock"></i>
                            </div>
                            <div class="summary-info">
                                <span class="summary-label">Pending Payout</span>
                                <span class="summary-value">{{ summary.pending_payout_display }}</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="summary-card paid">
                    <template #content>
                        <div class="summary-content">
                            <div class="summary-icon">
                                <i class="pi pi-check-circle"></i>
                            </div>
                            <div class="summary-info">
                                <span class="summary-label">Total Paid Out</span>
                                <span class="summary-value">{{ summary.total_paid_out_display }}</span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Monthly Earnings Chart Placeholder -->
            <Card class="chart-card" v-if="monthlyEarnings.length > 0">
                <template #title>
                    <div class="section-header">
                        <i class="pi pi-chart-bar"></i>
                        <span>Monthly Earnings</span>
                    </div>
                </template>
                <template #content>
                    <div class="monthly-bars">
                        <div
                            v-for="month in monthlyEarnings"
                            :key="month.month"
                            class="month-bar-container"
                        >
                            <div class="bar-wrapper">
                                <div
                                    class="bar"
                                    :style="{
                                        height: `${Math.max(10, (month.total / Math.max(...monthlyEarnings.map(m => m.total))) * 100)}%`
                                    }"
                                ></div>
                            </div>
                            <span class="month-label">{{ month.month_label.split(' ')[0] }}</span>
                            <span class="month-amount">{{ month.total_display }}</span>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="content-grid">
                <!-- Recent Payments -->
                <Card class="payments-card">
                    <template #title>
                        <div class="section-header">
                            <i class="pi pi-credit-card"></i>
                            <span>Recent Payments</span>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="recentPayments.length === 0" class="empty-state">
                            <i class="pi pi-inbox"></i>
                            <p>No payments received yet</p>
                        </div>
                        <DataTable v-else :value="recentPayments" :rows="5" class="payments-table">
                            <Column field="service_name" header="Service">
                                <template #body="{ data }">
                                    <div class="service-cell">
                                        <span class="service-name">{{ data.service_name }}</span>
                                        <span class="booking-date">{{ data.booking_date }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="provider_amount_display" header="Earned" class="text-right">
                                <template #body="{ data }">
                                    <span class="amount-earned">{{ data.provider_amount_display }}</span>
                                </template>
                            </Column>
                            <Column field="paid_at" header="Date" class="text-right">
                                <template #body="{ data }">
                                    <span class="payment-date">{{ data.paid_at }}</span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <!-- Payout History -->
                <Card class="payouts-card">
                    <template #title>
                        <div class="section-header">
                            <i class="pi pi-wallet"></i>
                            <span>Payout History</span>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="payouts.length === 0" class="empty-state">
                            <i class="pi pi-wallet"></i>
                            <p>No payouts yet</p>
                            <span class="empty-hint">Payouts are processed weekly</span>
                        </div>
                        <div v-else class="payouts-list">
                            <div
                                v-for="payout in payouts"
                                :key="payout.id"
                                class="payout-item"
                            >
                                <div class="payout-info">
                                    <span class="payout-amount">{{ payout.amount_display }}</span>
                                    <span class="payout-period">{{ payout.period_display }}</span>
                                </div>
                                <div class="payout-status">
                                    <Tag :severity="payout.status_color" :value="payout.status_label" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Commission Info -->
            <Card class="info-card">
                <template #content>
                    <div class="info-content">
                        <i class="pi pi-info-circle"></i>
                        <div class="info-text">
                            <strong>Platform Commission:</strong> Zeen Connect charges a 15% platform fee on each transaction.
                            Your earnings shown are net of this fee. Payouts are processed weekly for amounts over $1,000 JMD.
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.earnings-page {
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

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.summary-card {
    border-radius: 12px;
}

.summary-card.earnings .summary-icon {
    background: var(--p-green-100);
    color: var(--p-green-600);
}

.summary-card.pending .summary-icon {
    background: var(--p-orange-100);
    color: var(--p-orange-600);
}

.summary-card.paid .summary-icon {
    background: var(--p-blue-100);
    color: var(--p-blue-600);
}

.summary-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.summary-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.summary-icon i {
    font-size: 1.25rem;
}

.summary-info {
    display: flex;
    flex-direction: column;
}

.summary-label {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
}

.chart-card {
    border-radius: 12px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
}

.section-header i {
    color: var(--p-primary-color);
}

.monthly-bars {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    height: 200px;
    padding-top: 1rem;
}

.month-bar-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.bar-wrapper {
    height: 150px;
    width: 100%;
    max-width: 40px;
    display: flex;
    align-items: flex-end;
}

.bar {
    width: 100%;
    background: linear-gradient(180deg, var(--p-primary-400), var(--p-primary-600));
    border-radius: 4px 4px 0 0;
    transition: height 0.3s ease;
}

.month-label {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.month-amount {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--p-surface-700);
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.payments-card,
.payouts-card {
    border-radius: 12px;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--p-surface-400);
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

.empty-state p {
    margin: 0;
    font-weight: 500;
    color: var(--p-surface-600);
}

.empty-hint {
    font-size: 0.8125rem;
    color: var(--p-surface-400);
    margin-top: 0.25rem;
}

.payments-table :deep(.p-datatable-tbody > tr > td) {
    padding: 0.75rem;
}

.service-cell {
    display: flex;
    flex-direction: column;
}

.service-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.booking-date {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.amount-earned {
    font-weight: 600;
    color: var(--p-green-600);
}

.payment-date {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.payouts-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.payout-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--p-surface-50);
    border-radius: 8px;
}

.payout-info {
    display: flex;
    flex-direction: column;
}

.payout-amount {
    font-weight: 600;
    color: var(--p-surface-900);
}

.payout-period {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.info-card {
    border-radius: 12px;
    background: var(--p-blue-50);
    border: 1px solid var(--p-blue-200);
}

.info-content {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.info-content i {
    color: var(--p-blue-500);
    font-size: 1.25rem;
    flex-shrink: 0;
}

.info-text {
    color: var(--p-blue-700);
    font-size: 0.875rem;
    line-height: 1.5;
}

@media (max-width: 1024px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }

    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .monthly-bars {
        height: 150px;
    }

    .bar-wrapper {
        height: 100px;
    }
}
</style>

<script setup lang="ts">
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleStatCard,
    ConsoleEmptyState,
    ConsoleAlertBanner,
    ConsoleButton,
    ConsoleDataCard,
} from '@/components/console';
import AppLink from '@/components/common/AppLink.vue';
import Tag from 'primevue/tag';
import type { PaymentsIndexProps } from '@/types/payments';
import provider from '@/routes/provider';

const props = defineProps<PaymentsIndexProps>();

const getStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'info' | 'secondary' => {
    switch (status) {
        case 'completed':
            return 'success';
        case 'pending':
        case 'processing':
            return 'warn';
        case 'failed':
            return 'danger';
        default:
            return 'secondary';
    }
};
</script>

<template>
    <ConsoleLayout title="Earnings">
        <div class="w-full max-w-7xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader title="Earnings & Payments" subtitle="Track your earnings and manage payments">
                <template #actions>
                    <ConsoleButton label="Payment Setup" icon="pi pi-cog" variant="secondary" outlined
                        :href="provider.payments.setup.index().url" />
                </template>
            </ConsolePageHeader>

            <!-- No Gateway Alert -->
            <ConsoleAlertBanner v-if="!hasGatewayConfigured" variant="warning" class="mb-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <p class="font-medium m-0 mb-1">Set up a payment method</p>
                        <p class="text-sm m-0 opacity-80">Connect a payment gateway to start receiving payments from
                            customers.</p>
                    </div>
                    <ConsoleButton label="Set Up Now" icon="pi pi-arrow-right" icon-pos="right" size="small"
                        :href="provider.payments.setup.index().url" />
                </div>
            </ConsoleAlertBanner>

            <!-- Gateway Mode Info -->
            <ConsoleAlertBanner v-if="hasGatewayConfigured && gatewayMode === 'escrow'" variant="info" class="mb-6">
                <div class="flex items-start gap-2">
                    <i class="pi pi-info-circle mt-0.5" />
                    <div>
                        <p class="font-medium m-0">Escrow Mode Active</p>
                        <p class="text-sm m-0 opacity-80">
                            Payments are collected by the platform. View your wallet for balance and payout schedule.
                        </p>
                    </div>
                </div>
            </ConsoleAlertBanner>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <ConsoleStatCard title="Total Earnings" :value="summary.total_earnings_display" icon="pi pi-dollar"
                    icon-color="primary" />
                <ConsoleStatCard title="This Month" :value="summary.this_month_display || '$0.00'" icon="pi pi-calendar"
                    icon-color="accent" />
                <ConsoleStatCard v-if="gatewayMode === 'escrow'" title="Available Balance"
                    :value="summary.available_balance_display || '$0.00'" icon="pi pi-wallet" icon-color="success"
                    :href="provider.payments.wallet().url" />
                <ConsoleStatCard v-else title="Pending Payout" :value="summary.pending_payout_display"
                    icon="pi pi-clock" icon-color="warning" />
                <ConsoleStatCard title="Total Paid Out" :value="summary.total_paid_out_display"
                    icon="pi pi-check-circle" icon-color="purple" />
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-3 mb-6">
                <ConsoleButton label="View All Payments" icon="pi pi-list" variant="secondary" outlined
                    :href="provider.payments.history().url" />
                <ConsoleButton v-if="gatewayMode === 'escrow'" label="Wallet & Payouts" icon="pi pi-wallet"
                    variant="secondary" outlined :href="provider.payments.wallet().url" />
                <ConsoleButton label="Refunds" icon="pi pi-replay" variant="secondary" outlined
                    :href="provider.payments.refunds().url" />
            </div>

            <!-- Two Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Payments -->
                <ConsoleFormCard title="Recent Payments" icon="pi pi-credit-card">
                    <template #header-actions>
                        <ConsoleButton label="View All" icon="pi pi-arrow-right" icon-pos="right" size="small"
                            variant="secondary" outlined :href="provider.payments.history().url" />
                    </template>

                    <ConsoleEmptyState v-if="recentPayments.length === 0" icon="pi pi-credit-card"
                        title="No payments yet"
                        description="Payments will appear here once customers book your services." size="compact" />

                    <div v-else class="space-y-3">
                        <div v-for="payment in recentPayments" :key="payment.uuid"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-[#0D1F1B] m-0 truncate">
                                    {{ payment.service_name }}
                                </p>
                                <p class="text-xs text-gray-500 m-0">
                                    {{ payment.booking_date }}
                                </p>
                            </div>
                            <div class="text-right ml-3">
                                <p class="font-semibold text-[#106B4F] m-0">
                                    {{ payment.provider_amount_display }}
                                </p>
                                <p class="text-xs text-gray-400 m-0">
                                    {{ payment.paid_at }}
                                </p>
                            </div>
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Recent Payouts -->
                <ConsoleFormCard title="Recent Payouts" icon="pi pi-send">
                    <template #header-actions>
                        <ConsoleButton v-if="gatewayMode === 'escrow'" label="Wallet" icon="pi pi-arrow-right"
                            icon-pos="right" size="small" variant="secondary" outlined
                            :href="provider.payments.wallet().url" />
                    </template>

                    <ConsoleEmptyState v-if="payouts.length === 0" icon="pi pi-send" title="No payouts yet"
                        description="Payouts will appear here once payments are processed." size="compact" />

                    <div v-else class="space-y-3">
                        <AppLink v-for="payout in payouts" :key="payout.uuid"
                            :href="provider.payments.payout({ uuid: payout.uuid }).url"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors no-underline">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="font-medium text-[#0D1F1B] m-0">
                                        {{ payout.amount_display }}
                                    </p>
                                    <Tag :value="payout.status_label" :severity="getStatusSeverity(payout.status)"
                                        class="!text-xs" />
                                </div>
                                <p class="text-xs text-gray-500 m-0">
                                    {{ payout.period_display }}
                                </p>
                            </div>
                            <div class="text-right ml-3">
                                <p class="text-xs text-gray-400 m-0">
                                    {{ payout.processed_at || payout.created_at }}
                                </p>
                                <i class="pi pi-chevron-right text-gray-300 text-xs mt-1" />
                            </div>
                        </AppLink>
                    </div>
                </ConsoleFormCard>
            </div>

            <!-- Monthly Earnings Chart (if data available) -->
            <ConsoleFormCard v-if="monthlyEarnings.length > 0" title="Monthly Earnings" icon="pi pi-chart-bar"
                class="mt-6">
                <div class="h-48 flex items-end justify-between gap-2">
                    <div v-for="month in monthlyEarnings" :key="month.month" class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-[#106B4F] rounded-t-md transition-all hover:bg-[#0D5A42]" :style="{
                            height: `${Math.max(10, (month.total / Math.max(...monthlyEarnings.map(m => m.total))) * 100)}%`,
                            minHeight: '10px',
                        }" v-tooltip="{ value: `${month.month_label}: ${month.total_display}`, showDelay: 100 }" />
                        <span class="text-xs text-gray-500 mt-2">
                            {{ month.month_label.split(' ')[0] }}
                        </span>
                    </div>
                </div>
            </ConsoleFormCard>
        </div>
    </ConsoleLayout>
</template>

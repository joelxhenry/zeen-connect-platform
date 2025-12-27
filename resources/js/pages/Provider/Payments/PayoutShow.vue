<script setup lang="ts">
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleAlertBanner,
} from '@/components/console';
import Tag from 'primevue/tag';
import type { Payout, Payment } from '@/types/payments';

interface Props {
    payout: Payout & {
        period_start: string;
        period_end: string;
        notes?: string;
    };
    payments: Array<{
        id: number;
        uuid: string;
        provider_amount: number;
        provider_amount_display: string;
        paid_at: string;
    }>;
}

const props = defineProps<Props>();

const getStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'info' | 'secondary' => {
    switch (status) {
        case 'completed':
            return 'success';
        case 'pending':
        case 'processing':
            return 'warn';
        case 'failed':
            return 'danger';
        case 'cancelled':
            return 'secondary';
        default:
            return 'info';
    }
};
</script>

<template>
    <ConsoleLayout title="Payout Details">
        <div class="w-full max-w-4xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Payout Details"
                :subtitle="`Payout ${payout.reference_number || payout.uuid.slice(0, 8)}`"
                back-href="/payments"
            />

            <!-- Status Banner -->
            <div
                :class="[
                    'rounded-xl p-6 mb-6',
                    payout.status === 'completed' ? 'bg-green-50' :
                    payout.status === 'failed' ? 'bg-red-50' :
                    payout.status === 'cancelled' ? 'bg-gray-100' :
                    'bg-yellow-50'
                ]"
            >
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <i
                                :class="[
                                    'text-2xl',
                                    payout.status === 'completed' ? 'pi pi-check-circle text-green-600' :
                                    payout.status === 'failed' ? 'pi pi-times-circle text-red-600' :
                                    payout.status === 'cancelled' ? 'pi pi-ban text-gray-500' :
                                    'pi pi-clock text-yellow-600'
                                ]"
                            />
                            <Tag
                                :value="payout.status_label"
                                :severity="getStatusSeverity(payout.status)"
                            />
                        </div>
                        <p class="text-3xl font-bold text-[#0D1F1B] m-0">
                            {{ payout.amount_display }}
                        </p>
                        <p class="text-sm text-gray-500 m-0 mt-1">
                            {{ payout.period_display }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p v-if="payout.processed_at" class="text-sm text-gray-500 m-0">
                            Processed: {{ payout.processed_at }}
                        </p>
                        <p v-if="payout.reference_number" class="text-sm font-mono text-gray-500 m-0">
                            Ref: {{ payout.reference_number }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Notes Alert -->
            <ConsoleAlertBanner
                v-if="payout.notes"
                variant="info"
                class="mb-6"
            >
                {{ payout.notes }}
            </ConsoleAlertBanner>

            <!-- Payout Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <ConsoleFormCard title="Payout Information" icon="pi pi-info-circle">
                    <div class="space-y-4">
                        <div>
                            <span class="text-gray-500 text-sm block mb-1">Period</span>
                            <p class="font-medium text-[#0D1F1B] m-0">
                                {{ payout.period_start }} - {{ payout.period_end }}
                            </p>
                        </div>
                        <div v-if="payout.payout_method">
                            <span class="text-gray-500 text-sm block mb-1">Payout Method</span>
                            <p class="font-medium text-[#0D1F1B] m-0">
                                {{ payout.payout_method }}
                            </p>
                        </div>
                        <div v-if="payout.bank_account_display">
                            <span class="text-gray-500 text-sm block mb-1">Bank Account</span>
                            <p class="font-medium text-[#0D1F1B] m-0">
                                {{ payout.bank_account_display }}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-500 text-sm block mb-1">Created</span>
                            <p class="font-medium text-[#0D1F1B] m-0">
                                {{ payout.created_at }}
                            </p>
                        </div>
                    </div>
                </ConsoleFormCard>

                <ConsoleFormCard title="Summary" icon="pi pi-chart-bar">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Payments Included</span>
                            <span class="font-semibold text-[#0D1F1B]">
                                {{ payout.payments_count }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Total Amount</span>
                            <span class="font-semibold text-[#0D1F1B]">
                                {{ payout.amount_display }}
                            </span>
                        </div>
                        <div v-if="payout.processed_by" class="flex justify-between items-center">
                            <span class="text-gray-500">Processed By</span>
                            <span class="font-medium text-[#0D1F1B]">
                                {{ payout.processed_by }}
                            </span>
                        </div>
                    </div>
                </ConsoleFormCard>
            </div>

            <!-- Included Payments -->
            <ConsoleFormCard title="Included Payments" icon="pi pi-list">
                <div v-if="payments.length === 0" class="text-center py-8 text-gray-500">
                    No payment details available
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="payment in payments"
                        :key="payment.uuid"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#106B4F]/10 flex items-center justify-center">
                                <i class="pi pi-credit-card text-sm text-[#106B4F]" />
                            </div>
                            <div>
                                <p class="font-medium text-[#0D1F1B] m-0 text-sm">
                                    Payment #{{ payment.uuid.slice(0, 8) }}
                                </p>
                                <p class="text-xs text-gray-500 m-0">
                                    {{ payment.paid_at }}
                                </p>
                            </div>
                        </div>
                        <span class="font-semibold text-[#106B4F]">
                            {{ payment.provider_amount_display }}
                        </span>
                    </div>
                </div>
            </ConsoleFormCard>
        </div>
    </ConsoleLayout>
</template>

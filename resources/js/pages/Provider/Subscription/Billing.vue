<script setup lang="ts">
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleButton,
} from '@/components/console';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

interface Invoice {
    uuid: string;
    invoice_number: string;
    tier: string;
    tier_label: string;
    billing_cycle: string;
    amount: number;
    amount_display: string;
    currency: string;
    status: string;
    status_label: string;
    status_color: string;
    period_start: string;
    period_end: string;
    paid_at: string | null;
    created_at: string;
    download_url: string;
}

interface PaymentMethod {
    uuid: string;
    card_display: string;
    card_brand: string;
    card_last_four: string;
    card_expiry: string;
    is_default: boolean;
    is_expired: boolean;
}

interface Props {
    invoices: Invoice[];
    paymentMethod: PaymentMethod | null;
    hasActiveSubscription: boolean;
}

const props = defineProps<Props>();

const getStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'info' | 'secondary' => {
    switch (status) {
        case 'paid':
            return 'success';
        case 'pending':
            return 'warn';
        case 'failed':
            return 'danger';
        case 'refunded':
            return 'info';
        default:
            return 'secondary';
    }
};
</script>

<template>
    <ConsoleLayout title="Billing History">
        <div class="w-full max-w-5xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Billing"
                subtitle="View your invoices and manage payment methods"
            >
                <template #actions>
                    <AppLink :href="route('provider.subscription.index')">
                        <ConsoleButton
                            label="Back to Subscription"
                            icon="pi pi-arrow-left"
                            variant="secondary"
                        />
                    </AppLink>
                </template>
            </ConsolePageHeader>

            <!-- Payment Method Card -->
            <ConsoleFormCard class="mb-6">
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-[#0D1F1B] m-0">Payment Method</h3>
                        <AppLink v-if="paymentMethod" :href="route('provider.subscription.payment-method.edit')">
                            <ConsoleButton
                                label="Update"
                                icon="pi pi-pencil"
                                variant="secondary"
                                size="small"
                            />
                        </AppLink>
                    </div>
                </template>

                <div v-if="paymentMethod" class="flex items-center gap-4">
                    <div class="w-12 h-8 bg-gray-100 rounded flex items-center justify-center">
                        <i class="pi pi-credit-card text-gray-500"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-[#0D1F1B] m-0">{{ paymentMethod.card_display }}</p>
                        <p class="text-sm text-gray-500 m-0">Expires {{ paymentMethod.card_expiry }}</p>
                    </div>
                    <Tag v-if="paymentMethod.is_expired" value="Expired" severity="danger" />
                    <Tag v-else-if="paymentMethod.is_default" value="Default" severity="success" />
                </div>

                <div v-else class="text-center py-6">
                    <i class="pi pi-credit-card text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 m-0 mb-3">No payment method on file</p>
                    <AppLink v-if="hasActiveSubscription" :href="route('provider.subscription.payment-method.edit')">
                        <ConsoleButton
                            label="Add Payment Method"
                            icon="pi pi-plus"
                        />
                    </AppLink>
                </div>
            </ConsoleFormCard>

            <!-- Failed Payment Alert -->
            <div v-if="invoices.some(i => i.status === 'failed')"
                 class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <div class="flex items-start gap-3">
                    <i class="pi pi-exclamation-triangle text-red-600 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="font-medium text-red-800 m-0">Payment Failed</p>
                        <p class="text-sm text-red-700 m-0 mt-1">
                            Your most recent payment failed. Please update your payment method to avoid service interruption.
                        </p>
                    </div>
                    <AppLink :href="route('provider.subscription.payment-method.edit')">
                        <ConsoleButton
                            label="Update Payment"
                            size="small"
                            class="!bg-red-600 !border-red-600"
                        />
                    </AppLink>
                </div>
            </div>

            <!-- Invoice History -->
            <ConsoleFormCard>
                <template #header>
                    <h3 class="text-lg font-semibold text-[#0D1F1B] m-0">Invoice History</h3>
                </template>

                <DataTable
                    v-if="invoices.length > 0"
                    :value="invoices"
                    :paginator="invoices.length > 10"
                    :rows="10"
                    stripedRows
                    class="p-datatable-sm"
                >
                    <Column field="invoice_number" header="Invoice" sortable>
                        <template #body="{ data }">
                            <span class="font-medium text-[#0D1F1B]">{{ data.invoice_number }}</span>
                        </template>
                    </Column>
                    <Column field="created_at" header="Date" sortable>
                        <template #body="{ data }">
                            {{ data.created_at }}
                        </template>
                    </Column>
                    <Column field="tier_label" header="Plan">
                        <template #body="{ data }">
                            <div>
                                <span>{{ data.tier_label }}</span>
                                <span class="text-xs text-gray-500 ml-1">({{ data.billing_cycle }})</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="amount_display" header="Amount" sortable>
                        <template #body="{ data }">
                            <span class="font-medium">{{ data.amount_display }}</span>
                        </template>
                    </Column>
                    <Column field="status" header="Status" sortable>
                        <template #body="{ data }">
                            <Tag :value="data.status_label" :severity="getStatusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column header="Actions" style="width: 100px">
                        <template #body="{ data }">
                            <a :href="data.download_url" target="_blank" class="no-underline">
                                <ConsoleButton
                                    icon="pi pi-download"
                                    variant="secondary"
                                    size="small"
                                    rounded
                                    v-tooltip="'Download PDF'"
                                />
                            </a>
                        </template>
                    </Column>
                </DataTable>

                <div v-else class="text-center py-12">
                    <i class="pi pi-file text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 m-0">No invoices yet</p>
                    <p class="text-sm text-gray-400 m-0 mt-1">
                        Your billing history will appear here after your first payment.
                    </p>
                </div>
            </ConsoleFormCard>
        </div>
    </ConsoleLayout>
</template>

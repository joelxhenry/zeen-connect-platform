<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleEmptyState,
    ConsoleDataCard,
} from '@/components/console';
import AppLink from '@/components/common/AppLink.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import { useToast } from 'primevue/usetoast';
import type { PaymentHistoryProps, Payment } from '@/types/payments';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

const props = defineProps<PaymentHistoryProps>();
const toast = useToast();

const currentStatus = ref(props.current_status);
const searchQuery = ref('');

// Refund dialog
const showRefundDialog = ref(false);
const selectedPayment = ref<Payment | null>(null);
const refundForm = ref({
    amount: 0,
    reason: '',
    isPartial: false,
});

watch(currentStatus, (newStatus) => {
    router.get(resolveUrl(provider.payments.history().url), { status: newStatus }, {
        preserveState: true,
        preserveScroll: true,
    });
});

const getStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'info' | 'secondary' => {
    switch (status) {
        case 'completed':
            return 'success';
        case 'pending':
        case 'processing':
            return 'warn';
        case 'failed':
            return 'danger';
        case 'refunded':
        case 'partially_refunded':
            return 'secondary';
        default:
            return 'info';
    }
};

const openRefundDialog = (payment: Payment) => {
    selectedPayment.value = payment;
    refundForm.value = {
        amount: payment.amount,
        reason: '',
        isPartial: false,
    };
    showRefundDialog.value = true;
};

const submitRefund = () => {
    if (!selectedPayment.value) return;

    router.post(provider.payments.refund({ uuid: selectedPayment.value.uuid }).url, {
        amount: refundForm.value.isPartial ? refundForm.value.amount : null,
        reason: refundForm.value.reason,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRefundDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Refund Initiated',
                detail: 'The refund has been processed.',
                life: 3000,
            });
        },
        onError: (errors: Record<string, string>) => {
            toast.add({
                severity: 'error',
                summary: 'Refund Failed',
                detail: errors.refund || 'Failed to process refund.',
                life: 5000,
            });
        },
    });
};

const canRefund = (payment: Payment): boolean => {
    return payment.status === 'completed' && !payment.refunded_at;
};
</script>

<template>
    <ConsoleLayout title="Payment History">
        <div class="w-full max-w-5xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader title="Payment History" subtitle="View all your received payments"
                :back-href="provider.payments.index().url" />

            <!-- Filters -->
            <ConsoleFormCard class="mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <InputText v-model="searchQuery" placeholder="Search by booking reference or customer..."
                            class="w-full" />
                    </div>
                    <Select v-model="currentStatus" :options="[
                        { value: 'all', label: `All (${counts.all})` },
                        { value: 'completed', label: `Completed (${counts.completed})` },
                        { value: 'pending', label: `Pending (${counts.pending})` },
                        { value: 'failed', label: `Failed (${counts.failed})` },
                    ]" optionLabel="label" optionValue="value" class="w-full sm:w-48" />
                </div>
            </ConsoleFormCard>

            <!-- Payments List -->
            <ConsoleEmptyState v-if="!payments.data?.length" icon="pi pi-credit-card" title="No payments found"
                description="Payments matching your filters will appear here." />

            <div v-else class="space-y-4">
                <div v-for="payment in payments.data" :key="payment.uuid" class="payment-card">
                    <!-- Card Header -->
                    <div class="card-header">
                        <div class="header-left">
                            <div class="service-icon">
                                <i class="pi pi-briefcase" />
                            </div>
                            <div class="header-info">
                                <h3 class="service-name">{{ payment.service_name }}</h3>
                                <p class="client-info">{{ payment.client_name }} • {{ payment.booking_date }}</p>
                            </div>
                        </div>
                        <Tag :value="payment.status_label" :severity="getStatusSeverity(payment.status)" />
                    </div>

                    <!-- Card Body - Two Column Layout -->
                    <div class="card-body">
                        <div class="amount-section">
                            <div class="section-title">Amount Breakdown</div>
                            <div class="amount-rows">
                                <div class="amount-row">
                                    <span class="amount-label">Total Charged</span>
                                    <span class="amount-value">{{ payment.amount_display }}</span>
                                </div>
                                <div class="amount-row">
                                    <span class="amount-label">Platform Fee</span>
                                    <span class="amount-value text-gray-500">-{{ payment.platform_fee_display }}</span>
                                </div>
                                <div v-if="payment.card_display" class="amount-row">
                                    <span class="amount-label">Card</span>
                                    <span class="amount-value text-gray-500">{{ payment.card_display }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="earnings-section">
                            <div class="section-title">Provider Earnings</div>
                            <div class="earnings-amount">
                                <span class="earnings-label">You Receive</span>
                                <span class="earnings-value">{{ payment.provider_amount_display }}</span>
                            </div>
                            <div v-if="payment.refunded_at" class="refund-badge">
                                <i class="pi pi-replay" />
                                <span>Refunded {{ payment.refunded_at }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer - Action Buttons -->
                    <div class="card-footer">
                        <AppLink :href="provider.payments.show({ uuid: payment.uuid }).url" class="no-underline">
                            <Button label="View Details" icon="pi pi-file" size="small" severity="secondary" text />
                        </AppLink>
                        <div class="footer-actions">
                            <AppLink :href="provider.bookings.show({ uuid: payment.booking_uuid }).url" class="no-underline">
                                <Button label="View Booking" icon="pi pi-calendar" size="small" severity="secondary" outlined />
                            </AppLink>
                            <Button v-if="canRefund(payment)" label="Refund" icon="pi pi-replay" size="small"
                                severity="warn" outlined @click="openRefundDialog(payment)" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="payments.meta?.last_page && payments.meta.last_page > 1" class="flex justify-center gap-2 mt-6">
                <AppLink v-for="link in payments.meta.links" :key="link.label" :href="link.url || '#'" :class="[
                    'px-3 py-2 rounded-lg text-sm no-underline',
                    link.active ? 'bg-[#106B4F] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                    !link.url && 'opacity-50 pointer-events-none',
                ]" v-html="link.label" />
            </div>
        </div>

        <!-- Refund Dialog -->
        <Dialog v-model:visible="showRefundDialog" header="Refund Payment" :modal="true" :closable="true"
            class="w-full max-w-md">
            <div v-if="selectedPayment" class="space-y-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500 m-0 mb-1">Original Payment</p>
                    <p class="font-semibold text-[#0D1F1B] m-0">{{ selectedPayment.amount_display }}</p>
                    <p class="text-sm text-gray-500 m-0">{{ selectedPayment.service_name }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" :value="false" v-model="refundForm.isPartial" class="accent-[#106B4F]" />
                        <span class="text-sm">Full Refund</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" :value="true" v-model="refundForm.isPartial" class="accent-[#106B4F]" />
                        <span class="text-sm">Partial Refund</span>
                    </label>
                </div>

                <div v-if="refundForm.isPartial">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Refund Amount
                    </label>
                    <InputNumber v-model="refundForm.amount" mode="currency" currency="USD"
                        :max="selectedPayment.amount" :min="0.01" class="w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Reason for Refund *
                    </label>
                    <Textarea v-model="refundForm.reason" rows="3" class="w-full"
                        placeholder="Explain why you're issuing this refund..." />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showRefundDialog = false" />
                <Button label="Process Refund" icon="pi pi-check" severity="warn" :disabled="!refundForm.reason"
                    @click="submitRefund" />
            </template>
        </Dialog>
    </ConsoleLayout>
</template>

<style scoped>
/* Payment Card */
.payment-card {
    background: white;
    border-radius: 0.875rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

/* Card Header */
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.service-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    background: rgba(16, 107, 79, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.service-icon i {
    font-size: 1rem;
    color: #106B4F;
}

.header-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.service-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0;
}

.client-info {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0;
}

/* Card Body */
.card-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    padding: 1.25rem;
}

@media (max-width: 640px) {
    .card-body {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

.section-title {
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #9ca3af;
    margin-bottom: 0.75rem;
    font-weight: 500;
}

/* Amount Section */
.amount-section {
    border-right: 1px solid #f3f4f6;
    padding-right: 1.5rem;
}

@media (max-width: 640px) {
    .amount-section {
        border-right: none;
        border-bottom: 1px solid #f3f4f6;
        padding-right: 0;
        padding-bottom: 1rem;
    }
}

.amount-rows {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.amount-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.amount-label {
    font-size: 0.8125rem;
    color: #4b5563;
}

.amount-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0D1F1B;
}

/* Earnings Section */
.earnings-section {
    display: flex;
    flex-direction: column;
}

.earnings-amount {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.earnings-label {
    font-size: 0.8125rem;
    color: #6b7280;
}

.earnings-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #106B4F;
}

.refund-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    margin-top: 0.75rem;
    padding: 0.375rem 0.625rem;
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    width: fit-content;
}

.refund-badge i {
    font-size: 0.625rem;
}

/* Card Footer */
.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    background: #fafafa;
    border-top: 1px solid #f3f4f6;
}

.footer-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 640px) {
    .card-footer {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }

    .footer-actions {
        justify-content: flex-end;
    }
}
</style>

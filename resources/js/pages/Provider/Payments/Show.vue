<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleAlertBanner,
} from '@/components/console';
import AppLink from '@/components/common/AppLink.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface PaymentData {
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
    processing_fee: number;
    currency: string;
    payment_type: 'deposit' | 'full' | 'balance';
    gateway: string;
    card_display: string | null;
    status: string;
    status_label: string;
    status_color: string;
    status_icon: string;
    is_completed: boolean;
    is_pending: boolean;
    is_failed: boolean;
    can_refund: boolean;
    is_refunded: boolean;
    failure_reason: string | null;
    refund_reason: string | null;
    refund_transaction_id: string | null;
    paid_at: string | null;
    refunded_at: string | null;
    created_at: string;
    gateway_type?: string;
    gateway_provider?: string;
    gateway_transaction_id?: string;
    gateway_order_id?: string;
    processing_fee_payer?: string;
    client?: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
    };
    booking?: {
        id: number;
        uuid: string;
        booking_date: string;
        formatted_date: string;
        start_time: string;
        end_time: string;
        status: string;
        service_name: string;
        service_duration: number;
        guest_name: string | null;
        guest_email: string | null;
    };
}

interface Props {
    payment: PaymentData;
}

const props = defineProps<Props>();
const toast = useToast();

// Refund dialog state
const showRefundDialog = ref(false);
const isProcessingRefund = ref(false);
const refundForm = ref({
    amount: props.payment.amount,
    reason: '',
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

const getStatusIcon = (status: string): string => {
    switch (status) {
        case 'completed':
            return 'pi pi-check-circle text-green-600';
        case 'pending':
        case 'processing':
            return 'pi pi-clock text-yellow-600';
        case 'failed':
            return 'pi pi-times-circle text-red-600';
        case 'refunded':
        case 'partially_refunded':
            return 'pi pi-replay text-gray-500';
        default:
            return 'pi pi-info-circle text-blue-600';
    }
};

const getStatusBgClass = (status: string): string => {
    switch (status) {
        case 'completed':
            return 'bg-green-50';
        case 'pending':
        case 'processing':
            return 'bg-yellow-50';
        case 'failed':
            return 'bg-red-50';
        case 'refunded':
        case 'partially_refunded':
            return 'bg-gray-100';
        default:
            return 'bg-blue-50';
    }
};

const paymentTypeLabel = computed(() => {
    switch (props.payment.payment_type) {
        case 'deposit':
            return 'Deposit Payment';
        case 'balance':
            return 'Balance Payment';
        default:
            return 'Full Payment';
    }
});

const clientDisplayName = computed(() => {
    return props.payment.client?.name ||
           props.payment.booking?.guest_name ||
           props.payment.client_name ||
           'Guest';
});

const clientEmail = computed(() => {
    return props.payment.client?.email ||
           props.payment.booking?.guest_email ||
           null;
});

const bookingUrl = computed(() => {
    if (!props.payment.booking_uuid) return null;
    return resolveUrl(provider.bookings.show({ uuid: props.payment.booking_uuid }).url);
});

const historyUrl = computed(() => {
    return resolveUrl(provider.payments.history().url);
});

const openRefundDialog = () => {
    refundForm.value = {
        amount: props.payment.amount,
        reason: '',
    };
    showRefundDialog.value = true;
};

const setMaxRefund = () => {
    refundForm.value.amount = props.payment.amount;
};

const processRefund = () => {
    if (!refundForm.value.reason.trim()) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Please provide a reason for the refund',
            life: 3000,
        });
        return;
    }

    isProcessingRefund.value = true;

    router.post(
        resolveUrl(provider.payments.refund({ uuid: props.payment.uuid }).url),
        {
            amount: refundForm.value.amount,
            reason: refundForm.value.reason,
        },
        {
            onSuccess: () => {
                showRefundDialog.value = false;
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Refund processed successfully',
                    life: 3000,
                });
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: errors.refund || 'Failed to process refund',
                    life: 5000,
                });
            },
            onFinish: () => {
                isProcessingRefund.value = false;
            },
        }
    );
};
</script>

<template>
    <ConsoleLayout title="Payment Details">
        <div class="w-full max-w-4xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Payment Details"
                :subtitle="`Payment #${payment.uuid.slice(0, 8).toUpperCase()}`"
                :back-href="historyUrl"
            />

            <!-- Status Banner -->
            <div :class="['rounded-xl p-6 mb-6', getStatusBgClass(payment.status)]">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <i :class="['text-2xl', getStatusIcon(payment.status)]" />
                            <Tag
                                :value="payment.status_label"
                                :severity="getStatusSeverity(payment.status)"
                            />
                            <Tag
                                :value="paymentTypeLabel"
                                severity="info"
                                class="ml-1"
                            />
                        </div>
                        <p class="text-3xl font-bold text-[#0D1F1B] m-0">
                            {{ payment.amount_display }}
                        </p>
                        <p class="text-sm text-gray-500 m-0 mt-1">
                            {{ payment.service_name }} &bull; {{ payment.booking_date }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p v-if="payment.paid_at" class="text-sm text-gray-500 m-0">
                            Paid: {{ payment.paid_at }}
                        </p>
                        <p v-if="payment.card_display" class="text-sm text-gray-500 m-0 mt-1">
                            {{ payment.card_display }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Failure Alert -->
            <ConsoleAlertBanner
                v-if="payment.is_failed && payment.failure_reason"
                variant="danger"
                class="mb-6"
            >
                <strong>Payment Failed:</strong> {{ payment.failure_reason }}
            </ConsoleAlertBanner>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Left Column (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Booking Details -->
                    <ConsoleFormCard title="Booking Details" icon="pi pi-calendar">
                        <div class="space-y-4">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                                    <i class="pi pi-briefcase text-xl text-[#106B4F]" />
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-[#0D1F1B] m-0 text-lg">
                                        {{ payment.service_name }}
                                    </p>
                                    <p class="text-gray-500 m-0 text-sm">
                                        {{ payment.booking?.service_duration }} minutes
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-2">
                                <div>
                                    <span class="text-gray-500 text-sm block mb-1">Date & Time</span>
                                    <p class="font-medium text-[#0D1F1B] m-0">
                                        {{ payment.booking?.formatted_date || payment.booking_date }}
                                    </p>
                                    <p class="text-sm text-gray-500 m-0">
                                        {{ payment.booking?.start_time }}
                                        <template v-if="payment.booking?.end_time">
                                            - {{ payment.booking.end_time }}
                                        </template>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-500 text-sm block mb-1">Client</span>
                                    <p class="font-medium text-[#0D1F1B] m-0">
                                        {{ clientDisplayName }}
                                    </p>
                                    <p v-if="clientEmail" class="text-sm text-gray-500 m-0">
                                        {{ clientEmail }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="bookingUrl" class="pt-2">
                                <AppLink :href="bookingUrl">
                                    <Button
                                        label="View Full Booking"
                                        icon="pi pi-external-link"
                                        severity="secondary"
                                        outlined
                                        size="small"
                                    />
                                </AppLink>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Amount Breakdown -->
                    <ConsoleFormCard title="Amount Breakdown" icon="pi pi-wallet">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600">Total Charged</span>
                                <span class="font-semibold text-[#0D1F1B]">{{ payment.amount_display }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-t border-gray-100">
                                <span class="text-gray-600">Platform Fee</span>
                                <span class="text-gray-600">-{{ payment.platform_fee_display }}</span>
                            </div>
                            <div v-if="payment.processing_fee > 0" class="flex justify-between items-center py-2 border-t border-gray-100">
                                <span class="text-gray-600">
                                    Processing Fee
                                    <span v-if="payment.processing_fee_payer" class="text-xs text-gray-400">
                                        ({{ payment.processing_fee_payer === 'client' ? 'paid by client' : 'absorbed' }})
                                    </span>
                                </span>
                                <span class="text-gray-600">-${{ payment.processing_fee.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-t-2 border-gray-200 bg-green-50 -mx-5 px-5 -mb-5 rounded-b-xl">
                                <span class="font-semibold text-[#0D1F1B]">You Receive</span>
                                <span class="font-bold text-xl text-[#106B4F]">{{ payment.provider_amount_display }}</span>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Refund Section -->
                    <ConsoleFormCard
                        v-if="payment.is_refunded"
                        title="Refund Details"
                        icon="pi pi-replay"
                        variant="secondary"
                    >
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 mb-4">
                                <Tag
                                    :value="payment.status === 'partially_refunded' ? 'Partial Refund' : 'Full Refund'"
                                    severity="secondary"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-gray-500 text-sm block mb-1">Refund Amount</span>
                                    <p class="font-semibold text-[#0D1F1B] m-0">{{ payment.amount_display }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-500 text-sm block mb-1">Refunded On</span>
                                    <p class="font-medium text-[#0D1F1B] m-0">{{ payment.refunded_at }}</p>
                                </div>
                            </div>
                            <div v-if="payment.refund_reason">
                                <span class="text-gray-500 text-sm block mb-1">Reason</span>
                                <p class="text-[#0D1F1B] m-0 italic">"{{ payment.refund_reason }}"</p>
                            </div>
                            <div v-if="payment.refund_transaction_id">
                                <span class="text-gray-500 text-sm block mb-1">Transaction ID</span>
                                <p class="font-mono text-sm text-gray-600 m-0">{{ payment.refund_transaction_id }}</p>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Refund Action -->
                    <ConsoleFormCard
                        v-else-if="payment.can_refund"
                        title="Process Refund"
                        icon="pi pi-replay"
                    >
                        <p class="text-gray-600 m-0 mb-4">
                            Issue a full or partial refund to the customer. This action cannot be undone.
                        </p>
                        <Button
                            label="Process Refund"
                            icon="pi pi-replay"
                            severity="warn"
                            @click="openRefundDialog"
                        />
                    </ConsoleFormCard>
                </div>

                <!-- Right Column (1 col) -->
                <div class="space-y-6">
                    <!-- Payment Info -->
                    <ConsoleFormCard title="Payment Info" icon="pi pi-info-circle">
                        <div class="space-y-4">
                            <div>
                                <span class="text-gray-500 text-xs uppercase tracking-wide block mb-1">Payment ID</span>
                                <p class="font-mono text-sm text-[#0D1F1B] m-0">{{ payment.uuid.slice(0, 8).toUpperCase() }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 text-xs uppercase tracking-wide block mb-1">Type</span>
                                <p class="text-[#0D1F1B] m-0">{{ paymentTypeLabel }}</p>
                            </div>
                            <div v-if="payment.gateway_provider">
                                <span class="text-gray-500 text-xs uppercase tracking-wide block mb-1">Gateway</span>
                                <p class="text-[#0D1F1B] m-0 capitalize">{{ payment.gateway_provider }}</p>
                            </div>
                            <div v-if="payment.gateway_type">
                                <span class="text-gray-500 text-xs uppercase tracking-wide block mb-1">Gateway Type</span>
                                <p class="text-[#0D1F1B] m-0 capitalize">{{ payment.gateway_type }}</p>
                            </div>
                            <div v-if="payment.gateway_transaction_id">
                                <span class="text-gray-500 text-xs uppercase tracking-wide block mb-1">Transaction ID</span>
                                <p class="font-mono text-xs text-gray-600 m-0 break-all">{{ payment.gateway_transaction_id }}</p>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Timeline -->
                    <ConsoleFormCard title="Timeline" icon="pi pi-clock">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                    <i class="pi pi-plus text-xs text-gray-500" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-[#0D1F1B] m-0">Created</p>
                                    <p class="text-xs text-gray-500 m-0">{{ payment.created_at }}</p>
                                </div>
                            </div>

                            <div v-if="payment.paid_at" class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                                    <i class="pi pi-check text-xs text-green-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-[#0D1F1B] m-0">Paid</p>
                                    <p class="text-xs text-gray-500 m-0">{{ payment.paid_at }}</p>
                                </div>
                            </div>

                            <div v-if="payment.refunded_at" class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center shrink-0">
                                    <i class="pi pi-replay text-xs text-gray-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-[#0D1F1B] m-0">Refunded</p>
                                    <p class="text-xs text-gray-500 m-0">{{ payment.refunded_at }}</p>
                                </div>
                            </div>

                            <div v-if="payment.is_failed" class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                    <i class="pi pi-times text-xs text-red-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-[#0D1F1B] m-0">Failed</p>
                                    <p class="text-xs text-gray-500 m-0">{{ payment.created_at }}</p>
                                </div>
                            </div>
                        </div>
                    </ConsoleFormCard>
                </div>
            </div>
        </div>

        <!-- Refund Dialog -->
        <Dialog
            v-model:visible="showRefundDialog"
            modal
            header="Process Refund"
            :style="{ width: '28rem' }"
            :closable="!isProcessingRefund"
        >
            <div class="space-y-4">
                <p class="text-gray-600 m-0">
                    Issue a refund for this payment. The customer will be refunded to their original payment method.
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Refund Amount
                    </label>
                    <div class="flex items-center gap-2">
                        <InputNumber
                            v-model="refundForm.amount"
                            mode="currency"
                            currency="USD"
                            :min="0.01"
                            :max="payment.amount"
                            class="flex-1"
                        />
                        <Button
                            label="Max"
                            severity="secondary"
                            size="small"
                            @click="setMaxRefund"
                        />
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Original amount: {{ payment.amount_display }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Refund <span class="text-red-500">*</span>
                    </label>
                    <Textarea
                        v-model="refundForm.reason"
                        placeholder="Enter the reason for this refund..."
                        rows="3"
                        class="w-full"
                    />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button
                        label="Cancel"
                        severity="secondary"
                        :disabled="isProcessingRefund"
                        @click="showRefundDialog = false"
                    />
                    <Button
                        label="Process Refund"
                        severity="warn"
                        icon="pi pi-replay"
                        :loading="isProcessingRefund"
                        @click="processRefund"
                    />
                </div>
            </template>
        </Dialog>
    </ConsoleLayout>
</template>

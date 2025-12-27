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
                <ConsoleDataCard v-for="payment in payments.data" :key="payment.uuid">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                            <i class="pi pi-credit-card text-[#106B4F]" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <h3 class="font-semibold text-[#0D1F1B] m-0">{{ payment.service_name }}</h3>
                                <Tag :value="payment.status_label" :severity="getStatusSeverity(payment.status)"
                                    class="!text-xs" />
                            </div>
                            <p class="text-sm text-gray-500 m-0">
                                {{ payment.client_name }} &bull; {{ payment.booking_date }}
                            </p>
                            <p v-if="payment.card_display" class="text-xs text-gray-400 m-0 mt-1">
                                {{ payment.card_display }}
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="font-semibold text-[#106B4F] m-0 text-lg">
                                {{ payment.provider_amount_display }}
                            </p>
                            <p class="text-xs text-gray-400 m-0">
                                of {{ payment.amount_display }}
                            </p>
                            <p class="text-xs text-gray-400 m-0">
                                Fee: {{ payment.platform_fee_display }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <AppLink :href="provider.bookings.show({ uuid: payment.booking_uuid }).url">
                                <Button icon="pi pi-eye" size="small" severity="secondary" outlined
                                    v-tooltip="'View Booking'" />
                            </AppLink>
                            <Button v-if="canRefund(payment)" icon="pi pi-replay" size="small" severity="warn" outlined
                                v-tooltip="'Refund'" @click="openRefundDialog(payment)" />
                        </div>
                    </div>

                    <template #footer>
                        <div class="flex items-center gap-4 text-xs text-gray-400">
                            <span v-if="payment.paid_at">
                                <i class="pi pi-check mr-1" />
                                Paid {{ payment.paid_at }}
                            </span>
                            <span v-if="payment.refunded_at">
                                <i class="pi pi-replay mr-1" />
                                Refunded {{ payment.refunded_at }}
                            </span>
                            <span v-else>
                                <i class="pi pi-clock mr-1" />
                                Created {{ payment.created_at }}
                            </span>
                        </div>
                    </template>
                </ConsoleDataCard>
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

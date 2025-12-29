<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ClientLayout from '@/components/layout/ClientLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import type { Booking } from '@/types/models/booking';
import payment from '@/routes/payment';
import { resolveUrl } from '@/utils/url';

interface Props {
    booking: Booking;
}

const props = defineProps<Props>();
const toast = useToast();

const showCancelDialog = ref(false);
const cancelForm = useForm({
    reason: '',
});

const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'success';
        case 'completed': return 'info';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getRelativeDate = (dateStr: string) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const date = new Date(dateStr);
    date.setHours(0, 0, 0, 0);
    const diffDays = Math.ceil((date.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Tomorrow';
    if (diffDays < 7 && diffDays > 0) return `In ${diffDays} days`;
    return null;
};

const relativeDate = computed(() => getRelativeDate(props.booking.booking_date));

const submitCancel = () => {
    cancelForm.post(`/dashboard/bookings/${props.booking.uuid}/cancel`, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Booking Cancelled',
                detail: 'Your booking has been cancelled.',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to cancel booking.',
                life: 3000,
            });
        },
    });
};

const timelineEvents = computed(() => {
    const events = [
        {
            icon: 'pi pi-plus',
            iconBg: 'bg-gray-100',
            iconColor: 'text-gray-500',
            title: 'Booking Created',
            date: props.booking.created_at,
            show: true,
        },
        {
            icon: 'pi pi-check',
            iconBg: 'bg-[#106B4F]/10',
            iconColor: 'text-[#106B4F]',
            title: 'Confirmed',
            date: props.booking.confirmed_at,
            show: !!props.booking.confirmed_at,
        },
        {
            icon: 'pi pi-check-circle',
            iconBg: 'bg-blue-50',
            iconColor: 'text-blue-500',
            title: 'Completed',
            date: props.booking.completed_at,
            show: !!props.booking.completed_at,
        },
        {
            icon: 'pi pi-times',
            iconBg: 'bg-red-50',
            iconColor: 'text-red-500',
            title: 'Cancelled',
            date: props.booking.cancelled_at,
            show: !!props.booking.cancelled_at,
        },
    ];
    return events.filter(e => e.show);
});
</script>

<template>
    <ClientLayout :title="`Booking - ${booking.service?.name}`">
        <div class="min-h-screen bg-gray-50/50">
            <div class="max-w-2xl mx-auto px-4 py-8">
                <!-- Back Navigation -->
                <AppLink href="/dashboard/bookings"
                    class="inline-flex items-center gap-2 text-gray-500 hover:text-[#106B4F] mb-6 no-underline transition-colors">
                    <i class="pi pi-arrow-left text-sm"></i>
                    <span class="text-sm font-medium">Back to Bookings</span>
                </AppLink>

                <!-- Hero Card -->
                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-6">
                    <!-- Status Banner -->
                    <div v-if="relativeDate && !booking.is_past && booking.status !== 'cancelled'"
                        class="px-5 py-3 bg-gradient-to-r from-[#106B4F] to-[#0D5A42] text-white">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">
                                <i class="pi pi-star-fill mr-2 text-sm"></i>
                                {{ relativeDate }}
                            </span>
                            <span class="text-white/80 text-sm">{{ booking.formatted_time }}</span>
                        </div>
                    </div>

                    <!-- Provider Info -->
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <Avatar v-if="booking.provider?.avatar" :image="booking.provider.avatar" shape="circle"
                                class="!w-16 !h-16" />
                            <Avatar v-else :label="getInitials(booking.provider?.business_name || '')" shape="circle"
                                class="!w-16 !h-16 !bg-[#106B4F] !text-white !text-xl" />
                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h1 class="text-xl font-semibold text-[#0D1F1B] m-0">{{ booking.service?.name }}
                                        </h1>
                                        <AppLink :href="`/providers/${booking.provider?.slug}`"
                                            class="text-[#106B4F] hover:underline no-underline text-sm font-medium">
                                            {{ booking.provider?.business_name }}
                                        </AppLink>
                                    </div>
                                    <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)"
                                        class="!rounded-full !px-3" />
                                </div>
                            </div>
                        </div>

                        <!-- Quick Info -->
                        <div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t border-gray-100">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                                    <i class="pi pi-calendar text-[#106B4F]"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 m-0">Date</p>
                                    <p class="font-medium text-[#0D1F1B] m-0 text-sm">{{ booking.formatted_date }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                                    <i class="pi pi-clock text-[#106B4F]"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 m-0">Time</p>
                                    <p class="font-medium text-[#0D1F1B] m-0 text-sm">{{ booking.formatted_time }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                                    <i class="pi pi-stopwatch text-blue-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 m-0">Duration</p>
                                    <p class="font-medium text-[#0D1F1B] m-0 text-sm">{{
                                        booking.service?.duration_minutes }} min</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                                    <i class="pi pi-wallet text-amber-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 m-0">Total</p>
                                    <p class="font-semibold text-[#0D1F1B] m-0 text-sm">{{ booking.total_display }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Alert -->
                <div v-if="booking.can_pay"
                    class="flex items-center gap-4 p-5 bg-amber-50 rounded-2xl border border-amber-100 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                        <i class="pi pi-credit-card text-amber-600 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-amber-800 m-0">Complete Your Payment</p>
                        <p class="text-amber-600 m-0 text-sm">Pay ${{ booking.deposit_amount.toFixed(2) }} deposit to
                            confirm your booking</p>
                    </div>
                    <AppLink :href="resolveUrl(payment.checkout({ bookingUuid: booking.uuid }).url)">
                        <Button label="Pay Now" icon="pi pi-arrow-right" iconPos="right"
                            class="!bg-amber-500 !border-amber-500 !rounded-full" />
                    </AppLink>
                </div>

                <!-- Location Card -->
                <div v-if="booking.provider?.address" class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                            <i class="pi pi-map-marker text-[#106B4F] text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-[#0D1F1B] m-0">Location</p>
                            <p class="text-gray-600 m-0 text-sm mt-1">{{ booking.provider.address }}</p>
                        </div>
                        <AppLink :href="`https://maps.google.com/?q=${encodeURIComponent(booking.provider.address)}`"
                            target="_blank" class="shrink-0">
                            <Button label="Directions" icon="pi pi-external-link" text class="!text-[#106B4F]" />
                        </AppLink>
                    </div>
                </div>

                <!-- Notes Section -->
                <div v-if="booking.service?.description || booking.client_notes || booking.provider_notes"
                    class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-[#0D1F1B] m-0">Details</h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="booking.service?.description">
                            <p class="text-xs text-gray-500 uppercase tracking-wide m-0 mb-1">Service Description</p>
                            <p class="text-gray-700 m-0 text-sm">{{ booking.service.description }}</p>
                        </div>
                        <div v-if="booking.client_notes" class="pt-4 border-t border-gray-50">
                            <p class="text-xs text-gray-500 uppercase tracking-wide m-0 mb-1">Your Notes</p>
                            <p class="text-gray-700 m-0 text-sm">{{ booking.client_notes }}</p>
                        </div>
                        <div v-if="booking.provider_notes" class="pt-4 border-t border-gray-50">
                            <p class="text-xs text-gray-500 uppercase tracking-wide m-0 mb-1">Provider Notes</p>
                            <p class="text-gray-700 m-0 text-sm">{{ booking.provider_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Reason -->
                <div v-if="booking.cancellation_reason" class="bg-red-50 rounded-2xl border border-red-100 p-5 mb-6">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                            <i class="pi pi-times text-red-500"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-red-800 m-0">Cancellation Reason</p>
                            <p class="text-red-600 m-0 text-sm mt-1">{{ booking.cancellation_reason }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Breakdown -->
                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="font-semibold text-[#0D1F1B] m-0">Payment Summary</h2>
                        <div v-if="booking.payment" class="flex items-center gap-1.5 text-[#106B4F] text-sm">
                            <i class="pi pi-check-circle text-xs"></i>
                            <span>Paid</span>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Service</span>
                                <span class="font-medium text-[#0D1F1B]">${{ booking.service_price.toFixed(2) }}</span>
                            </div>

                            <div v-if="booking.fee_payer === 'client' && booking.convenience_fee > 0"
                                class="flex justify-between text-sm">
                                <span class="text-gray-600">Service Fee</span>
                                <span class="font-medium text-[#0D1F1B]">${{ booking.convenience_fee.toFixed(2)
                                    }}</span>
                            </div>

                            <template v-if="booking.requires_deposit">
                                <div class="h-px bg-gray-100 my-2"></div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Deposit</span>
                                    <span class="font-medium"
                                        :class="booking.deposit_paid ? 'text-[#106B4F]' : 'text-amber-600'">
                                        ${{ booking.deposit_amount.toFixed(2) }}
                                        <span class="text-xs ml-1">({{ booking.deposit_paid ? 'Paid' : 'Due' }})</span>
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Balance at appointment</span>
                                    <span class="font-medium text-[#0D1F1B]">${{ booking.balance_amount.toFixed(2)
                                        }}</span>
                                </div>
                            </template>

                            <div class="h-px bg-gray-200 my-3"></div>

                            <div class="flex justify-between">
                                <span class="font-semibold text-[#0D1F1B]">Total</span>
                                <span class="font-bold text-lg text-[#0D1F1B]">{{ booking.total_display }}</span>
                            </div>
                        </div>

                        <div v-if="booking.payment" class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i class="pi pi-check-circle text-[#106B4F]"></i>
                                <span>{{ booking.payment.payment_type === 'deposit' ? 'Deposit' : 'Payment' }} received
                                    on {{
                                    booking.payment.paid_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-[#0D1F1B] m-0">Timeline</h2>
                    </div>
                    <div class="p-5">
                        <div class="relative">
                            <!-- Timeline line -->
                            <div class="absolute left-4 top-4 bottom-4 w-px bg-gray-200"></div>

                            <div class="space-y-6">
                                <div v-for="(event, index) in timelineEvents" :key="index"
                                    class="flex items-start gap-4 relative">
                                    <div
                                        :class="[event.iconBg, 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 relative z-10']">
                                        <i :class="[event.icon, event.iconColor, 'text-sm']"></i>
                                    </div>
                                    <div class="pt-1">
                                        <p class="font-medium text-[#0D1F1B] m-0 text-sm">{{ event.title }}</p>
                                        <p class="text-gray-500 m-0 text-xs">{{ event.date }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <AppLink :href="`/providers/${booking.provider?.slug}`" class="flex-1">
                        <Button label="View Provider" icon="pi pi-user" outlined
                            class="!border-[#106B4F] !text-[#106B4F] !rounded-full w-full" />
                    </AppLink>
                    <Button v-if="booking.can_cancel" label="Cancel Booking" icon="pi pi-times" severity="danger"
                        outlined class="!rounded-full flex-1" @click="showCancelDialog = true" />
                </div>
            </div>
        </div>

        <!-- Cancel Dialog -->
        <Dialog v-model:visible="showCancelDialog" modal :style="{ width: '420px' }" :pt="{
            root: { class: '!rounded-2xl overflow-hidden' },
            header: { class: '!p-5 !border-b !border-gray-100' },
            content: { class: '!p-5' },
            footer: { class: '!p-5 !pt-0' }
        }">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center">
                        <i class="pi pi-exclamation-triangle text-red-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[#0D1F1B] m-0">Cancel Booking</h3>
                        <p class="text-gray-500 text-sm m-0">This action cannot be undone</p>
                    </div>
                </div>
            </template>

            <div class="space-y-4">
                <p class="text-gray-600 m-0 text-sm">
                    Please let us know why you're cancelling. This helps the provider improve their service.
                </p>
                <div>
                    <label for="cancel_reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for cancellation
                    </label>
                    <Textarea id="cancel_reason" v-model="cancelForm.reason" rows="3" class="w-full !rounded-xl"
                        :class="{ 'p-invalid': cancelForm.errors.reason }"
                        placeholder="e.g., Schedule conflict, changed plans..." />
                    <small v-if="cancelForm.errors.reason" class="text-red-500">{{ cancelForm.errors.reason }}</small>
                </div>
            </div>

            <template #footer>
                <div class="flex gap-3 w-full">
                    <Button label="Keep Booking" outlined class="!rounded-full flex-1"
                        @click="showCancelDialog = false" />
                    <Button label="Cancel Booking" severity="danger" class="!rounded-full flex-1"
                        :loading="cancelForm.processing" :disabled="!cancelForm.reason" @click="submitCancel" />
                </div>
            </template>
        </Dialog>
    </ClientLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/components/layout/DashboardLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';

interface Props {
    booking: {
        id: number;
        uuid: string;
        provider: {
            business_name: string;
            slug: string;
            avatar?: string;
            location?: string;
            address?: string;
        };
        service: {
            name: string;
            description?: string;
            duration_minutes: number;
        };
        booking_date: string;
        formatted_date: string;
        formatted_time: string;
        status: string;
        status_label: string;
        status_color: string;
        service_price: number;
        total_amount: number;
        total_display: string;
        client_notes?: string;
        provider_notes?: string;
        cancellation_reason?: string;
        is_past: boolean;
        is_today: boolean;
        can_cancel: boolean;
        confirmed_at?: string;
        completed_at?: string;
        cancelled_at?: string;
        created_at: string;
        is_guest_booking: boolean;
        client_name: string;
        client_email: string;
        requires_deposit: boolean;
        deposit_amount: number;
        deposit_paid: boolean;
        balance_amount: number;
        platform_fee_amount: number;
        processing_fee_amount: number;
        can_pay: boolean;
        payment?: {
            status: string;
            amount: number;
            payment_type: string;
            paid_at?: string;
        };
    };
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
</script>

<template>
    <DashboardLayout :title="`Booking - ${booking.service.name}`">
        <div class="max-w-3xl mx-auto px-4 py-8">
            <!-- Back Link -->
            <Link href="/dashboard/bookings" class="inline-flex items-center gap-2 text-gray-500 hover:text-[#0D1F1B] mb-6 no-underline">
                <i class="pi pi-arrow-left"></i>
                <span>Back to Bookings</span>
            </Link>

            <!-- Status Banner -->
            <div
                v-if="booking.can_pay"
                class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 bg-yellow-50 border border-yellow-200 rounded-xl mb-6"
            >
                <div class="flex items-center gap-3">
                    <i class="pi pi-exclamation-circle text-yellow-600 text-xl"></i>
                    <div>
                        <p class="font-medium text-yellow-800 m-0">Deposit Payment Required</p>
                        <p class="text-sm text-yellow-700 m-0">Pay ${{ booking.deposit_amount.toFixed(2) }} to secure your booking</p>
                    </div>
                </div>
                <Link :href="`/payment/${booking.uuid}/checkout`">
                    <Button label="Pay Now" icon="pi pi-credit-card" class="!bg-yellow-600 !border-yellow-600" />
                </Link>
            </div>

            <div
                v-if="booking.is_today && !booking.is_past && booking.status === 'confirmed'"
                class="flex items-center gap-3 px-5 py-4 bg-[#106B4F]/10 border border-[#106B4F]/20 rounded-xl mb-6"
            >
                <i class="pi pi-star-fill text-[#106B4F] text-xl"></i>
                <div>
                    <p class="font-medium text-[#106B4F] m-0">Your appointment is today!</p>
                    <p class="text-sm text-[#106B4F]/80 m-0">{{ booking.formatted_time }} at {{ booking.provider.location || booking.provider.address }}</p>
                </div>
            </div>

            <!-- Main Content -->
            <div class="space-y-6">
                <!-- Provider & Status Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <Avatar
                                    v-if="booking.provider.avatar"
                                    :image="booking.provider.avatar"
                                    shape="circle"
                                    class="!w-14 !h-14"
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(booking.provider.business_name)"
                                    shape="circle"
                                    class="!w-14 !h-14 !bg-[#106B4F] !text-lg"
                                />
                                <div>
                                    <h1 class="text-xl font-semibold text-[#0D1F1B] m-0">{{ booking.service.name }}</h1>
                                    <Link :href="`/providers/${booking.provider.slug}`" class="text-[#106B4F] hover:underline no-underline">
                                        {{ booking.provider.business_name }}
                                    </Link>
                                </div>
                            </div>
                            <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" class="!text-sm !px-3 !py-1.5" />
                        </div>
                    </div>
                </div>

                <!-- Booking Details Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Appointment Details</h2>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="text-sm text-gray-500 block mb-1">Date</label>
                                <p class="font-medium text-[#0D1F1B] m-0 flex items-center gap-2">
                                    <i class="pi pi-calendar text-[#106B4F]"></i>
                                    {{ booking.formatted_date }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500 block mb-1">Time</label>
                                <p class="font-medium text-[#0D1F1B] m-0 flex items-center gap-2">
                                    <i class="pi pi-clock text-[#106B4F]"></i>
                                    {{ booking.formatted_time }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500 block mb-1">Duration</label>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ booking.service.duration_minutes }} minutes</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500 block mb-1">Location</label>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ booking.provider.address || booking.provider.location || 'Contact provider' }}</p>
                            </div>
                        </div>

                        <div v-if="booking.service.description" class="mt-6 pt-6 border-t border-gray-100">
                            <label class="text-sm text-gray-500 block mb-2">Service Description</label>
                            <p class="text-gray-700 m-0">{{ booking.service.description }}</p>
                        </div>

                        <div v-if="booking.client_notes" class="mt-6 pt-6 border-t border-gray-100">
                            <label class="text-sm text-gray-500 block mb-2">Your Notes</label>
                            <p class="text-gray-700 m-0">{{ booking.client_notes }}</p>
                        </div>

                        <div v-if="booking.provider_notes" class="mt-6 pt-6 border-t border-gray-100">
                            <label class="text-sm text-gray-500 block mb-2">Provider Notes</label>
                            <p class="text-gray-700 m-0">{{ booking.provider_notes }}</p>
                        </div>

                        <div v-if="booking.cancellation_reason" class="mt-6 pt-6 border-t border-gray-100">
                            <label class="text-sm text-gray-500 block mb-2">Cancellation Reason</label>
                            <p class="text-red-600 m-0">{{ booking.cancellation_reason }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Payment</h2>
                    </div>
                    <div class="p-5">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Service Price</span>
                                <span class="font-medium">${{ booking.service_price.toFixed(2) }}</span>
                            </div>

                            <template v-if="booking.requires_deposit">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Deposit</span>
                                    <span :class="booking.deposit_paid ? 'text-[#106B4F]' : 'text-yellow-600'" class="font-medium">
                                        ${{ booking.deposit_amount.toFixed(2) }}
                                        <span v-if="booking.deposit_paid" class="text-xs ml-1">(Paid)</span>
                                        <span v-else class="text-xs ml-1">(Pending)</span>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Balance Due at Appointment</span>
                                    <span class="font-medium">${{ booking.balance_amount.toFixed(2) }}</span>
                                </div>
                            </template>

                            <hr class="border-gray-200 my-2" />

                            <div class="flex justify-between text-base">
                                <span class="font-semibold text-[#0D1F1B]">Total</span>
                                <span class="font-bold text-[#0D1F1B]">{{ booking.total_display }}</span>
                            </div>
                        </div>

                        <div v-if="booking.payment" class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2 text-sm text-[#106B4F]">
                                <i class="pi pi-check-circle"></i>
                                <span>{{ booking.payment.payment_type === 'deposit' ? 'Deposit' : 'Payment' }} received on {{ booking.payment.paid_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Timeline</h2>
                    </div>
                    <div class="p-5">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                    <i class="pi pi-plus text-gray-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-[#0D1F1B] m-0">Booking Created</p>
                                    <p class="text-sm text-gray-500 m-0">{{ booking.created_at }}</p>
                                </div>
                            </div>

                            <div v-if="booking.confirmed_at" class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                                    <i class="pi pi-check text-[#106B4F] text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-[#0D1F1B] m-0">Confirmed</p>
                                    <p class="text-sm text-gray-500 m-0">{{ booking.confirmed_at }}</p>
                                </div>
                            </div>

                            <div v-if="booking.completed_at" class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                    <i class="pi pi-check-circle text-blue-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-[#0D1F1B] m-0">Completed</p>
                                    <p class="text-sm text-gray-500 m-0">{{ booking.completed_at }}</p>
                                </div>
                            </div>

                            <div v-if="booking.cancelled_at" class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                    <i class="pi pi-times text-red-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-[#0D1F1B] m-0">Cancelled</p>
                                    <p class="text-sm text-gray-500 m-0">{{ booking.cancelled_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-3">
                    <Link :href="`/providers/${booking.provider.slug}`">
                        <Button label="View Provider" icon="pi pi-user" outlined class="!border-[#106B4F] !text-[#106B4F]" />
                    </Link>
                    <Button
                        v-if="booking.can_cancel"
                        label="Cancel Booking"
                        icon="pi pi-times"
                        severity="danger"
                        outlined
                        @click="showCancelDialog = true"
                    />
                </div>
            </div>

            <!-- Cancel Dialog -->
            <Dialog
                v-model:visible="showCancelDialog"
                header="Cancel Booking"
                modal
                :style="{ width: '400px' }"
            >
                <div class="space-y-4">
                    <p class="text-gray-600 m-0">
                        Are you sure you want to cancel this booking? This action cannot be undone.
                    </p>
                    <div>
                        <label for="cancel_reason" class="block text-sm font-medium text-gray-700 mb-1">
                            Reason for cancellation *
                        </label>
                        <Textarea
                            id="cancel_reason"
                            v-model="cancelForm.reason"
                            rows="3"
                            class="w-full"
                            :class="{ 'p-invalid': cancelForm.errors.reason }"
                            placeholder="Please provide a reason for cancelling..."
                        />
                        <small v-if="cancelForm.errors.reason" class="text-red-500">{{ cancelForm.errors.reason }}</small>
                    </div>
                </div>
                <template #footer>
                    <Button label="Keep Booking" severity="secondary" @click="showCancelDialog = false" />
                    <Button
                        label="Cancel Booking"
                        severity="danger"
                        :loading="cancelForm.processing"
                        :disabled="!cancelForm.reason"
                        @click="submitCancel"
                    />
                </template>
            </Dialog>
        </div>
    </DashboardLayout>
</template>

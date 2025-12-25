<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import ProviderBookingController from '@/actions/App/Domains/Booking/Controllers/ProviderBookingController';

interface Props {
    booking: {
        id: number;
        uuid: string;
        client: {
            name: string;
            email: string;
            phone?: string;
            avatar?: string;
            is_guest: boolean;
        };
        service: {
            name: string;
            description?: string;
            duration_minutes: number;
            price: number;
        };
        booking_date: string;
        formatted_date: string;
        formatted_time: string;
        status: string;
        status_label: string;
        status_color: string;
        service_price: number;
        platform_fee: number;
        total_amount: number;
        total_display: string;
        client_notes?: string;
        provider_notes?: string;
        cancellation_reason?: string;
        is_past: boolean;
        is_today: boolean;
        can_confirm: boolean;
        can_complete: boolean;
        can_cancel: boolean;
        confirmed_at?: string;
        completed_at?: string;
        cancelled_at?: string;
        created_at: string;
        is_guest_booking: boolean;
        deposit_amount: number;
        deposit_paid: boolean;
        balance_amount: number;
        payment?: {
            uuid: string;
            amount: number;
            status: string;
            status_label: string;
            payment_type: string;
            card_display?: string;
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

const getStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast' | undefined => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        case 'no_show': return 'secondary';
        default: return 'secondary';
    }
};

const getInitials = (name: string) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const confirmBooking = () => {
    router.post(ProviderBookingController.confirm({ uuid: props.booking.uuid }).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Booking Confirmed',
                detail: 'The booking has been confirmed.',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to confirm booking.',
                life: 3000,
            });
        },
    });
};

const completeBooking = () => {
    router.post(ProviderBookingController.complete({ uuid: props.booking.uuid }).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Booking Completed',
                detail: 'The booking has been marked as completed.',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to complete booking.',
                life: 3000,
            });
        },
    });
};

const markNoShow = () => {
    router.post(ProviderBookingController.noShow({ uuid: props.booking.uuid }).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'No-Show Recorded',
                detail: 'The booking has been marked as no-show.',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to mark booking as no-show.',
                life: 3000,
            });
        },
    });
};

const submitCancel = () => {
    cancelForm.post(ProviderBookingController.cancel({ uuid: props.booking.uuid }).url, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Booking Cancelled',
                detail: 'The booking has been cancelled.',
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
    <ConsoleLayout :title="`Booking - ${booking.service.name}`">
        <div class="max-w-4xl mx-auto">
            <!-- Back Link -->
            <AppLink :href="ProviderBookingController.index().url" class="inline-flex items-center gap-2 text-gray-500 hover:text-[#0D1F1B] mb-6 no-underline">
                <i class="pi pi-arrow-left"></i>
                <span>Back to Bookings</span>
            </AppLink>

            <!-- Today Banner -->
            <div
                v-if="booking.is_today && !booking.is_past && booking.status === 'confirmed'"
                class="flex items-center gap-3 px-5 py-4 bg-[#106B4F]/10 border border-[#106B4F]/20 rounded-xl mb-6"
            >
                <i class="pi pi-star-fill text-[#106B4F] text-xl"></i>
                <div>
                    <p class="font-medium text-[#106B4F] m-0">This appointment is today!</p>
                    <p class="text-sm text-[#106B4F]/80 m-0">{{ booking.formatted_time }}</p>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Client Info Card -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-[#0D1F1B] m-0">
                                {{ booking.client.is_guest ? 'Guest Information' : 'Client Information' }}
                            </h2>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-4">
                                <Avatar
                                    v-if="booking.client.avatar"
                                    :image="booking.client.avatar"
                                    shape="circle"
                                    class="!w-14 !h-14"
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(booking.client.name)"
                                    shape="circle"
                                    class="!w-14 !h-14 !text-lg"
                                    :class="booking.client.is_guest ? '!bg-gray-400' : '!bg-[#106B4F]'"
                                />
                                <div>
                                    <h3 class="font-medium text-[#0D1F1B] m-0 flex items-center gap-2">
                                        {{ booking.client.name || 'Unknown' }}
                                        <span v-if="booking.client.is_guest" class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">
                                            Guest
                                        </span>
                                    </h3>
                                    <p class="text-sm text-gray-500 m-0">{{ booking.client.email }}</p>
                                    <p v-if="booking.client.phone" class="text-sm text-gray-500 m-0">{{ booking.client.phone }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Appointment Details Card -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Appointment Details</h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Service</label>
                                    <p class="font-medium text-[#0D1F1B] m-0">{{ booking.service.name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Duration</label>
                                    <p class="font-medium text-[#0D1F1B] m-0">{{ booking.service.duration_minutes }} minutes</p>
                                </div>
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
                            </div>

                            <div v-if="booking.service.description" class="mt-6 pt-6 border-t border-gray-100">
                                <label class="text-sm text-gray-500 block mb-2">Service Description</label>
                                <p class="text-gray-700 m-0">{{ booking.service.description }}</p>
                            </div>

                            <div v-if="booking.client_notes" class="mt-6 pt-6 border-t border-gray-100">
                                <label class="text-sm text-gray-500 block mb-2">Client Notes</label>
                                <p class="text-gray-700 m-0">{{ booking.client_notes }}</p>
                            </div>

                            <div v-if="booking.provider_notes" class="mt-6 pt-6 border-t border-gray-100">
                                <label class="text-sm text-gray-500 block mb-2">Your Notes</label>
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
                            <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Payment Information</h2>
                        </div>
                        <div class="p-5">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Service Price</span>
                                    <span class="font-medium">${{ booking.service_price.toFixed(2) }}</span>
                                </div>

                                <template v-if="booking.deposit_amount > 0">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Deposit</span>
                                        <span :class="booking.deposit_paid ? 'text-[#106B4F]' : 'text-yellow-600'" class="font-medium">
                                            ${{ booking.deposit_amount.toFixed(2) }}
                                            <span v-if="booking.deposit_paid" class="text-xs ml-1">(Paid)</span>
                                            <span v-else class="text-xs ml-1">(Pending)</span>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Balance Due</span>
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
                                    <span>
                                        {{ booking.payment.payment_type === 'deposit' ? 'Deposit' : 'Payment' }}
                                        received on {{ booking.payment.paid_at }}
                                    </span>
                                </div>
                                <p v-if="booking.payment.card_display" class="text-xs text-gray-500 mt-1 m-0">
                                    {{ booking.payment.card_display }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Status</h2>
                        </div>
                        <div class="p-5">
                            <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" class="!text-sm !px-3 !py-1.5" />

                            <!-- Timeline -->
                            <div class="mt-6 space-y-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                        <i class="pi pi-plus text-gray-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-[#0D1F1B] m-0 text-sm">Booked</p>
                                        <p class="text-xs text-gray-500 m-0">{{ booking.created_at }}</p>
                                    </div>
                                </div>

                                <div v-if="booking.confirmed_at" class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                                        <i class="pi pi-check text-[#106B4F] text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-[#0D1F1B] m-0 text-sm">Confirmed</p>
                                        <p class="text-xs text-gray-500 m-0">{{ booking.confirmed_at }}</p>
                                    </div>
                                </div>

                                <div v-if="booking.completed_at" class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                        <i class="pi pi-check-circle text-blue-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-[#0D1F1B] m-0 text-sm">Completed</p>
                                        <p class="text-xs text-gray-500 m-0">{{ booking.completed_at }}</p>
                                    </div>
                                </div>

                                <div v-if="booking.cancelled_at" class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                        <i class="pi pi-times text-red-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-[#0D1F1B] m-0 text-sm">Cancelled</p>
                                        <p class="text-xs text-gray-500 m-0">{{ booking.cancelled_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Actions</h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <!-- Confirm button for pending -->
                            <Button
                                v-if="booking.can_confirm"
                                label="Confirm Booking"
                                icon="pi pi-check"
                                class="w-full !bg-[#106B4F] !border-[#106B4F]"
                                @click="confirmBooking"
                            />

                            <!-- Complete button for confirmed -->
                            <Button
                                v-if="booking.can_complete"
                                label="Mark as Completed"
                                icon="pi pi-check-circle"
                                class="w-full !bg-[#106B4F] !border-[#106B4F]"
                                @click="completeBooking"
                            />

                            <!-- No-Show button for confirmed -->
                            <Button
                                v-if="booking.status === 'confirmed' && booking.is_past"
                                label="Mark as No-Show"
                                icon="pi pi-user-minus"
                                severity="secondary"
                                outlined
                                class="w-full"
                                @click="markNoShow"
                            />

                            <!-- Cancel/Decline button -->
                            <Button
                                v-if="booking.can_cancel"
                                :label="booking.status === 'pending' ? 'Decline Booking' : 'Cancel Booking'"
                                icon="pi pi-times"
                                severity="danger"
                                outlined
                                class="w-full"
                                @click="showCancelDialog = true"
                            />

                            <!-- No actions available message -->
                            <p
                                v-if="!booking.can_confirm && !booking.can_complete && !booking.can_cancel && booking.status !== 'confirmed'"
                                class="text-sm text-gray-500 text-center m-0"
                            >
                                No actions available
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cancel Dialog -->
            <Dialog
                v-model:visible="showCancelDialog"
                :header="booking.status === 'pending' ? 'Decline Booking' : 'Cancel Booking'"
                modal
                :style="{ width: '400px' }"
            >
                <div class="space-y-4">
                    <p class="text-gray-600 m-0">
                        {{ booking.status === 'pending'
                            ? 'Are you sure you want to decline this booking request?'
                            : 'Are you sure you want to cancel this booking? The client will be notified.'
                        }}
                    </p>
                    <div v-if="booking.deposit_paid" class="flex items-center gap-2 px-3 py-2 bg-yellow-50 rounded-lg text-yellow-700 text-sm">
                        <i class="pi pi-info-circle"></i>
                        <span>The deposit will be automatically refunded.</span>
                    </div>
                    <div>
                        <label for="cancel_reason" class="block text-sm font-medium text-gray-700 mb-1">
                            Reason *
                        </label>
                        <Textarea
                            id="cancel_reason"
                            v-model="cancelForm.reason"
                            rows="3"
                            class="w-full"
                            :class="{ 'p-invalid': cancelForm.errors.reason }"
                            placeholder="Please provide a reason..."
                        />
                        <small v-if="cancelForm.errors.reason" class="text-red-500">{{ cancelForm.errors.reason }}</small>
                    </div>
                </div>
                <template #footer>
                    <Button label="Go Back" severity="secondary" @click="showCancelDialog = false" />
                    <Button
                        :label="booking.status === 'pending' ? 'Decline' : 'Cancel Booking'"
                        severity="danger"
                        :loading="cancelForm.processing"
                        :disabled="!cancelForm.reason"
                        @click="submitCancel"
                    />
                </template>
            </Dialog>
        </div>
    </ConsoleLayout>
</template>

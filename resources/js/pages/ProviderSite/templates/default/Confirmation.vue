<script setup lang="ts">
import { computed } from 'vue';
import DefaultLayout from './components/DefaultLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { Booking } from '@/types/models/booking';
import payment from '@/routes/payment';

// Event booking interface
interface EventBookingData {
    id: number;
    uuid: string;
    status: string;
    status_label: string;
    spots_booked: number;
    total_amount: number;
    total_amount_display: string;
    deposit_amount?: number | null;
    deposit_amount_display?: string | null;
    deposit_paid: boolean;
    requires_deposit: boolean;
    client_notes?: string;
    booker: { name: string; email?: string; phone?: string };
    event: {
        id: number; uuid: string; name: string; slug: string;
        price: number; price_display: string; duration_display: string;
        location_type: 'virtual' | 'in_person'; location?: string;
    };
    occurrence: { formatted_date: string; formatted_time: string };
    provider: { id: number; business_name: string; slug: string; address?: string; avatar?: string };
}

interface Props {
    bookingType?: 'service' | 'event';
    booking: Booking | EventBookingData;
}

const props = defineProps<Props>();

const isEventBooking = computed(() => props.bookingType === 'event');
const serviceBooking = computed(() => props.booking as Booking);
const eventBooking = computed(() => props.booking as EventBookingData);

const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'success';
        case 'completed': return 'info';
        case 'attended': return 'success';
        case 'cancelled': return 'danger';
        case 'no_show': return 'danger';
        default: return 'secondary';
    }
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getBookerEmail = computed(() => {
    if (isEventBooking.value) return eventBooking.value.booker?.email;
    return serviceBooking.value.client?.email;
});

const needsDeposit = computed(() => props.booking.requires_deposit && !props.booking.deposit_paid);
</script>

<template>
    <DefaultLayout title="Booking Confirmation">
        <div class="confirmation-page">
            <div class="max-w-2xl mx-auto px-4 py-8">
                <!-- Success Header -->
                <div class="text-center mb-6">
                    <div class="success-icon">
                        <i class="pi pi-check text-3xl text-[var(--provider-primary)]"></i>
                    </div>
                    <h1 class="text-2xl font-semibold text-[var(--provider-text)] m-0">
                        {{ isEventBooking ? 'Registration Confirmed!' : 'Booking Submitted!' }}
                    </h1>
                    <p class="text-gray-500 mt-2">
                        {{ needsDeposit
                            ? 'Complete your deposit payment to secure your booking.'
                            : 'We\'ve sent a confirmation email to ' + getBookerEmail
                        }}
                    </p>
                </div>

                <!-- Booking Details Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-base font-semibold text-[var(--provider-text)] m-0">Booking Details</h2>
                        <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                    </div>
                    <div class="p-5 space-y-4">
                        <!-- Provider Info -->
                        <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                            <Avatar
                                v-if="booking.provider?.avatar"
                                :image="booking.provider.avatar"
                                shape="circle"
                                class="!w-12 !h-12"
                            />
                            <Avatar
                                v-else
                                :label="getInitials(booking.provider?.business_name || '')"
                                shape="circle"
                                class="!w-12 !h-12 avatar-primary"
                            />
                            <div>
                                <h3 class="font-medium text-[var(--provider-text)] m-0">{{ booking.provider?.business_name }}</h3>
                                <p v-if="booking.provider?.address" class="text-sm text-gray-500 m-0">
                                    <i class="pi pi-map-marker mr-1"></i>
                                    {{ booking.provider.address }}
                                </p>
                            </div>
                        </div>

                        <!-- Event Booking Details -->
                        <template v-if="isEventBooking">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="text-sm text-gray-500 block mb-1">Event</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ eventBooking.event.name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Date</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ eventBooking.occurrence.formatted_date }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Time</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ eventBooking.occurrence.formatted_time }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Duration</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ eventBooking.event.duration_display }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Spots Booked</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ eventBooking.spots_booked }}</p>
                                </div>
                                <div v-if="eventBooking.event.location_type === 'in_person' && eventBooking.event.location">
                                    <label class="text-sm text-gray-500 block mb-1">Location</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ eventBooking.event.location }}</p>
                                </div>
                                <div v-else-if="eventBooking.event.location_type === 'virtual'">
                                    <label class="text-sm text-gray-500 block mb-1">Location</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">Virtual Event</p>
                                </div>
                            </div>

                            <!-- Booker Info -->
                            <div class="pt-4 border-t border-gray-100">
                                <label class="text-sm text-gray-500 block mb-1">Registered by</label>
                                <p class="font-medium text-[var(--provider-text)] m-0">{{ eventBooking.booker.name }}</p>
                                <p class="text-sm text-gray-500 m-0">{{ eventBooking.booker.email }}</p>
                            </div>
                        </template>

                        <!-- Service Booking Details -->
                        <template v-else>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Service</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ serviceBooking.service.name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Duration</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ serviceBooking.service.duration_minutes }} minutes</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Date</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ serviceBooking.formatted_date }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Time</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ serviceBooking.formatted_time }}</p>
                                </div>
                            </div>

                            <!-- Your Info -->
                            <div class="pt-4 border-t border-gray-100">
                                <label class="text-sm text-gray-500 block mb-1">Booked by</label>
                                <p class="font-medium text-[var(--provider-text)] m-0">{{ serviceBooking.client?.name }}</p>
                                <p class="text-sm text-gray-500 m-0">{{ serviceBooking.client?.email }}</p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Payment Card (if deposit required) -->
                <div v-if="booking.requires_deposit" class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-[var(--provider-text)] m-0">Payment</h2>
                    </div>
                    <div class="p-5">
                        <!-- Event Booking Payment -->
                        <template v-if="isEventBooking">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Amount</span>
                                    <span class="font-medium">{{ eventBooking.total_amount_display }}</span>
                                </div>
                                <div class="flex justify-between text-[var(--provider-primary)]">
                                    <span>Deposit Required</span>
                                    <span class="font-medium">{{ eventBooking.deposit_amount_display }}</span>
                                </div>
                            </div>
                        </template>

                        <!-- Service Booking Payment -->
                        <template v-else>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Service Total</span>
                                    <span class="font-medium">${{ serviceBooking.service_price.toFixed(2) }}</span>
                                </div>
                                <div v-if="serviceBooking.fee_payer === 'client' && serviceBooking.convenience_fee > 0" class="flex justify-between">
                                    <span class="text-gray-600">Service Fee</span>
                                    <span class="font-medium">${{ serviceBooking.convenience_fee.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-[var(--provider-primary)]">
                                    <span>Deposit Required</span>
                                    <span class="font-medium">${{ serviceBooking.deposit_amount.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>Balance Due at Appointment</span>
                                    <span>${{ serviceBooking.balance_amount.toFixed(2) }}</span>
                                </div>
                            </div>
                        </template>

                        <hr class="my-4 border-gray-200" />

                        <div v-if="booking.deposit_paid" class="flex items-center gap-2 p-3 bg-green-50 rounded-lg text-green-700">
                            <i class="pi pi-check-circle"></i>
                            <span class="text-sm font-medium">Deposit Paid</span>
                        </div>
                        <div v-else>
                            <AppLink :href="payment.checkout({ bookingUuid: booking.uuid }).url">
                                <Button
                                    label="Pay Deposit Now"
                                    icon="pi pi-credit-card"
                                    class="w-full btn-primary"
                                />
                            </AppLink>
                            <p class="text-xs text-center text-gray-400 mt-2 m-0">
                                Pay your deposit to secure your {{ isEventBooking ? 'registration' : 'appointment' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- What's Next Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-[var(--provider-text)] m-0">What's Next?</h2>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-3 m-0 p-0 list-none">
                            <li v-if="needsDeposit" class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center shrink-0 text-sm">1</span>
                                <div>
                                    <p class="font-medium text-[var(--provider-text)] m-0">Complete your deposit payment</p>
                                    <p class="text-sm text-gray-500 m-0">Pay the deposit to secure your {{ isEventBooking ? 'registration' : 'booking' }}</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="step-indicator">
                                    {{ needsDeposit ? '2' : '1' }}
                                </span>
                                <div>
                                    <p class="font-medium text-[var(--provider-text)] m-0">Check your email</p>
                                    <p class="text-sm text-gray-500 m-0">We've sent {{ isEventBooking ? 'registration' : 'booking' }} details to {{ getBookerEmail }}</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="step-indicator">
                                    {{ needsDeposit ? '3' : '2' }}
                                </span>
                                <div>
                                    <p class="font-medium text-[var(--provider-text)] m-0">Save the date</p>
                                    <p class="text-sm text-gray-500 m-0">
                                        {{ isEventBooking ? eventBooking.occurrence.formatted_date : serviceBooking.formatted_date }}
                                        at
                                        {{ isEventBooking ? eventBooking.occurrence.formatted_time : serviceBooking.formatted_time }}
                                    </p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="step-indicator">
                                    {{ needsDeposit ? '4' : '3' }}
                                </span>
                                <div>
                                    <p class="font-medium text-[var(--provider-text)] m-0">
                                        {{ isEventBooking && eventBooking.event.location_type === 'virtual' ? 'Join the event' : 'Arrive on time' }}
                                    </p>
                                    <p class="text-sm text-gray-500 m-0">
                                        <template v-if="isEventBooking">
                                            {{ eventBooking.event.location_type === 'virtual' ? 'Virtual event - check email for details' : eventBooking.event.location || eventBooking.provider?.address }}
                                        </template>
                                        <template v-else>
                                            {{ serviceBooking.provider?.address }}
                                        </template>
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-center gap-4 mt-6">
                    <AppLink href="/">
                        <Button label="Back to Home" severity="secondary" />
                    </AppLink>
                    <AppLink :href="isEventBooking ? '/events' : '/services'">
                        <Button :label="isEventBooking ? 'Browse Events' : 'Browse Services'" outlined class="btn-outlined" />
                    </AppLink>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

<style scoped>
.confirmation-page {
    min-height: 100%;
    background-color: #f9fafb;
}

.success-icon {
    width: 4rem;
    height: 4rem;
    border-radius: 9999px;
    background: var(--provider-primary-10);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.step-indicator {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 9999px;
    background: var(--provider-primary-10);
    color: var(--provider-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.875rem;
}

:deep(.avatar-primary) {
    background-color: var(--provider-primary) !important;
}

:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

:deep(.btn-outlined) {
    border-color: var(--provider-primary) !important;
    color: var(--provider-primary) !important;
}

:deep(.btn-outlined:hover) {
    background-color: var(--provider-primary-10) !important;
}
</style>

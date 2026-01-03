<script setup lang="ts">
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { Booking } from '@/types/models/booking';
import payment from '@/routes/payment';

// Service booking interface (existing)
interface ServiceBookingData extends Booking {}

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
    confirmed_at?: string;
    booker: {
        name: string;
        email?: string;
        phone?: string;
    };
    event: {
        id: number;
        uuid: string;
        name: string;
        slug: string;
        description?: string;
        price: number;
        price_display: string;
        duration_display: string;
        location_type: 'virtual' | 'in_person';
        location?: string;
    };
    occurrence: {
        id: number;
        uuid: string;
        formatted_date: string;
        formatted_time: string;
        start_datetime: string;
        end_datetime: string;
    };
    provider: {
        id: number;
        business_name: string;
        slug: string;
        address?: string;
        avatar?: string;
    };
}

interface Props {
    bookingType: 'service' | 'event';
    booking: ServiceBookingData | EventBookingData;
}

const props = defineProps<Props>();

// Type guards
const isServiceBooking = (booking: ServiceBookingData | EventBookingData): booking is ServiceBookingData => {
    return props.bookingType === 'service';
};

const isEventBooking = (booking: ServiceBookingData | EventBookingData): booking is EventBookingData => {
    return props.bookingType === 'event';
};

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

// Get booker email for display
const getBookerEmail = () => {
    if (isServiceBooking(props.booking)) {
        return props.booking.client?.email;
    }
    return props.booking.booker?.email;
};

// Get booker name for display
const getBookerName = () => {
    if (isServiceBooking(props.booking)) {
        return props.booking.client?.name;
    }
    return props.booking.booker?.name;
};

// Get provider info
const getProvider = () => {
    return props.booking.provider;
};

// Check if deposit is required and not paid
const needsDeposit = () => {
    if (isServiceBooking(props.booking)) {
        return props.booking.requires_deposit && !props.booking.deposit_paid;
    }
    return props.booking.requires_deposit && !props.booking.deposit_paid;
};
</script>

<template>
    <ProviderSiteLayout :title="bookingType === 'event' ? 'Registration Confirmation' : 'Booking Confirmation'">
        <div class="confirmation-page">
            <div class="max-w-2xl mx-auto px-4 py-8">
                <!-- Success Header -->
                <div class="text-center mb-6">
                    <div class="success-icon">
                        <i class="pi pi-check text-3xl text-[var(--provider-primary)]"></i>
                    </div>
                    <h1 class="text-2xl font-semibold text-[var(--provider-text)] m-0">
                        {{ bookingType === 'event' ? 'Registration Submitted!' : 'Booking Submitted!' }}
                    </h1>
                    <p class="text-gray-500 mt-2">
                        {{ needsDeposit()
                            ? 'Complete your deposit payment to secure your spot.'
                            : 'We\'ve sent a confirmation email to ' + getBookerEmail()
                        }}
                    </p>
                </div>

                <!-- Event Booking Details -->
                <template v-if="bookingType === 'event' && isEventBooking(booking)">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-base font-semibold text-[var(--provider-text)] m-0">Registration Details</h2>
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

                            <!-- Event & Occurrence Info -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Event</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.event.name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Duration</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.event.duration_display }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Date</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.occurrence.formatted_date }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Time</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.occurrence.formatted_time }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Spots Booked</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.spots_booked }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Location</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">
                                        <i :class="booking.event.location_type === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker'" class="mr-1"></i>
                                        {{ booking.event.location_type === 'virtual' ? 'Virtual Event' : (booking.event.location || 'In Person') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Registrant Info -->
                            <div class="pt-4 border-t border-gray-100">
                                <label class="text-sm text-gray-500 block mb-1">Registered by</label>
                                <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.booker.name }}</p>
                                <p class="text-sm text-gray-500 m-0">{{ booking.booker.email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Card for Events -->
                    <div v-if="booking.requires_deposit" class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-5 py-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-[var(--provider-text)] m-0">Payment</h2>
                        </div>
                        <div class="p-5">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ booking.event.price_display }} × {{ booking.spots_booked }} {{ booking.spots_booked === 1 ? 'spot' : 'spots' }}</span>
                                    <span class="font-medium">{{ booking.total_amount_display }}</span>
                                </div>
                                <div v-if="booking.deposit_amount" class="flex justify-between text-[var(--provider-primary)]">
                                    <span>Deposit Required</span>
                                    <span class="font-medium">{{ booking.deposit_amount_display }}</span>
                                </div>
                            </div>

                            <hr class="my-4 border-gray-200" />

                            <div v-if="booking.deposit_paid" class="flex items-center gap-2 p-3 bg-green-50 rounded-lg text-green-700">
                                <i class="pi pi-check-circle"></i>
                                <span class="text-sm font-medium">Deposit Paid</span>
                            </div>
                            <div v-else-if="booking.requires_deposit">
                                <Button
                                    label="Pay Deposit Now"
                                    icon="pi pi-credit-card"
                                    class="w-full btn-primary"
                                />
                                <p class="text-xs text-center text-gray-400 mt-2 m-0">
                                    Pay your deposit to secure your spot
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Service Booking Details (existing) -->
                <template v-else-if="bookingType === 'service' && isServiceBooking(booking)">
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

                            <!-- Service & Time -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Service</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.service.name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Duration</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.service.duration_minutes }} minutes</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Date</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.formatted_date }}</p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500 block mb-1">Time</label>
                                    <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.formatted_time }}</p>
                                </div>
                            </div>

                            <!-- Your Info -->
                            <div class="pt-4 border-t border-gray-100">
                                <label class="text-sm text-gray-500 block mb-1">Booked by</label>
                                <p class="font-medium text-[var(--provider-text)] m-0">{{ booking.client?.name }}</p>
                                <p class="text-sm text-gray-500 m-0">{{ booking.client?.email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Card (if deposit required) -->
                    <div v-if="booking.requires_deposit" class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-5 py-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-[var(--provider-text)] m-0">Payment</h2>
                        </div>
                        <div class="p-5">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Service Total</span>
                                    <span class="font-medium">${{ booking.service_price.toFixed(2) }}</span>
                                </div>
                                <div v-if="booking.fee_payer === 'client' && booking.convenience_fee > 0" class="flex justify-between">
                                    <span class="text-gray-600">Service Fee</span>
                                    <span class="font-medium">${{ booking.convenience_fee.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-[var(--provider-primary)]">
                                    <span>Deposit Required</span>
                                    <span class="font-medium">${{ booking.deposit_amount.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>Balance Due at Appointment</span>
                                    <span>${{ booking.balance_amount.toFixed(2) }}</span>
                                </div>
                            </div>

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
                                    Pay your deposit to secure your appointment
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- What's Next Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-[var(--provider-text)] m-0">What's Next?</h2>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-3 m-0 p-0 list-none">
                            <li v-if="needsDeposit()" class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center shrink-0 text-sm">1</span>
                                <div>
                                    <p class="font-medium text-[var(--provider-text)] m-0">Complete your deposit payment</p>
                                    <p class="text-sm text-gray-500 m-0">Pay the deposit to secure your {{ bookingType === 'event' ? 'spot' : 'booking' }}</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="step-indicator">
                                    {{ needsDeposit() ? '2' : '1' }}
                                </span>
                                <div>
                                    <p class="font-medium text-[var(--provider-text)] m-0">Check your email</p>
                                    <p class="text-sm text-gray-500 m-0">We've sent {{ bookingType === 'event' ? 'registration' : 'booking' }} details to {{ getBookerEmail() }}</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="step-indicator">
                                    {{ needsDeposit() ? '3' : '2' }}
                                </span>
                                <div>
                                    <p class="font-medium text-[var(--provider-text)] m-0">Save the date</p>
                                    <p class="text-sm text-gray-500 m-0">
                                        <template v-if="bookingType === 'event' && isEventBooking(booking)">
                                            {{ booking.occurrence.formatted_date }} at {{ booking.occurrence.formatted_time }}
                                        </template>
                                        <template v-else-if="isServiceBooking(booking)">
                                            {{ booking.formatted_date }} at {{ booking.formatted_time }}
                                        </template>
                                    </p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="step-indicator">
                                    {{ needsDeposit() ? '4' : '3' }}
                                </span>
                                <div>
                                    <p class="font-medium text-[var(--provider-text)] m-0">
                                        {{ bookingType === 'event' && isEventBooking(booking) && booking.event.location_type === 'virtual' ? 'Join online' : 'Arrive on time' }}
                                    </p>
                                    <p class="text-sm text-gray-500 m-0">
                                        <template v-if="bookingType === 'event' && isEventBooking(booking)">
                                            {{ booking.event.location_type === 'virtual' ? 'Check your email for the meeting link' : (booking.event.location || booking.provider?.address) }}
                                        </template>
                                        <template v-else>
                                            {{ getProvider()?.address }}
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
                    <AppLink :href="bookingType === 'event' ? '/events' : '/services'">
                        <Button :label="bookingType === 'event' ? 'Browse Events' : 'Browse Services'" outlined class="btn-outlined" />
                    </AppLink>
                </div>
            </div>
        </div>
    </ProviderSiteLayout>
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

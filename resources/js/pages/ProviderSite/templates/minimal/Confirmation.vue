<script setup lang="ts">
import { computed } from 'vue';
import MinimalLayout from './components/MinimalLayout.vue';
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

const getBookerEmail = computed(() => {
    if (isEventBooking.value) return eventBooking.value.booker?.email;
    return serviceBooking.value.client?.email;
});

const needsDeposit = computed(() => props.booking.requires_deposit && !props.booking.deposit_paid);
</script>

<template>
    <MinimalLayout title="Booking Confirmed">
        <div class="minimal-confirmation">
            <div class="page-container">
                <!-- Success Message -->
                <div class="success-header">
                    <div class="success-icon">
                        <i class="pi pi-check"></i>
                    </div>
                    <h1>{{ isEventBooking ? 'Registration Confirmed' : 'Booking Submitted' }}</h1>
                    <p>
                        {{ needsDeposit
                            ? 'Complete your deposit payment to secure your booking.'
                            : 'Confirmation details have been sent to your email.'
                        }}
                    </p>
                </div>

                <!-- Booking Card -->
                <div class="booking-card">
                    <div class="card-header">
                        <span class="card-title">{{ isEventBooking ? 'Registration Details' : 'Booking Details' }}</span>
                        <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                    </div>

                    <div class="card-body">
                        <!-- Event Booking Details -->
                        <template v-if="isEventBooking">
                            <div class="detail-row">
                                <span class="detail-label">Event</span>
                                <span class="detail-value">{{ eventBooking.event.name }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date</span>
                                <span class="detail-value">{{ eventBooking.occurrence.formatted_date }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Time</span>
                                <span class="detail-value">{{ eventBooking.occurrence.formatted_time }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Duration</span>
                                <span class="detail-value">{{ eventBooking.event.duration_display }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Spots</span>
                                <span class="detail-value">{{ eventBooking.spots_booked }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Provider</span>
                                <span class="detail-value">{{ eventBooking.provider?.business_name }}</span>
                            </div>
                            <div v-if="eventBooking.event.location_type === 'in_person' && eventBooking.event.location" class="detail-row">
                                <span class="detail-label">Location</span>
                                <span class="detail-value">{{ eventBooking.event.location }}</span>
                            </div>
                            <div v-else-if="eventBooking.event.location_type === 'virtual'" class="detail-row">
                                <span class="detail-label">Location</span>
                                <span class="detail-value">Virtual Event</span>
                            </div>
                        </template>

                        <!-- Service Booking Details -->
                        <template v-else>
                            <div class="detail-row">
                                <span class="detail-label">Service</span>
                                <span class="detail-value">{{ serviceBooking.service.name }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Duration</span>
                                <span class="detail-value">{{ serviceBooking.service.duration_minutes }} minutes</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date</span>
                                <span class="detail-value">{{ serviceBooking.formatted_date }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Time</span>
                                <span class="detail-value">{{ serviceBooking.formatted_time }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Provider</span>
                                <span class="detail-value">{{ serviceBooking.provider?.business_name }}</span>
                            </div>
                            <div v-if="serviceBooking.provider?.address" class="detail-row">
                                <span class="detail-label">Location</span>
                                <span class="detail-value">{{ serviceBooking.provider.address }}</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Payment Card (if deposit required) -->
                <div v-if="booking.requires_deposit" class="payment-card">
                    <div class="card-header">
                        <span class="card-title">Payment</span>
                    </div>

                    <div class="card-body">
                        <!-- Event Booking Payment -->
                        <template v-if="isEventBooking">
                            <div class="detail-row">
                                <span class="detail-label">Total Amount</span>
                                <span class="detail-value">{{ eventBooking.total_amount_display }}</span>
                            </div>
                            <div class="detail-row highlight">
                                <span class="detail-label">Deposit Required</span>
                                <span class="detail-value">{{ eventBooking.deposit_amount_display }}</span>
                            </div>
                        </template>

                        <!-- Service Booking Payment -->
                        <template v-else>
                            <div class="detail-row">
                                <span class="detail-label">Service Total</span>
                                <span class="detail-value">${{ serviceBooking.service_price.toFixed(2) }}</span>
                            </div>
                            <div v-if="serviceBooking.fee_payer === 'client' && serviceBooking.convenience_fee > 0" class="detail-row">
                                <span class="detail-label">Service Fee</span>
                                <span class="detail-value">${{ serviceBooking.convenience_fee.toFixed(2) }}</span>
                            </div>
                            <div class="detail-row highlight">
                                <span class="detail-label">Deposit Required</span>
                                <span class="detail-value">${{ serviceBooking.deposit_amount.toFixed(2) }}</span>
                            </div>
                            <div class="detail-row muted">
                                <span class="detail-label">Balance Due at Appointment</span>
                                <span class="detail-value">${{ serviceBooking.balance_amount.toFixed(2) }}</span>
                            </div>
                        </template>

                        <div v-if="booking.deposit_paid" class="deposit-paid">
                            <i class="pi pi-check-circle"></i>
                            Deposit Paid
                        </div>
                        <div v-else class="deposit-action">
                            <AppLink :href="payment.checkout({ bookingUuid: booking.uuid }).url">
                                <Button label="Pay Deposit Now" icon="pi pi-credit-card" class="pay-btn" />
                            </AppLink>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="next-steps">
                    <h2>What's Next?</h2>
                    <ol class="steps-list">
                        <li v-if="needsDeposit">
                            <strong>Pay your deposit</strong> to secure your {{ isEventBooking ? 'registration' : 'booking' }}
                        </li>
                        <li>
                            <strong>Check your email</strong> for confirmation details
                        </li>
                        <li>
                            <strong>Save the date:</strong>
                            {{ isEventBooking ? eventBooking.occurrence.formatted_date : serviceBooking.formatted_date }}
                            at
                            {{ isEventBooking ? eventBooking.occurrence.formatted_time : serviceBooking.formatted_time }}
                        </li>
                        <li v-if="isEventBooking">
                            <template v-if="eventBooking.event.location_type === 'virtual'">
                                <strong>Join the event</strong> - check email for details
                            </template>
                            <template v-else-if="eventBooking.event.location">
                                <strong>Arrive on time</strong> at {{ eventBooking.event.location }}
                            </template>
                        </li>
                        <li v-else-if="serviceBooking.provider?.address">
                            <strong>Arrive on time</strong> at {{ serviceBooking.provider.address }}
                        </li>
                    </ol>
                </div>

                <!-- Actions -->
                <div class="actions">
                    <AppLink href="/">
                        <Button label="Back to Home" severity="secondary" />
                    </AppLink>
                    <AppLink :href="isEventBooking ? '/events' : '/services'">
                        <Button :label="isEventBooking ? 'Browse Events' : 'Browse Services'" outlined class="services-btn" />
                    </AppLink>
                </div>
            </div>
        </div>
    </MinimalLayout>
</template>

<style scoped>
.minimal-confirmation {
    min-height: 100%;
    background: #fafafa;
}

.page-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
}

.success-header {
    text-align: center;
    margin-bottom: 2rem;
}

.success-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    background: var(--provider-primary-10);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.success-icon i {
    font-size: 1.5rem;
    color: var(--provider-primary);
}

.success-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--provider-text);
}

.success-header p {
    margin: 0;
    color: #6b7280;
}

.booking-card,
.payment-card {
    background: white;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.card-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--provider-text);
}

.card-body {
    padding: 1.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.detail-row:first-child {
    padding-top: 0;
}

.detail-row:last-child {
    padding-bottom: 0;
}

.detail-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.detail-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text);
}

.detail-row.highlight {
    color: var(--provider-primary);
}

.detail-row.highlight .detail-label,
.detail-row.highlight .detail-value {
    color: var(--provider-primary);
}

.detail-row.muted .detail-label,
.detail-row.muted .detail-value {
    color: #9ca3af;
}

.deposit-paid {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    margin-top: 1rem;
    background: #dcfce7;
    border-radius: 0.375rem;
    color: #166534;
    font-size: 0.875rem;
    font-weight: 500;
}

.deposit-action {
    margin-top: 1rem;
}

:deep(.pay-btn) {
    width: 100%;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.pay-btn:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.next-steps {
    margin-bottom: 2rem;
}

.next-steps h2 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text);
}

.steps-list {
    margin: 0;
    padding-left: 1.25rem;
}

.steps-list li {
    padding: 0.5rem 0;
    color: #4b5563;
    font-size: 0.9375rem;
    line-height: 1.5;
}

.steps-list li strong {
    color: var(--provider-text);
}

.actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

:deep(.services-btn) {
    border-color: var(--provider-primary) !important;
    color: var(--provider-primary) !important;
}

:deep(.services-btn:hover) {
    background-color: var(--provider-primary-10) !important;
}

@media (max-width: 480px) {
    .actions {
        flex-direction: column;
    }

    .actions :deep(button) {
        width: 100%;
    }
}
</style>

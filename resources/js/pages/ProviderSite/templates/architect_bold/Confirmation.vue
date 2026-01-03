<script setup lang="ts">
import { computed } from 'vue';
import ArchitectBoldLayout from './components/ArchitectBoldLayout.vue';
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
    <ArchitectBoldLayout title="Booking Confirmed">
        <div class="confirmation-page">
            <div class="page-container">
                <!-- Success Message -->
                <div class="success-header">
                    <div class="success-icon">
                        <i class="pi pi-check"></i>
                    </div>
                    <h1>{{ isEventBooking ? 'REGISTRATION CONFIRMED' : 'BOOKING SUBMITTED' }}</h1>
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
                        <span class="card-title">{{ isEventBooking ? 'REGISTRATION DETAILS' : 'BOOKING DETAILS' }}</span>
                        <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                    </div>

                    <div class="card-body">
                        <!-- Event Booking Details -->
                        <template v-if="isEventBooking">
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-calendar-plus"></i>
                                    EVENT
                                </span>
                                <span class="detail-value">{{ eventBooking.event.name }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-calendar"></i>
                                    DATE
                                </span>
                                <span class="detail-value">{{ eventBooking.occurrence.formatted_date }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-clock"></i>
                                    TIME
                                </span>
                                <span class="detail-value">{{ eventBooking.occurrence.formatted_time }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-stopwatch"></i>
                                    DURATION
                                </span>
                                <span class="detail-value">{{ eventBooking.event.duration_display }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-users"></i>
                                    SPOTS
                                </span>
                                <span class="detail-value">{{ eventBooking.spots_booked }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-building"></i>
                                    PROVIDER
                                </span>
                                <span class="detail-value">{{ eventBooking.provider?.business_name }}</span>
                            </div>
                            <div v-if="eventBooking.event.location_type === 'in_person' && eventBooking.event.location" class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-map-marker"></i>
                                    LOCATION
                                </span>
                                <span class="detail-value">{{ eventBooking.event.location }}</span>
                            </div>
                            <div v-else-if="eventBooking.event.location_type === 'virtual'" class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-video"></i>
                                    LOCATION
                                </span>
                                <span class="detail-value">VIRTUAL EVENT</span>
                            </div>
                        </template>

                        <!-- Service Booking Details -->
                        <template v-else>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-bookmark"></i>
                                    SERVICE
                                </span>
                                <span class="detail-value">{{ serviceBooking.service.name }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-clock"></i>
                                    DURATION
                                </span>
                                <span class="detail-value">{{ serviceBooking.service.duration_minutes }} minutes</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-calendar"></i>
                                    DATE
                                </span>
                                <span class="detail-value">{{ serviceBooking.formatted_date }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-clock"></i>
                                    TIME
                                </span>
                                <span class="detail-value">{{ serviceBooking.formatted_time }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-building"></i>
                                    PROVIDER
                                </span>
                                <span class="detail-value">{{ serviceBooking.provider?.business_name }}</span>
                            </div>
                            <div v-if="serviceBooking.provider?.address" class="detail-row">
                                <span class="detail-label">
                                    <i class="pi pi-map-marker"></i>
                                    LOCATION
                                </span>
                                <span class="detail-value">{{ serviceBooking.provider.address }}</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Payment Card (if deposit required) -->
                <div v-if="booking.requires_deposit" class="payment-card">
                    <div class="card-header">
                        <span class="card-title">PAYMENT SUMMARY</span>
                    </div>

                    <div class="card-body">
                        <!-- Event Booking Payment -->
                        <template v-if="isEventBooking">
                            <div class="detail-row">
                                <span class="detail-label">TOTAL AMOUNT</span>
                                <span class="detail-value">{{ eventBooking.total_amount_display }}</span>
                            </div>
                            <div class="detail-row highlight">
                                <span class="detail-label">DEPOSIT REQUIRED</span>
                                <span class="detail-value">{{ eventBooking.deposit_amount_display }}</span>
                            </div>
                        </template>

                        <!-- Service Booking Payment -->
                        <template v-else>
                            <div class="detail-row">
                                <span class="detail-label">SERVICE TOTAL</span>
                                <span class="detail-value">${{ serviceBooking.service_price.toFixed(2) }}</span>
                            </div>
                            <div v-if="serviceBooking.fee_payer === 'client' && serviceBooking.convenience_fee > 0" class="detail-row">
                                <span class="detail-label">SERVICE FEE</span>
                                <span class="detail-value">${{ serviceBooking.convenience_fee.toFixed(2) }}</span>
                            </div>
                            <div class="detail-row highlight">
                                <span class="detail-label">DEPOSIT REQUIRED</span>
                                <span class="detail-value">${{ serviceBooking.deposit_amount.toFixed(2) }}</span>
                            </div>
                            <div class="detail-row muted">
                                <span class="detail-label">BALANCE DUE AT APPOINTMENT</span>
                                <span class="detail-value">${{ serviceBooking.balance_amount.toFixed(2) }}</span>
                            </div>
                        </template>

                        <div v-if="booking.deposit_paid" class="deposit-paid">
                            <i class="pi pi-check-circle"></i>
                            DEPOSIT PAID
                        </div>
                        <div v-else class="deposit-action">
                            <AppLink :href="payment.checkout({ bookingUuid: booking.uuid }).url">
                                <Button label="PAY DEPOSIT NOW" icon="pi pi-credit-card" class="pay-btn" />
                            </AppLink>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="next-steps">
                    <h2>WHAT'S NEXT?</h2>
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
                        <Button label="BACK TO HOME" severity="secondary" outlined class="action-btn" />
                    </AppLink>
                    <AppLink :href="isEventBooking ? '/events' : '/services'">
                        <Button :label="isEventBooking ? 'BROWSE EVENTS' : 'BROWSE SERVICES'" outlined class="action-btn secondary" />
                    </AppLink>
                </div>
            </div>
        </div>
    </ArchitectBoldLayout>
</template>

<style scoped>
.confirmation-page {
    min-height: 100vh;
    background: var(--provider-background, #f5f5f5);
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
    background: var(--provider-primary, #1a1a1a);
    display: flex;
    align-items: center;
    justify-content: center;
}

.success-icon i {
    font-size: 1.5rem;
    color: #fff;
}

.success-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.05em;
}

.success-header p {
    margin: 0;
    color: var(--provider-secondary, #6b7280);
}

.booking-card,
.payment-card {
    background: var(--provider-surface, #fff);
    margin-bottom: 1.5rem;
    border: 1px solid var(--provider-border, #e5e5e5);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--provider-border, #e5e5e5);
}

.card-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.1em;
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
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--provider-secondary, #6b7280);
    letter-spacing: 0.05em;
}

.detail-label i {
    color: var(--provider-primary, #1a1a1a);
    font-size: 0.875rem;
}

.detail-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--provider-text, #1a1a1a);
}

.detail-row.highlight {
    color: var(--provider-primary, #1a1a1a);
}

.detail-row.highlight .detail-label,
.detail-row.highlight .detail-value {
    color: var(--provider-primary, #1a1a1a);
}

.detail-row.muted .detail-label,
.detail-row.muted .detail-value {
    color: var(--provider-secondary, #9ca3af);
}

.deposit-paid {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    margin-top: 1rem;
    background: var(--provider-primary, #1a1a1a);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
}

.deposit-action {
    margin-top: 1rem;
}

:deep(.pay-btn) {
    width: 100%;
    background-color: var(--provider-primary, #1a1a1a) !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
    font-weight: 700;
    letter-spacing: 0.1em;
}

:deep(.pay-btn:hover) {
    background-color: var(--provider-primary-hover, #333) !important;
    border-color: var(--provider-primary-hover, #333) !important;
}

.next-steps {
    margin-bottom: 2rem;
}

.next-steps h2 {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.1em;
}

.steps-list {
    margin: 0;
    padding-left: 1.25rem;
}

.steps-list li {
    padding: 0.5rem 0;
    color: var(--provider-secondary, #6b7280);
    font-size: 0.9375rem;
    line-height: 1.5;
}

.steps-list li strong {
    color: var(--provider-text, #1a1a1a);
}

.actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

:deep(.action-btn) {
    border-radius: 0 !important;
    font-weight: 700;
    letter-spacing: 0.1em;
}

:deep(.action-btn.secondary) {
    border-color: var(--provider-primary, #1a1a1a) !important;
    color: var(--provider-primary, #1a1a1a) !important;
}

:deep(.action-btn.secondary:hover) {
    background-color: var(--provider-primary-10, rgba(26, 26, 26, 0.1)) !important;
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

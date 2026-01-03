<script setup lang="ts">
import { computed } from 'vue';
import GrandHorizonLayout from './components/GrandHorizonLayout.vue';
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
    <GrandHorizonLayout title="Confirmation">
        <div class="confirmation-page">
            <!-- Success Header -->
            <section class="confirmation-header">
                <div class="header-content">
                    <div class="success-icon">
                        <i class="pi pi-check"></i>
                    </div>
                    <h4 class="header-label">{{ isEventBooking ? 'Registration Complete' : 'Reservation Complete' }}</h4>
                    <h1>Thank You</h1>
                    <p>
                        {{ needsDeposit
                            ? 'Complete your deposit to secure your reservation.'
                            : 'Your ' + (isEventBooking ? 'registration' : 'booking') + ' has been confirmed. Details have been sent to your email.'
                        }}
                    </p>
                </div>
            </section>

            <!-- Confirmation Content -->
            <section class="confirmation-content">
                <div class="content-container">
                    <!-- Booking Details Card -->
                    <div class="detail-card">
                        <div class="card-header">
                            <h2>{{ isEventBooking ? 'Registration Details' : 'Reservation Details' }}</h2>
                            <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                        </div>

                        <div class="card-body">
                            <div class="detail-grid">
                                <!-- Event Booking Details -->
                                <template v-if="isEventBooking">
                                    <div class="detail-item" style="grid-column: span 2;">
                                        <span class="detail-label">Event</span>
                                        <span class="detail-value">{{ eventBooking.event.name }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Date</span>
                                        <span class="detail-value">{{ eventBooking.occurrence.formatted_date }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Time</span>
                                        <span class="detail-value">{{ eventBooking.occurrence.formatted_time }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Duration</span>
                                        <span class="detail-value">{{ eventBooking.event.duration_display }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Spots Reserved</span>
                                        <span class="detail-value">{{ eventBooking.spots_booked }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Host</span>
                                        <span class="detail-value">{{ eventBooking.provider?.business_name }}</span>
                                    </div>
                                    <div v-if="eventBooking.event.location_type === 'in_person' && eventBooking.event.location" class="detail-item">
                                        <span class="detail-label">Venue</span>
                                        <span class="detail-value">{{ eventBooking.event.location }}</span>
                                    </div>
                                    <div v-else-if="eventBooking.event.location_type === 'virtual'" class="detail-item">
                                        <span class="detail-label">Format</span>
                                        <span class="detail-value">Virtual Event</span>
                                    </div>
                                </template>

                                <!-- Service Booking Details -->
                                <template v-else>
                                    <div class="detail-item">
                                        <span class="detail-label">Experience</span>
                                        <span class="detail-value">{{ serviceBooking.service.name }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Duration</span>
                                        <span class="detail-value">{{ serviceBooking.service.duration_minutes }} minutes</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Date</span>
                                        <span class="detail-value">{{ serviceBooking.formatted_date }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Time</span>
                                        <span class="detail-value">{{ serviceBooking.formatted_time }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Location</span>
                                        <span class="detail-value">{{ serviceBooking.provider?.business_name }}</span>
                                    </div>
                                    <div v-if="serviceBooking.provider?.address" class="detail-item">
                                        <span class="detail-label">Address</span>
                                        <span class="detail-value">{{ serviceBooking.provider.address }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Card -->
                    <div v-if="booking.requires_deposit" class="detail-card">
                        <div class="card-header">
                            <h2>Payment Summary</h2>
                        </div>

                        <div class="card-body">
                            <div class="payment-rows">
                                <!-- Event Booking Payment -->
                                <template v-if="isEventBooking">
                                    <div class="payment-row">
                                        <span class="payment-label">Total amount</span>
                                        <span class="payment-value">{{ eventBooking.total_amount_display }}</span>
                                    </div>
                                    <div class="payment-row payment-row--highlight">
                                        <span class="payment-label">Deposit required</span>
                                        <span class="payment-value">{{ eventBooking.deposit_amount_display }}</span>
                                    </div>
                                </template>

                                <!-- Service Booking Payment -->
                                <template v-else>
                                    <div class="payment-row">
                                        <span class="payment-label">Service total</span>
                                        <span class="payment-value">${{ serviceBooking.service_price.toFixed(2) }}</span>
                                    </div>
                                    <div v-if="serviceBooking.fee_payer === 'client' && serviceBooking.convenience_fee > 0" class="payment-row">
                                        <span class="payment-label">Service fee</span>
                                        <span class="payment-value">${{ serviceBooking.convenience_fee.toFixed(2) }}</span>
                                    </div>
                                    <div class="payment-row payment-row--highlight">
                                        <span class="payment-label">Deposit required</span>
                                        <span class="payment-value">${{ serviceBooking.deposit_amount.toFixed(2) }}</span>
                                    </div>
                                    <div class="payment-row payment-row--muted">
                                        <span class="payment-label">Balance due at appointment</span>
                                        <span class="payment-value">${{ serviceBooking.balance_amount.toFixed(2) }}</span>
                                    </div>
                                </template>
                            </div>

                            <div v-if="booking.deposit_paid" class="deposit-status deposit-status--paid">
                                <i class="pi pi-check-circle"></i>
                                <span>Deposit paid successfully</span>
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
                        <h3>What's Next</h3>
                        <div class="steps-grid">
                            <div v-if="needsDeposit" class="step-item">
                                <span class="step-number">01</span>
                                <div class="step-content">
                                    <strong>Pay Your Deposit</strong>
                                    <p>Secure your {{ isEventBooking ? 'registration' : 'reservation' }} with the required deposit payment.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <span class="step-number">{{ needsDeposit ? '02' : '01' }}</span>
                                <div class="step-content">
                                    <strong>Check Your Email</strong>
                                    <p>Confirmation details have been sent to your inbox.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <span class="step-number">{{ needsDeposit ? '03' : '02' }}</span>
                                <div class="step-content">
                                    <strong>Mark Your Calendar</strong>
                                    <p>
                                        {{ isEventBooking ? eventBooking.occurrence.formatted_date : serviceBooking.formatted_date }}
                                        at
                                        {{ isEventBooking ? eventBooking.occurrence.formatted_time : serviceBooking.formatted_time }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="isEventBooking" class="step-item">
                                <span class="step-number">{{ needsDeposit ? '04' : '03' }}</span>
                                <div class="step-content">
                                    <template v-if="eventBooking.event.location_type === 'virtual'">
                                        <strong>Join the Event</strong>
                                        <p>Check your email for virtual event details.</p>
                                    </template>
                                    <template v-else-if="eventBooking.event.location">
                                        <strong>Plan Your Visit</strong>
                                        <p>{{ eventBooking.event.location }}</p>
                                    </template>
                                </div>
                            </div>
                            <div v-else-if="serviceBooking.provider?.address" class="step-item">
                                <span class="step-number">{{ needsDeposit ? '04' : '03' }}</span>
                                <div class="step-content">
                                    <strong>Plan Your Visit</strong>
                                    <p>{{ serviceBooking.provider.address }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="actions">
                        <AppLink href="/">
                            <Button label="Back to Home" severity="secondary" outlined class="action-btn" />
                        </AppLink>
                        <AppLink :href="isEventBooking ? '/events' : '/services'">
                            <Button :label="isEventBooking ? 'Browse More Events' : 'Browse More Experiences'" outlined class="action-btn action-btn--primary" />
                        </AppLink>
                    </div>
                </div>
            </section>
        </div>
    </GrandHorizonLayout>
</template>

<style scoped>
.confirmation-page {
    min-height: 100vh;
}

/* Confirmation Header */
.confirmation-header {
    padding: 6rem 2rem;
    text-align: center;
    background: var(--provider-dark, #1a1a1a);
}

.header-content {
    max-width: 600px;
    margin: 0 auto;
}

.success-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 2rem;
    background: var(--provider-primary, #c9a87c);
    display: flex;
    align-items: center;
    justify-content: center;
}

.success-icon i {
    font-size: 2rem;
    color: var(--provider-dark, #1a1a1a);
}

.header-label {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--provider-primary, #c9a87c);
    margin-bottom: 1rem;
}

.confirmation-header h1 {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: clamp(2.5rem, 6vw, 3.5rem);
    font-weight: 500;
    color: #ffffff;
    margin: 0 0 1rem 0;
}

.confirmation-header p {
    margin: 0;
    font-size: 1.0625rem;
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.7;
}

/* Confirmation Content */
.confirmation-content {
    padding: 4rem 0 6rem;
}

.content-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Detail Cards */
.detail-card {
    background: var(--provider-surface, #ffffff);
    border: 1px solid var(--provider-border, #e5e0d8);
    margin-bottom: 2rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--provider-border, #e5e0d8);
}

.card-header h2 {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--provider-text, #1a1a1a);
    margin: 0;
}

.card-body {
    padding: 2rem;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.detail-label {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--provider-secondary, #6a6a6a);
}

.detail-value {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.125rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
}

/* Payment Rows */
.payment-rows {
    margin-bottom: 1.5rem;
}

.payment-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
}

.payment-label {
    font-size: 0.9375rem;
    color: var(--provider-secondary, #6a6a6a);
}

.payment-value {
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
}

.payment-row--highlight .payment-label,
.payment-row--highlight .payment-value {
    color: var(--provider-primary, #c9a87c);
    font-weight: 600;
}

.payment-row--muted .payment-label,
.payment-row--muted .payment-value {
    color: var(--provider-secondary, #6a6a6a);
    font-size: 0.875rem;
}

/* Deposit Status */
.deposit-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.deposit-status--paid {
    background: var(--provider-success, #7cb798);
    color: #ffffff;
}

.deposit-action {
    margin-top: 0.5rem;
}

:deep(.pay-btn) {
    width: 100%;
    font-family: var(--font-body, 'Montserrat', sans-serif) !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    background-color: var(--provider-dark, #1a1a1a) !important;
    border-color: var(--provider-dark, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 1rem 2rem;
}

:deep(.pay-btn:hover) {
    background-color: var(--provider-primary, #c9a87c) !important;
    border-color: var(--provider-primary, #c9a87c) !important;
}

/* Next Steps */
.next-steps {
    margin-bottom: 3rem;
}

.next-steps h3 {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--provider-text, #1a1a1a);
    margin: 0 0 1.5rem 0;
}

.steps-grid {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.step-item {
    display: flex;
    gap: 1.25rem;
    padding: 1.25rem;
    background: var(--provider-surface, #ffffff);
    border: 1px solid var(--provider-border, #e5e0d8);
}

.step-number {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: var(--provider-primary, #c9a87c);
    flex-shrink: 0;
}

.step-content {
    flex: 1;
}

.step-content strong {
    display: block;
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.0625rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    margin-bottom: 0.25rem;
}

.step-content p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--provider-secondary, #6a6a6a);
    line-height: 1.6;
}

/* Actions */
.actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

:deep(.action-btn) {
    font-family: var(--font-body, 'Montserrat', sans-serif) !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    border-radius: 0 !important;
    padding: 1rem 2rem;
}

:deep(.action-btn--primary) {
    border-color: var(--provider-dark, #1a1a1a) !important;
    color: var(--provider-dark, #1a1a1a) !important;
}

:deep(.action-btn--primary:hover) {
    background-color: var(--provider-dark, #1a1a1a) !important;
    color: #ffffff !important;
}

/* Responsive */
@media (max-width: 768px) {
    .confirmation-header {
        padding: 4rem 1.5rem;
    }

    .success-icon {
        width: 64px;
        height: 64px;
    }

    .success-icon i {
        font-size: 1.5rem;
    }

    .content-container {
        padding: 0 1.5rem;
    }

    .confirmation-content {
        padding: 3rem 0 4rem;
    }

    .card-header,
    .card-body {
        padding: 1.25rem;
    }

    .detail-grid {
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }

    .step-item {
        padding: 1rem;
    }

    .actions {
        flex-direction: column;
    }

    .actions :deep(button) {
        width: 100%;
    }
}
</style>

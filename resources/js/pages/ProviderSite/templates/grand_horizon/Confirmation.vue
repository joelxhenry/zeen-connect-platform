<script setup lang="ts">
import GrandHorizonLayout from './components/GrandHorizonLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { ConfirmationPageProps } from '@/types/providersite';
import payment from '@/routes/payment';

const props = defineProps<ConfirmationPageProps>();

const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'success';
        case 'completed': return 'info';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
};
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
                    <h4 class="header-label">Reservation Complete</h4>
                    <h1>Thank You</h1>
                    <p>
                        {{ booking.requires_deposit && !booking.deposit_paid
                            ? 'Complete your deposit to secure your reservation.'
                            : 'Your booking has been confirmed. Details have been sent to your email.'
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
                            <h2>Reservation Details</h2>
                            <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                        </div>

                        <div class="card-body">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Experience</span>
                                    <span class="detail-value">{{ booking.service.name }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Duration</span>
                                    <span class="detail-value">{{ booking.service.duration_minutes }} minutes</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date</span>
                                    <span class="detail-value">{{ booking.formatted_date }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Time</span>
                                    <span class="detail-value">{{ booking.formatted_time }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Location</span>
                                    <span class="detail-value">{{ booking.provider?.business_name }}</span>
                                </div>
                                <div v-if="booking.provider?.address" class="detail-item">
                                    <span class="detail-label">Address</span>
                                    <span class="detail-value">{{ booking.provider.address }}</span>
                                </div>
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
                                <div class="payment-row">
                                    <span class="payment-label">Service total</span>
                                    <span class="payment-value">${{ booking.service_price.toFixed(2) }}</span>
                                </div>
                                <div v-if="booking.fee_payer === 'client' && booking.convenience_fee > 0" class="payment-row">
                                    <span class="payment-label">Service fee</span>
                                    <span class="payment-value">${{ booking.convenience_fee.toFixed(2) }}</span>
                                </div>
                                <div class="payment-row payment-row--highlight">
                                    <span class="payment-label">Deposit required</span>
                                    <span class="payment-value">${{ booking.deposit_amount.toFixed(2) }}</span>
                                </div>
                                <div class="payment-row payment-row--muted">
                                    <span class="payment-label">Balance due at appointment</span>
                                    <span class="payment-value">${{ booking.balance_amount.toFixed(2) }}</span>
                                </div>
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
                            <div v-if="booking.requires_deposit && !booking.deposit_paid" class="step-item">
                                <span class="step-number">01</span>
                                <div class="step-content">
                                    <strong>Pay Your Deposit</strong>
                                    <p>Secure your reservation with the required deposit payment.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <span class="step-number">{{ booking.requires_deposit && !booking.deposit_paid ? '02' : '01' }}</span>
                                <div class="step-content">
                                    <strong>Check Your Email</strong>
                                    <p>Confirmation details have been sent to your inbox.</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <span class="step-number">{{ booking.requires_deposit && !booking.deposit_paid ? '03' : '02' }}</span>
                                <div class="step-content">
                                    <strong>Mark Your Calendar</strong>
                                    <p>{{ booking.formatted_date }} at {{ booking.formatted_time }}</p>
                                </div>
                            </div>
                            <div v-if="booking.provider?.address" class="step-item">
                                <span class="step-number">{{ booking.requires_deposit && !booking.deposit_paid ? '04' : '03' }}</span>
                                <div class="step-content">
                                    <strong>Plan Your Visit</strong>
                                    <p>{{ booking.provider.address }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="actions">
                        <AppLink href="/">
                            <Button label="Back to Home" severity="secondary" outlined class="action-btn" />
                        </AppLink>
                        <AppLink href="/services">
                            <Button label="Browse More Experiences" outlined class="action-btn action-btn--primary" />
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

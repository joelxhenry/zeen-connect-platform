<script setup lang="ts">
import ShowcaseLayout from './components/ShowcaseLayout.vue';
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
    <ShowcaseLayout title="Booking Confirmed">
        <div class="confirmation-page">
            <div class="page-container">
                <!-- Success Header -->
                <div class="success-header">
                    <div class="success-icon">
                        <i class="pi pi-check"></i>
                    </div>
                    <h1>BOOKING SUBMITTED</h1>
                    <p>
                        {{ booking.requires_deposit && !booking.deposit_paid
                            ? 'Complete your deposit payment to secure your booking.'
                            : 'Confirmation details have been sent to your email.'
                        }}
                    </p>
                </div>

                <!-- Booking Card -->
                <div class="info-card">
                    <div class="card-header">
                        <span class="card-title">BOOKING DETAILS</span>
                        <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                    </div>

                    <div class="card-body">
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="pi pi-bookmark"></i>
                                SERVICE
                            </span>
                            <span class="detail-value">{{ booking.service.name }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="pi pi-clock"></i>
                                DURATION
                            </span>
                            <span class="detail-value">{{ booking.service.duration_minutes }} minutes</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="pi pi-calendar"></i>
                                DATE
                            </span>
                            <span class="detail-value">{{ booking.formatted_date }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="pi pi-clock"></i>
                                TIME
                            </span>
                            <span class="detail-value">{{ booking.formatted_time }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="pi pi-building"></i>
                                PROVIDER
                            </span>
                            <span class="detail-value">{{ booking.provider?.business_name }}</span>
                        </div>
                        <div v-if="booking.provider?.address" class="detail-row">
                            <span class="detail-label">
                                <i class="pi pi-map-marker"></i>
                                LOCATION
                            </span>
                            <span class="detail-value">{{ booking.provider.address }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Card -->
                <div v-if="booking.requires_deposit" class="info-card">
                    <div class="card-header">
                        <span class="card-title">PAYMENT SUMMARY</span>
                    </div>

                    <div class="card-body">
                        <div class="detail-row">
                            <span class="detail-label">SERVICE TOTAL</span>
                            <span class="detail-value price-value">${{ booking.service_price.toFixed(2) }}</span>
                        </div>
                        <div v-if="booking.fee_payer === 'client' && booking.convenience_fee > 0" class="detail-row">
                            <span class="detail-label">SERVICE FEE</span>
                            <span class="detail-value price-value">${{ booking.convenience_fee.toFixed(2) }}</span>
                        </div>
                        <div class="detail-row highlight">
                            <span class="detail-label">DEPOSIT REQUIRED</span>
                            <span class="detail-value price-value">${{ booking.deposit_amount.toFixed(2) }}</span>
                        </div>
                        <div class="detail-row muted">
                            <span class="detail-label">BALANCE DUE AT APPOINTMENT</span>
                            <span class="detail-value price-value">${{ booking.balance_amount.toFixed(2) }}</span>
                        </div>

                        <div v-if="booking.deposit_paid" class="deposit-status deposit-status--paid">
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
                        <li v-if="booking.requires_deposit && !booking.deposit_paid">
                            <strong>Pay your deposit</strong> to secure your booking
                        </li>
                        <li>
                            <strong>Check your email</strong> for confirmation details
                        </li>
                        <li>
                            <strong>Save the date:</strong> {{ booking.formatted_date }} at {{ booking.formatted_time }}
                        </li>
                        <li v-if="booking.provider?.address">
                            <strong>Arrive on time</strong> at {{ booking.provider.address }}
                        </li>
                    </ol>
                </div>

                <!-- Actions -->
                <div class="actions">
                    <AppLink href="/">
                        <Button label="BACK TO HOME" severity="secondary" outlined class="action-btn" />
                    </AppLink>
                    <AppLink href="/services">
                        <Button label="BROWSE SERVICES" outlined class="action-btn action-btn--secondary" />
                    </AppLink>
                </div>
            </div>
        </div>
    </ShowcaseLayout>
</template>

<style scoped>
.confirmation-page {
    min-height: 100vh;
    background: var(--provider-background, #fafafa);
    padding: 3rem 0 5rem;
}

.page-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Success Header */
.success-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.success-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: var(--provider-primary, #1a1a1a);
    display: flex;
    align-items: center;
    justify-content: center;
}

.success-icon i {
    font-size: 2rem;
    color: #fff;
}

.success-header h1 {
    margin: 0 0 0.75rem 0;
    font-size: 2rem;
    color: var(--provider-text, #1a1a1a);
}

.success-header p {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-secondary, #6b7280);
    line-height: 1.6;
}

/* Info Cards */
.info-card {
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
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.625rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.15em;
}

.card-body {
    padding: 1.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0;
}

.detail-row:first-child {
    padding-top: 0;
}

.detail-row:last-of-type {
    padding-bottom: 0;
}

.detail-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.625rem;
    font-weight: 700;
    color: var(--provider-secondary, #6b7280);
    letter-spacing: 0.1em;
}

.detail-label i {
    color: var(--provider-primary, #1a1a1a);
    font-size: 0.875rem;
}

.detail-value {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--provider-text, #1a1a1a);
}

.price-value {
    font-family: var(--font-mono, 'Space Mono', monospace);
}

.detail-row.highlight .detail-label,
.detail-row.highlight .detail-value {
    color: var(--provider-primary, #1a1a1a);
}

.detail-row.muted .detail-label,
.detail-row.muted .detail-value {
    color: var(--provider-secondary, #9ca3af);
}

/* Deposit Status */
.deposit-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    margin-top: 1.5rem;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
}

.deposit-status--paid {
    background: var(--provider-primary, #1a1a1a);
    color: #fff;
}

.deposit-action {
    margin-top: 1.5rem;
}

:deep(.pay-btn) {
    width: 100%;
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    background-color: var(--provider-primary, #1a1a1a) !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
}

:deep(.pay-btn:hover) {
    background-color: var(--provider-primary-hover, #333) !important;
    border-color: var(--provider-primary-hover, #333) !important;
}

/* Next Steps */
.next-steps {
    margin-bottom: 2.5rem;
}

.next-steps h2 {
    margin: 0 0 1.25rem 0;
    font-size: 1rem;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.1em;
}

.steps-list {
    margin: 0;
    padding-left: 1.25rem;
}

.steps-list li {
    padding: 0.625rem 0;
    color: var(--provider-secondary, #6b7280);
    font-size: 1rem;
    line-height: 1.6;
}

.steps-list li strong {
    color: var(--provider-text, #1a1a1a);
}

/* Actions */
.actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

:deep(.action-btn) {
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    border-radius: 0 !important;
}

:deep(.action-btn--secondary) {
    border-color: var(--provider-primary, #1a1a1a) !important;
    color: var(--provider-primary, #1a1a1a) !important;
}

:deep(.action-btn--secondary:hover) {
    background-color: var(--provider-primary-10, rgba(26, 26, 26, 0.1)) !important;
}

/* Responsive */
@media (max-width: 640px) {
    .confirmation-page {
        padding: 2rem 0 4rem;
    }

    .page-container {
        padding: 0 1rem;
    }

    .success-icon {
        width: 64px;
        height: 64px;
    }

    .success-icon i {
        font-size: 1.5rem;
    }

    .success-header h1 {
        font-size: 1.5rem;
    }

    .card-header {
        padding: 1rem;
    }

    .card-body {
        padding: 1rem;
    }

    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .actions {
        flex-direction: column;
    }

    .actions :deep(button) {
        width: 100%;
    }
}
</style>

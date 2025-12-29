<script setup lang="ts">
import ArchitectBoldLayout from './components/ArchitectBoldLayout.vue';
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
    <ArchitectBoldLayout title="Booking Confirmed">
        <div class="confirmation-page">
            <div class="page-container">
                <!-- Success Message -->
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
                <div class="booking-card">
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

                <!-- Payment Card (if deposit required) -->
                <div v-if="booking.requires_deposit" class="payment-card">
                    <div class="card-header">
                        <span class="card-title">PAYMENT SUMMARY</span>
                    </div>

                    <div class="card-body">
                        <div class="detail-row">
                            <span class="detail-label">SERVICE TOTAL</span>
                            <span class="detail-value">${{ booking.service_price.toFixed(2) }}</span>
                        </div>
                        <div v-if="booking.fee_payer === 'client' && booking.convenience_fee > 0" class="detail-row">
                            <span class="detail-label">SERVICE FEE</span>
                            <span class="detail-value">${{ booking.convenience_fee.toFixed(2) }}</span>
                        </div>
                        <div class="detail-row highlight">
                            <span class="detail-label">DEPOSIT REQUIRED</span>
                            <span class="detail-value">${{ booking.deposit_amount.toFixed(2) }}</span>
                        </div>
                        <div class="detail-row muted">
                            <span class="detail-label">BALANCE DUE AT APPOINTMENT</span>
                            <span class="detail-value">${{ booking.balance_amount.toFixed(2) }}</span>
                        </div>

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
                        <Button label="BROWSE SERVICES" outlined class="action-btn secondary" />
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

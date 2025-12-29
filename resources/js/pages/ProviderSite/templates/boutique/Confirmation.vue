<script setup lang="ts">
import BoutiqueLayout from './components/BoutiqueLayout.vue';
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
    <BoutiqueLayout title="Booking Confirmed">
        <div class="confirmation-page">
            <div class="page-container">
                <!-- Success Header -->
                <div class="success-header">
                    <div class="success-icon">
                        <i class="pi pi-check"></i>
                    </div>
                    <h1>Booking Confirmed</h1>
                    <p>
                        {{ booking.requires_deposit && !booking.deposit_paid
                            ? 'Complete your deposit payment to secure your booking.'
                            : 'We\'ve sent a confirmation to your email.'
                        }}
                    </p>
                </div>

                <!-- Booking Details Card -->
                <div class="info-card">
                    <div class="card-header">
                        <span class="card-title">Booking Details</span>
                        <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                    </div>

                    <div class="card-body">
                        <div class="detail-item">
                            <span class="detail-label">Service</span>
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
                            <span class="detail-label">With</span>
                            <span class="detail-value">{{ booking.provider?.business_name }}</span>
                        </div>
                        <div v-if="booking.provider?.address" class="detail-item">
                            <span class="detail-label">Location</span>
                            <span class="detail-value">{{ booking.provider.address }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Card -->
                <div v-if="booking.requires_deposit" class="info-card">
                    <div class="card-header">
                        <span class="card-title">Payment Summary</span>
                    </div>

                    <div class="card-body">
                        <div class="detail-item">
                            <span class="detail-label">Service total</span>
                            <span class="detail-value">${{ booking.service_price.toFixed(2) }}</span>
                        </div>
                        <div v-if="booking.fee_payer === 'client' && booking.convenience_fee > 0" class="detail-item">
                            <span class="detail-label">Service fee</span>
                            <span class="detail-value">${{ booking.convenience_fee.toFixed(2) }}</span>
                        </div>
                        <div class="detail-item detail-item--highlight">
                            <span class="detail-label">Deposit required</span>
                            <span class="detail-value">${{ booking.deposit_amount.toFixed(2) }}</span>
                        </div>
                        <div class="detail-item detail-item--muted">
                            <span class="detail-label">Balance due at appointment</span>
                            <span class="detail-value">${{ booking.balance_amount.toFixed(2) }}</span>
                        </div>

                        <div v-if="booking.deposit_paid" class="deposit-status deposit-status--paid">
                            <i class="pi pi-check-circle"></i>
                            <span>Deposit paid</span>
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
                    <h2>What's next?</h2>
                    <ul class="steps-list">
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
                            <strong>Plan your visit:</strong> {{ booking.provider.address }}
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="actions">
                    <AppLink href="/">
                        <Button label="Back to Home" severity="secondary" outlined class="action-btn" />
                    </AppLink>
                    <AppLink href="/services">
                        <Button label="Browse Services" outlined class="action-btn action-btn--secondary" />
                    </AppLink>
                </div>
            </div>
        </div>
    </BoutiqueLayout>
</template>

<style scoped>
.confirmation-page {
    min-height: 100vh;
    background: var(--provider-background, #fdfcfb);
    padding: 3rem 0 5rem;
}

.page-container {
    max-width: 550px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Success Header */
.success-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.success-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 1.5rem;
    background: var(--provider-success, #7cb798);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.success-icon i {
    font-size: 1.75rem;
    color: #fff;
}

.success-header h1 {
    margin: 0 0 0.75rem 0;
    font-size: 2rem;
    color: var(--provider-text, #3d3d3d);
}

.success-header p {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-secondary, #8a8a8a);
    line-height: 1.6;
}

/* Info Cards */
.info-card {
    background: var(--provider-surface, #fff);
    margin-bottom: 1.5rem;
    border: 1px solid var(--provider-border, #ebe8e4);
    border-radius: 1rem;
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

.card-title {
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 1.125rem;
    font-weight: 500;
    color: var(--provider-text, #3d3d3d);
}

.card-body {
    padding: 1.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0;
}

.detail-item:first-child {
    padding-top: 0;
}

.detail-item:last-of-type {
    padding-bottom: 0;
}

.detail-label {
    font-size: 0.875rem;
    color: var(--provider-secondary, #8a8a8a);
}

.detail-value {
    font-size: 0.9375rem;
    color: var(--provider-text, #3d3d3d);
}

.detail-item--highlight .detail-label,
.detail-item--highlight .detail-value {
    color: var(--provider-primary, #8b7355);
    font-weight: 500;
}

.detail-item--muted .detail-label,
.detail-item--muted .detail-value {
    color: var(--provider-secondary, #9a9a9a);
}

/* Deposit Status */
.deposit-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    margin-top: 1.5rem;
    border-radius: 0.5rem;
    font-size: 0.9375rem;
    font-weight: 500;
}

.deposit-status--paid {
    background: var(--provider-success, #7cb798);
    color: #fff;
}

.deposit-action {
    margin-top: 1.5rem;
}

:deep(.pay-btn) {
    width: 100%;
    font-family: var(--font-body, 'Nunito Sans', sans-serif) !important;
    font-weight: 500;
    font-size: 0.9375rem;
    letter-spacing: 0.02em;
    background-color: var(--provider-primary, #8b7355) !important;
    border-color: var(--provider-primary, #8b7355) !important;
    border-radius: 2rem !important;
}

:deep(.pay-btn:hover) {
    background-color: var(--provider-primary-hover, #6d5a43) !important;
    border-color: var(--provider-primary-hover, #6d5a43) !important;
}

/* Next Steps */
.next-steps {
    margin-bottom: 2.5rem;
}

.next-steps h2 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    color: var(--provider-text, #3d3d3d);
}

.steps-list {
    margin: 0;
    padding-left: 1.25rem;
    list-style: disc;
}

.steps-list li {
    padding: 0.5rem 0;
    font-size: 0.9375rem;
    color: var(--provider-secondary, #8a8a8a);
    line-height: 1.6;
}

.steps-list li strong {
    color: var(--provider-text, #3d3d3d);
    font-weight: 500;
}

/* Actions */
.actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

:deep(.action-btn) {
    font-family: var(--font-body, 'Nunito Sans', sans-serif) !important;
    font-weight: 500;
    font-size: 0.9375rem;
    border-radius: 2rem !important;
}

:deep(.action-btn--secondary) {
    border-color: var(--provider-primary, #8b7355) !important;
    color: var(--provider-primary, #8b7355) !important;
}

:deep(.action-btn--secondary:hover) {
    background-color: var(--provider-primary-05, rgba(139, 115, 85, 0.05)) !important;
}

/* Responsive */
@media (max-width: 600px) {
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

    .detail-item {
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

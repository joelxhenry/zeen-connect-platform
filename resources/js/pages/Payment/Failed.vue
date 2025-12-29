<script setup lang="ts">
import { computed } from 'vue';
import PaymentLayout from '@/components/layout/PaymentLayout.vue';
import Button from 'primevue/button';
import paymentRoutes from '@/routes/payment';
import providersite from '@/routes/providersite';
import { home } from '@/routes';
import { resolveUrl } from '@/utils/url';

interface Props {
    payment: {
        uuid: string;
        amount_display: string;
        failure_reason: string | null;
    };
    booking: {
        uuid: string;
        provider_slug: string;
        is_guest: boolean;
    };
    error: string | null;
    isAuthenticated: boolean;
}

const props = defineProps<Props>();

const errorMessage = computed(() => {
    return props.error || props.payment.failure_reason || 'Your payment could not be processed. Please try again.';
});

const checkoutUrl = computed(() => {
    return resolveUrl(paymentRoutes.checkout({ bookingUuid: props.booking.uuid }).url);
});

const confirmationUrl = computed(() => {
    return resolveUrl(providersite.book.confirmation({
        provider: props.booking.provider_slug,
        uuid: props.booking.uuid
    }).url);
});

const homeUrl = computed(() => {
    return resolveUrl(home().url);
});
</script>

<template>
    <PaymentLayout title="Payment Failed" step="failed">
        <div class="failed-content">
            <!-- Error Header -->
            <div class="failed-header">
                <div class="failed-icon">
                    <div class="icon-ring"></div>
                    <div class="icon-x">
                        <i class="pi pi-times"></i>
                    </div>
                </div>
                <h1>Payment Failed</h1>
                <p>We couldn't process your payment. Don't worry, you can try again.</p>
            </div>

            <!-- Error Details -->
            <div class="error-card">
                <div class="error-icon">
                    <i class="pi pi-exclamation-triangle"></i>
                </div>
                <div class="error-content">
                    <span class="error-label">What happened?</span>
                    <span class="error-message">{{ errorMessage }}</span>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="info-card">
                <div class="card-header">
                    <span class="card-title">Payment Details</span>
                    <span class="status-badge">
                        <i class="pi pi-times-circle"></i>
                        Failed
                    </span>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Amount</span>
                        <span class="info-value">{{ payment.amount_display }}</span>
                    </div>
                </div>
            </div>

            <!-- Suggestions -->
            <div class="suggestions-card">
                <h3>Things to try:</h3>
                <ul>
                    <li>
                        <i class="pi pi-credit-card"></i>
                        <span>Check that your card details are correct</span>
                    </li>
                    <li>
                        <i class="pi pi-wallet"></i>
                        <span>Ensure you have sufficient funds</span>
                    </li>
                    <li>
                        <i class="pi pi-sync"></i>
                        <span>Try a different payment method</span>
                    </li>
                    <li>
                        <i class="pi pi-phone"></i>
                        <span>Contact your bank if the issue persists</span>
                    </li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="action-buttons">
                <AppLink :href="checkoutUrl" class="action-primary">
                    <Button
                        label="Try Again"
                        icon="pi pi-refresh"
                        class="primary-btn"
                    />
                </AppLink>
                <AppLink :href="confirmationUrl" class="action-secondary">
                    <Button
                        label="Back to Booking"
                        icon="pi pi-arrow-left"
                        class="secondary-btn"
                    />
                </AppLink>
                <AppLink :href="homeUrl" class="action-tertiary">
                    <Button
                        label="Return to Home"
                        text
                        class="tertiary-btn"
                    />
                </AppLink>
            </div>
        </div>
    </PaymentLayout>
</template>

<style scoped>
.failed-content {
    width: 100%;
}

/* Header */
.failed-header {
    text-align: center;
    margin-bottom: 2rem;
}

.failed-icon {
    position: relative;
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1.5rem;
}

.icon-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.05));
    animation: pulse 2s ease-in-out infinite;
}

.icon-x {
    position: absolute;
    inset: 0.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
}

.icon-x i {
    font-size: 2rem;
    color: white;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
}

.failed-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0 0 0.5rem 0;
}

.failed-header p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
    max-width: 280px;
    margin-left: auto;
    margin-right: auto;
}

/* Error Card */
.error-card {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 1rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.error-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    background: rgba(239, 68, 68, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.error-icon i {
    font-size: 1rem;
    color: #dc2626;
}

.error-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.error-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #991b1b;
}

.error-message {
    font-size: 0.8125rem;
    color: #b91c1c;
    line-height: 1.4;
}

/* Info Card */
.info-card {
    background: white;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0D1F1B;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #dc2626;
    background: rgba(239, 68, 68, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
}

.status-badge i {
    font-size: 0.75rem;
}

.card-body {
    padding: 1.25rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
}

/* Suggestions */
.suggestions-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.suggestions-card h3 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0 0 0.75rem 0;
}

.suggestions-card ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}

.suggestions-card li {
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
    font-size: 0.8125rem;
    color: #4b5563;
}

.suggestions-card li i {
    color: #9ca3af;
    font-size: 0.875rem;
    margin-top: 0.125rem;
    flex-shrink: 0;
}

/* Actions */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.action-primary,
.action-secondary,
.action-tertiary {
    display: block;
}

.primary-btn {
    width: 100%;
    height: 3rem;
    border-radius: 0.75rem;
    font-weight: 600;
    background: linear-gradient(135deg, #106B4F 0%, #0d5a42 100%);
    border: none;
    box-shadow: 0 2px 8px rgba(16, 107, 79, 0.2);
}

.secondary-btn {
    width: 100%;
    height: 3rem;
    border-radius: 0.75rem;
    font-weight: 500;
    background: white;
    border: 1px solid #e5e7eb;
    color: #0D1F1B;
}

.secondary-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.tertiary-btn {
    width: 100%;
    height: 2.5rem;
    color: #6b7280;
}

.tertiary-btn:hover {
    color: #106B4F;
    background: transparent;
}
</style>

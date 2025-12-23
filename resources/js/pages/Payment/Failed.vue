<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';

interface Payment {
    uuid: string;
    amount_display: string;
    failure_reason: string | null;
}

defineProps<{
    payment: Payment;
    booking_uuid: string;
    error?: string;
}>();
</script>

<template>
    <PublicLayout>
        <Head title="Payment Failed" />

        <div class="failed-page">
            <div class="failed-container">
                <Card class="failed-card">
                    <template #content>
                        <div class="failed-content">
                            <div class="failed-icon">
                                <i class="pi pi-times"></i>
                            </div>

                            <h1 class="failed-title">Payment Failed</h1>
                            <p class="failed-subtitle">We couldn't process your payment</p>

                            <div class="error-details" v-if="error || payment.failure_reason">
                                <i class="pi pi-exclamation-triangle"></i>
                                <p>{{ error || payment.failure_reason }}</p>
                            </div>

                            <div class="help-section">
                                <h3>What can you do?</h3>
                                <ul>
                                    <li>Check your card details and try again</li>
                                    <li>Make sure you have sufficient funds</li>
                                    <li>Try a different payment method</li>
                                    <li>Contact your bank if the problem persists</li>
                                </ul>
                            </div>

                            <div class="actions">
                                <Link :href="route('payment.checkout', booking_uuid)">
                                    <Button
                                        label="Try Again"
                                        icon="pi pi-refresh"
                                        class="retry-button"
                                    />
                                </Link>
                                <Link :href="route('client.bookings.show', booking_uuid)">
                                    <Button
                                        label="Back to Booking"
                                        severity="secondary"
                                        outlined
                                    />
                                </Link>
                            </div>

                            <div class="support-notice">
                                <p>
                                    Need help?
                                    <a href="mailto:support@zeenconnect.com" class="link">Contact Support</a>
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.failed-page {
    min-height: calc(100vh - 80px);
    background: var(--p-surface-50);
    padding: 2rem 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.failed-container {
    max-width: 500px;
    width: 100%;
}

.failed-card {
    border-radius: 16px;
    overflow: hidden;
}

.failed-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1rem 0;
}

.failed-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--p-red-400), var(--p-red-500));
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.failed-icon i {
    font-size: 2.5rem;
    color: white;
}

.failed-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0 0 0.5rem 0;
}

.failed-subtitle {
    color: var(--p-surface-600);
    margin: 0 0 1.5rem 0;
}

.error-details {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    background: var(--p-red-50);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    width: 100%;
    text-align: left;
}

.error-details i {
    color: var(--p-red-500);
    font-size: 1.25rem;
    flex-shrink: 0;
}

.error-details p {
    color: var(--p-red-700);
    font-size: 0.9375rem;
    margin: 0;
}

.help-section {
    width: 100%;
    background: var(--p-surface-50);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    text-align: left;
}

.help-section h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.75rem 0;
}

.help-section ul {
    margin: 0;
    padding-left: 1.25rem;
}

.help-section li {
    color: var(--p-surface-600);
    font-size: 0.9375rem;
    padding: 0.25rem 0;
}

.actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
    margin-bottom: 1.5rem;
}

.actions a {
    text-decoration: none;
}

.retry-button {
    width: 100%;
}

.actions :deep(.p-button-secondary) {
    width: 100%;
}

.support-notice {
    color: var(--p-surface-500);
    font-size: 0.875rem;
}

.support-notice p {
    margin: 0;
}

.link {
    color: var(--p-primary-color);
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}

@media (max-width: 640px) {
    .failed-page {
        padding: 1rem;
        align-items: flex-start;
        padding-top: 2rem;
    }

    .failed-title {
        font-size: 1.5rem;
    }
}
</style>

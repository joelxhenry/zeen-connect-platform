<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';

interface Payment {
    uuid: string;
    amount_display: string;
    card_display: string | null;
    paid_at: string;
}

interface Booking {
    uuid: string;
    provider_name: string;
    service_name: string;
    formatted_date: string;
    formatted_time: string;
}

defineProps<{
    payment: Payment;
    booking: Booking;
}>();
</script>

<template>
    <PublicLayout>
        <Head title="Payment Successful" />

        <div class="success-page">
            <div class="success-container">
                <Card class="success-card">
                    <template #content>
                        <div class="success-content">
                            <div class="success-icon">
                                <i class="pi pi-check"></i>
                            </div>

                            <h1 class="success-title">Payment Successful!</h1>
                            <p class="success-subtitle">Your booking has been confirmed</p>

                            <div class="booking-summary">
                                <div class="summary-item">
                                    <span class="label">Provider</span>
                                    <span class="value">{{ booking.provider_name }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Service</span>
                                    <span class="value">{{ booking.service_name }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Date</span>
                                    <span class="value">{{ booking.formatted_date }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Time</span>
                                    <span class="value">{{ booking.formatted_time }}</span>
                                </div>
                                <div class="summary-item highlight">
                                    <span class="label">Amount Paid</span>
                                    <span class="value">{{ payment.amount_display }}</span>
                                </div>
                                <div class="summary-item" v-if="payment.card_display">
                                    <span class="label">Payment Method</span>
                                    <span class="value">{{ payment.card_display }}</span>
                                </div>
                            </div>

                            <div class="confirmation-notice">
                                <i class="pi pi-envelope"></i>
                                <p>A confirmation email has been sent to your registered email address.</p>
                            </div>

                            <div class="actions">
                                <Link :href="route('client.bookings.show', booking.uuid)">
                                    <Button
                                        label="View Booking Details"
                                        icon="pi pi-eye"
                                        class="view-button"
                                    />
                                </Link>
                                <Link :href="route('client.dashboard')">
                                    <Button
                                        label="Go to Dashboard"
                                        severity="secondary"
                                        outlined
                                    />
                                </Link>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.success-page {
    min-height: calc(100vh - 80px);
    background: var(--p-surface-50);
    padding: 2rem 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.success-container {
    max-width: 500px;
    width: 100%;
}

.success-card {
    border-radius: 16px;
    overflow: hidden;
}

.success-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1rem 0;
}

.success-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--p-green-400), var(--p-green-500));
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.success-icon i {
    font-size: 2.5rem;
    color: white;
}

.success-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0 0 0.5rem 0;
}

.success-subtitle {
    color: var(--p-surface-600);
    margin: 0 0 2rem 0;
}

.booking-summary {
    width: 100%;
    background: var(--p-surface-50);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.625rem 0;
    border-bottom: 1px solid var(--p-surface-200);
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item.highlight {
    background: var(--p-green-50);
    margin: 0.5rem -1.25rem;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    border-bottom: none;
}

.summary-item .label {
    color: var(--p-surface-600);
    font-size: 0.9375rem;
}

.summary-item .value {
    font-weight: 600;
    color: var(--p-surface-900);
}

.summary-item.highlight .value {
    color: var(--p-green-700);
}

.confirmation-notice {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: var(--p-blue-50);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    width: 100%;
}

.confirmation-notice i {
    color: var(--p-blue-500);
    font-size: 1.25rem;
}

.confirmation-notice p {
    color: var(--p-blue-700);
    font-size: 0.875rem;
    margin: 0;
    text-align: left;
}

.actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
}

.actions a {
    text-decoration: none;
}

.view-button {
    width: 100%;
}

.actions :deep(.p-button-secondary) {
    width: 100%;
}

@media (max-width: 640px) {
    .success-page {
        padding: 1rem;
        align-items: flex-start;
        padding-top: 2rem;
    }

    .success-title {
        font-size: 1.5rem;
    }
}
</style>

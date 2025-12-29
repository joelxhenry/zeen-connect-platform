<script setup lang="ts">
import { computed } from 'vue';
import PaymentLayout from '@/components/layout/PaymentLayout.vue';
import Button from 'primevue/button';
import client from '@/routes/client';
import providersite from '@/routes/providersite';
import { home } from '@/routes';
import { resolveUrl } from '@/utils/url';

interface Props {
    payment: {
        uuid: string;
        amount_display: string;
        card_display: string | null;
        paid_at: string | null;
        payment_type: 'deposit' | 'full';
    };
    booking: {
        uuid: string;
        provider_name: string;
        provider_slug: string;
        service_name: string;
        formatted_date: string;
        formatted_time: string;
        is_guest: boolean;
    };
    isAuthenticated: boolean;
}

const props = defineProps<Props>();

const confirmationUrl = computed(() => {
    return resolveUrl(providersite.book.confirmation({
        provider: props.booking.provider_slug,
        uuid: props.booking.uuid
    }).url);
});

const bookingsUrl = computed(() => {
    return resolveUrl(client.bookings.index().url);
});

const homeUrl = computed(() => {
    return resolveUrl(home().url);
});
</script>

<template>
    <PaymentLayout title="Payment Successful" step="success">
        <div class="success-content">
            <!-- Success Animation -->
            <div class="success-header">
                <div class="success-icon">
                    <div class="icon-ring"></div>
                    <div class="icon-check">
                        <i class="pi pi-check"></i>
                    </div>
                </div>
                <h1>Payment Successful!</h1>
                <p>
                    {{ payment.payment_type === 'deposit'
                        ? 'Your deposit has been received and your booking is confirmed.'
                        : 'Your payment has been processed successfully.'
                    }}
                </p>
            </div>

            <!-- Payment Receipt -->
            <div class="receipt-card">
                <div class="receipt-header">
                    <span class="receipt-title">Payment Receipt</span>
                    <span class="receipt-status">
                        <i class="pi pi-check-circle"></i>
                        Confirmed
                    </span>
                </div>
                <div class="receipt-body">
                    <div class="receipt-amount">
                        <span class="amount-label">Amount Paid</span>
                        <span class="amount-value">{{ payment.amount_display }}</span>
                    </div>
                    <div class="receipt-details">
                        <div v-if="payment.card_display" class="detail-row">
                            <span class="detail-label">Payment Method</span>
                            <span class="detail-value">{{ payment.card_display }}</span>
                        </div>
                        <div v-if="payment.paid_at" class="detail-row">
                            <span class="detail-label">Date</span>
                            <span class="detail-value">{{ payment.paid_at }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Type</span>
                            <span class="detail-value capitalize">{{ payment.payment_type }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Info -->
            <div class="booking-card">
                <div class="card-header">
                    <span class="card-title">Your Appointment</span>
                </div>
                <div class="card-body">
                    <div class="booking-row">
                        <div class="row-icon service">
                            <i class="pi pi-briefcase"></i>
                        </div>
                        <div class="row-content">
                            <span class="row-label">{{ booking.service_name }}</span>
                            <span class="row-sublabel">{{ booking.provider_name }}</span>
                        </div>
                    </div>
                    <div class="booking-row">
                        <div class="row-icon date">
                            <i class="pi pi-calendar"></i>
                        </div>
                        <div class="row-content">
                            <span class="row-label">{{ booking.formatted_date }}</span>
                            <span class="row-sublabel">{{ booking.formatted_time }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <h3>What's Next?</h3>
                <ul>
                    <li>
                        <i class="pi pi-envelope"></i>
                        <span>Confirmation email sent to your inbox</span>
                    </li>
                    <li>
                        <i class="pi pi-calendar-plus"></i>
                        <span>Save {{ booking.formatted_date }} at {{ booking.formatted_time }}</span>
                    </li>
                    <li>
                        <i class="pi pi-clock"></i>
                        <span>Arrive on time for your appointment</span>
                    </li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="action-buttons">
                <AppLink v-if="isAuthenticated" :href="bookingsUrl" class="action-primary">
                    <Button
                        label="View My Bookings"
                        icon="pi pi-calendar"
                        class="primary-btn"
                    />
                </AppLink>
                <AppLink :href="confirmationUrl" :class="isAuthenticated ? 'action-secondary' : 'action-primary'">
                    <Button
                        label="View Booking Details"
                        icon="pi pi-eye"
                        :class="isAuthenticated ? 'secondary-btn' : 'primary-btn'"
                    />
                </AppLink>
                <AppLink :href="homeUrl" class="action-tertiary">
                    <Button
                        label="Back to Home"
                        text
                        class="tertiary-btn"
                    />
                </AppLink>
            </div>
        </div>
    </PaymentLayout>
</template>

<style scoped>
.success-content {
    width: 100%;
}

/* Header with Animation */
.success-header {
    text-align: center;
    margin-bottom: 2rem;
}

.success-icon {
    position: relative;
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1.5rem;
}

.icon-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(16, 107, 79, 0.15), rgba(16, 107, 79, 0.05));
    animation: pulse 2s ease-in-out infinite;
}

.icon-check {
    position: absolute;
    inset: 0.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, #106B4F, #14967a);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(16, 107, 79, 0.3);
}

.icon-check i {
    font-size: 2rem;
    color: white;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
}

.success-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0 0 0.5rem 0;
}

.success-header p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
    max-width: 280px;
    margin-left: auto;
    margin-right: auto;
}

/* Receipt Card */
.receipt-card {
    background: linear-gradient(135deg, #106B4F 0%, #0d5a42 100%);
    border-radius: 1rem;
    overflow: hidden;
    margin-bottom: 1rem;
    color: white;
    box-shadow: 0 4px 16px rgba(16, 107, 79, 0.25);
}

.receipt-header {
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.receipt-title {
    font-size: 0.875rem;
    font-weight: 500;
    opacity: 0.9;
}

.receipt-status {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
}

.receipt-status i {
    font-size: 0.75rem;
}

.receipt-body {
    padding: 1.25rem;
}

.receipt-amount {
    text-align: center;
    padding-bottom: 1rem;
    border-bottom: 1px dashed rgba(255, 255, 255, 0.2);
    margin-bottom: 1rem;
}

.amount-label {
    display: block;
    font-size: 0.75rem;
    opacity: 0.8;
    margin-bottom: 0.25rem;
}

.amount-value {
    font-size: 2rem;
    font-weight: 700;
}

.receipt-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.8125rem;
}

.detail-label {
    opacity: 0.8;
}

.detail-value {
    font-weight: 500;
}

.detail-value.capitalize {
    text-transform: capitalize;
}

/* Booking Card */
.booking-card {
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
}

.card-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0D1F1B;
}

.card-body {
    padding: 1.25rem;
}

.booking-row {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.5rem 0;
}

.booking-row:first-child {
    padding-top: 0;
}

.booking-row:last-child {
    padding-bottom: 0;
}

.row-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.row-icon.service {
    background: rgba(16, 107, 79, 0.1);
    color: #106B4F;
}

.row-icon.date {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.row-icon i {
    font-size: 1rem;
}

.row-content {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.row-label {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #0D1F1B;
}

.row-sublabel {
    font-size: 0.8125rem;
    color: #6b7280;
}

/* Next Steps */
.next-steps {
    background: rgba(16, 107, 79, 0.04);
    border: 1px solid rgba(16, 107, 79, 0.1);
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.next-steps h3 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0 0 0.75rem 0;
}

.next-steps ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}

.next-steps li {
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
    font-size: 0.8125rem;
    color: #4b5563;
}

.next-steps li i {
    color: #106B4F;
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

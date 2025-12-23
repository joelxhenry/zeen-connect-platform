<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Checkbox from 'primevue/checkbox';

interface Booking {
    uuid: string;
    provider: {
        name: string;
        slug: string;
    };
    service: {
        name: string;
        duration_minutes: number;
    };
    formatted_date: string;
    formatted_time: string;
    service_price: number;
    platform_fee: number;
    total_amount: number;
    total_display: string;
}

const props = defineProps<{
    booking: Booking;
}>();

const termsAccepted = ref(false);
const processing = ref(false);

const formatPrice = (price: number): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(price);
};

const processPayment = () => {
    if (!termsAccepted.value) {
        return;
    }

    processing.value = true;
    router.post(route('payment.process', props.booking.uuid), {}, {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <PublicLayout>
        <Head title="Complete Payment" />

        <div class="checkout-page">
            <div class="checkout-container">
                <div class="checkout-header">
                    <Link :href="route('client.bookings.show', booking.uuid)" class="back-link">
                        <i class="pi pi-arrow-left"></i>
                        Back to booking
                    </Link>
                    <h1 class="page-title">Complete Your Payment</h1>
                    <p class="page-subtitle">Review your booking and pay securely</p>
                </div>

                <div class="checkout-content">
                    <!-- Order Summary -->
                    <Card class="summary-card">
                        <template #title>
                            <div class="card-header">
                                <i class="pi pi-shopping-cart"></i>
                                <span>Order Summary</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="booking-details">
                                <div class="provider-info">
                                    <h3 class="provider-name">{{ booking.provider.name }}</h3>
                                    <p class="service-name">{{ booking.service.name }}</p>
                                </div>

                                <div class="appointment-info">
                                    <div class="info-row">
                                        <i class="pi pi-calendar"></i>
                                        <span>{{ booking.formatted_date }}</span>
                                    </div>
                                    <div class="info-row">
                                        <i class="pi pi-clock"></i>
                                        <span>{{ booking.formatted_time }}</span>
                                    </div>
                                    <div class="info-row">
                                        <i class="pi pi-hourglass"></i>
                                        <span>{{ booking.service.duration_minutes }} minutes</span>
                                    </div>
                                </div>

                                <Divider />

                                <div class="price-breakdown">
                                    <div class="price-row">
                                        <span>Service</span>
                                        <span>{{ formatPrice(booking.service_price) }}</span>
                                    </div>
                                    <div class="price-row">
                                        <span>Service fee</span>
                                        <span>{{ formatPrice(booking.platform_fee) }}</span>
                                    </div>
                                    <Divider />
                                    <div class="price-row total">
                                        <span>Total</span>
                                        <span>{{ booking.total_display }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Payment Section -->
                    <Card class="payment-card">
                        <template #title>
                            <div class="card-header">
                                <i class="pi pi-credit-card"></i>
                                <span>Payment</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="payment-info">
                                <div class="secure-badge">
                                    <i class="pi pi-lock"></i>
                                    <span>Secure payment powered by PowerTranz</span>
                                </div>

                                <p class="payment-description">
                                    You will be redirected to a secure payment page to enter your card details.
                                    Your payment information is encrypted and never stored on our servers.
                                </p>

                                <div class="card-brands">
                                    <span class="brand">Visa</span>
                                    <span class="brand">Mastercard</span>
                                    <span class="brand">Discover</span>
                                </div>

                                <Divider />

                                <div class="terms-section">
                                    <div class="terms-checkbox">
                                        <Checkbox v-model="termsAccepted" :binary="true" inputId="terms" />
                                        <label for="terms">
                                            I agree to the
                                            <a href="#" class="link">Terms of Service</a>
                                            and
                                            <a href="#" class="link">Cancellation Policy</a>
                                        </label>
                                    </div>
                                </div>

                                <Button
                                    :label="processing ? 'Processing...' : `Pay ${booking.total_display}`"
                                    icon="pi pi-lock"
                                    class="pay-button"
                                    :disabled="!termsAccepted || processing"
                                    :loading="processing"
                                    @click="processPayment"
                                />

                                <p class="cancel-info">
                                    <i class="pi pi-info-circle"></i>
                                    Free cancellation up to 24 hours before your appointment
                                </p>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.checkout-page {
    min-height: calc(100vh - 80px);
    background: var(--p-surface-50);
    padding: 2rem 1rem;
}

.checkout-container {
    max-width: 800px;
    margin: 0 auto;
}

.checkout-header {
    margin-bottom: 2rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--p-surface-600);
    text-decoration: none;
    font-size: 0.875rem;
    margin-bottom: 1rem;
    transition: color 0.2s;
}

.back-link:hover {
    color: var(--p-primary-color);
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0 0 0.5rem 0;
}

.page-subtitle {
    color: var(--p-surface-600);
    margin: 0;
}

.checkout-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.summary-card,
.payment-card {
    border-radius: 16px;
    overflow: hidden;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
}

.card-header i {
    color: var(--p-primary-color);
}

.booking-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.provider-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.25rem 0;
}

.service-name {
    color: var(--p-surface-600);
    margin: 0;
}

.appointment-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--p-surface-700);
    font-size: 0.9375rem;
}

.info-row i {
    color: var(--p-surface-400);
    width: 18px;
}

.price-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.price-row {
    display: flex;
    justify-content: space-between;
    color: var(--p-surface-700);
}

.price-row.total {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
}

.payment-info {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.secure-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--p-green-50);
    color: var(--p-green-700);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
}

.payment-description {
    color: var(--p-surface-600);
    font-size: 0.9375rem;
    line-height: 1.6;
    margin: 0;
}

.card-brands {
    display: flex;
    gap: 0.75rem;
}

.brand {
    background: var(--p-surface-100);
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--p-surface-600);
}

.terms-section {
    background: var(--p-surface-50);
    padding: 1rem;
    border-radius: 8px;
}

.terms-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.terms-checkbox label {
    font-size: 0.9375rem;
    color: var(--p-surface-700);
    line-height: 1.5;
}

.link {
    color: var(--p-primary-color);
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}

.pay-button {
    width: 100%;
    height: 48px;
    font-size: 1rem;
    font-weight: 600;
}

.cancel-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: var(--p-surface-500);
    font-size: 0.8125rem;
    margin: 0;
}

@media (max-width: 640px) {
    .checkout-page {
        padding: 1rem;
    }

    .page-title {
        font-size: 1.5rem;
    }
}
</style>

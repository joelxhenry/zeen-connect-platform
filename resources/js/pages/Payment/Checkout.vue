<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PaymentLayout from '@/components/layout/PaymentLayout.vue';
import Button from 'primevue/button';
import Message from 'primevue/message';
import paymentRoutes from '@/routes/payment';
import providersite from '@/routes/providersite';
import { resolveUrl } from '@/utils/url';

interface Props {
    booking: {
        uuid: string;
        provider: { name: string; slug: string; };
        service: { name: string; duration_minutes: number; };
        formatted_date: string;
        formatted_time: string;
        service_price: number;
        total_amount: number;
        total_display: string;
        is_guest: boolean;
        client_name: string;
        client_email: string;
    };
    payment: {
        type: 'deposit' | 'full';
        amount: number;
        zeen_fee: number;
        gateway_fee: number;
        total_fees: number;
        convenience_fee: number;
        total_to_charge: number;
        amount_to_gateway: number;
        deposit_percentage: number;
        fee_payer: 'provider' | 'client';
        tier: string;
        tier_label: string;
        gateway_type: string;
    };
    isAuthenticated: boolean;
}

const props = defineProps<Props>();
const page = usePage();

const isProcessing = ref(false);
const errorMessage = ref<string | null>(null);

// Get validation errors from page props
const errors = computed(() => page.props.errors as Record<string, string>);

const submitPayment = () => {
    isProcessing.value = true;
    errorMessage.value = null;

    router.post(resolveUrl(paymentRoutes.process(props.booking.uuid).url), {
        payment_type: props.payment.type,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            isProcessing.value = false;
            errorMessage.value = errors.payment || 'Payment initialization failed. Please try again.';
        },
        onFinish: () => {
            // Only reset if we haven't redirected (which means there was an error)
            if (errorMessage.value) {
                isProcessing.value = false;
            }
        }
    });
};

const balanceAmount = computed(() => {
    if (props.payment.type === 'deposit') {
        return props.booking.total_amount - props.payment.amount;
    }
    return 0;
});

const confirmationUrl = computed(() => {
    return resolveUrl(providersite.book.confirmation({
        provider: props.booking.provider.slug,
        uuid: props.booking.uuid
    }).url);
});
</script>

<template>
    <PaymentLayout title="Checkout" step="checkout">
        <div class="checkout-content">
            <!-- Header -->
            <div class="checkout-header">
                <div class="header-icon">
                    <i class="pi pi-credit-card"></i>
                </div>
                <h1>Complete Payment</h1>
                <p>Secure checkout for your booking</p>
            </div>

            <!-- Booking Summary -->
            <div class="summary-card">
                <div class="card-header">
                    <span class="card-title">Booking Summary</span>
                </div>
                <div class="card-body">
                    <div class="summary-row">
                        <div class="row-icon service">
                            <i class="pi pi-briefcase"></i>
                        </div>
                        <div class="row-content">
                            <span class="row-label">{{ booking.service.name }}</span>
                            <span class="row-sublabel">{{ booking.service.duration_minutes }} min</span>
                        </div>
                    </div>
                    <div class="summary-row">
                        <div class="row-icon provider">
                            <i class="pi pi-user"></i>
                        </div>
                        <div class="row-content">
                            <span class="row-label">{{ booking.provider.name }}</span>
                            <span class="row-sublabel">Service Provider</span>
                        </div>
                    </div>
                    <div class="summary-row">
                        <div class="row-icon date">
                            <i class="pi pi-calendar"></i>
                        </div>
                        <div class="row-content">
                            <span class="row-label">{{ booking.formatted_date }}</span>
                            <span class="row-sublabel">{{ booking.formatted_time }}</span>
                        </div>
                    </div>
                    <div class="client-info">
                        <span class="info-label">Booked by</span>
                        <span class="info-name">{{ booking.client_name }}</span>
                        <span class="info-email">{{ booking.client_email }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="payment-card">
                <div class="card-header">
                    <span class="card-title">Payment Details</span>
                    <span v-if="payment.type === 'deposit'" class="deposit-badge">
                        Deposit
                    </span>
                </div>
                <div class="card-body">
                    <div class="price-row">
                        <span>Service Total</span>
                        <span class="price">${{ (booking.service_price ?? 0).toFixed(2) }}</span>
                    </div>

                    <div v-if="payment.fee_payer === 'client' && (payment.convenience_fee ?? 0) > 0"
                        class="price-row">
                        <span>Service Fee</span>
                        <span class="price">${{ (payment.convenience_fee ?? 0).toFixed(2) }}</span>
                    </div>

                    <template v-if="payment.type === 'deposit'">
                        <div class="divider"></div>
                        <div class="price-row highlight">
                            <span>Deposit ({{ payment.deposit_percentage ?? 0 }}%)</span>
                            <span class="price">${{ (payment.amount ?? 0).toFixed(2) }}</span>
                        </div>
                        <div class="price-row muted">
                            <span>Balance at Appointment</span>
                            <span class="price">${{ (balanceAmount ?? 0).toFixed(2) }}</span>
                        </div>
                    </template>

                    <div class="divider thick"></div>

                    <div class="total-row">
                        <span>{{ payment.type === 'deposit' ? 'Pay Now' : 'Total' }}</span>
                        <span class="total-amount">${{ (payment.total_to_charge ?? 0).toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <Message v-if="errorMessage" severity="error" :closable="true" @close="errorMessage = null"
                class="error-message">
                {{ errorMessage }}
            </Message>

            <!-- Payment Button -->
            <Button :label="isProcessing ? 'Processing...' : `Pay $${(payment.total_to_charge ?? 0).toFixed(2)}`"
                icon="pi pi-lock" class="pay-button" :loading="isProcessing" :disabled="isProcessing"
                @click="submitPayment" />

            <!-- Back Link -->
            <div class="back-link">
                <AppLink :href="confirmationUrl">
                    <i class="pi pi-arrow-left"></i>
                    Back to booking
                </AppLink>
            </div>
        </div>
    </PaymentLayout>
</template>

<style scoped>
.checkout-content {
    width: 100%;
}

/* Header */
.checkout-header {
    text-align: center;
    margin-bottom: 2rem;
}

.header-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, rgba(16, 107, 79, 0.15), rgba(16, 107, 79, 0.05));
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-icon i {
    font-size: 1.5rem;
    color: #106B4F;
}

.checkout-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0 0 0.5rem 0;
}

.checkout-header p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

/* Cards */
.summary-card,
.payment-card {
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

.deposit-badge {
    font-size: 0.75rem;
    font-weight: 500;
    color: #106B4F;
    background: rgba(16, 107, 79, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
}

.card-body {
    padding: 1.25rem;
}

/* Summary Rows */
.summary-row {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.75rem 0;
}

.summary-row:first-child {
    padding-top: 0;
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

.row-icon.provider {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
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

/* Client Info */
.client-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.info-label {
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #9ca3af;
    margin-bottom: 0.25rem;
}

.info-name {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #0D1F1B;
}

.info-email {
    font-size: 0.8125rem;
    color: #6b7280;
}

/* Price Rows */
.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: #4b5563;
}

.price-row .price {
    font-weight: 500;
    color: #0D1F1B;
}

.price-row.highlight {
    color: #106B4F;
}

.price-row.highlight .price {
    color: #106B4F;
    font-weight: 600;
}

.price-row.muted {
    color: #9ca3af;
}

.price-row.muted .price {
    color: #9ca3af;
}

.divider {
    height: 1px;
    background: #f3f4f6;
    margin: 0.5rem 0;
}

.divider.thick {
    background: #e5e7eb;
    margin: 0.75rem 0;
}

/* Total */
.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.5rem;
}

.total-row span:first-child {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0D1F1B;
}

.total-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0D1F1B;
}

/* Error Message */
.error-message {
    margin-bottom: 1rem;
    border-radius: 0.75rem;
}

/* Button */
.pay-button {
    width: 100%;
    height: 3.25rem;
    border-radius: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    background: linear-gradient(135deg, #106B4F 0%, #0d5a42 100%);
    border: none;
    margin-top: 0.5rem;
    box-shadow: 0 4px 12px rgba(16, 107, 79, 0.25);
    transition: all 0.2s ease;
}

.pay-button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(16, 107, 79, 0.3);
}

.pay-button:active:not(:disabled) {
    transform: translateY(0);
}

/* Back Link */
.back-link {
    text-align: center;
    margin-top: 1.5rem;
}

.back-link a {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
    text-decoration: none;
    transition: color 0.2s;
}

.back-link a:hover {
    color: #106B4F;
}

.back-link i {
    font-size: 0.75rem;
}
</style>

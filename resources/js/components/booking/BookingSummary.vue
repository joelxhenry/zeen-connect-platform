<script setup lang="ts">
import Button from 'primevue/button';

interface Slot {
    start_time: string;
    end_time: string;
    display: string;
}

interface Fees {
    deposit_amount: number;
    deposit_percentage: number;
    processing_fee: number;
    processing_fee_payer: string | null;
    requires_deposit: boolean;
}

interface Service {
    name: string;
    duration_display: string;
    price: number;
}

interface Props {
    service: Service | null;
    date: Date | null;
    slot: Slot | null;
    fees: Fees | null;
    canSubmit: boolean;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<{
    submit: [];
}>();

const formatSlotTime = (slot: Slot) => {
    const [hours, minutes] = slot.start_time.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};

const formattedServicePrice = (price: number) => `$${price.toFixed(2)}`;
const formattedDepositAmount = (amount: number) => `$${amount.toFixed(2)}`;

const formatDate = (date: Date) => {
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <div class="booking-summary">
        <div class="booking-summary__header">
            <h2>Booking Summary</h2>
        </div>
        <div class="booking-summary__content">
            <!-- Empty state -->
            <div v-if="!service" class="booking-summary__empty">
                <i class="pi pi-shopping-cart"></i>
                <p>Select a service to see summary</p>
            </div>

            <!-- Summary content -->
            <div v-else class="booking-summary__details">
                <!-- Selected Service -->
                <div class="booking-summary__service">
                    <h3>{{ service.name }}</h3>
                    <p>{{ service.duration_display }}</p>
                </div>

                <!-- Date & Time -->
                <div v-if="date && slot" class="booking-summary__datetime">
                    <i class="pi pi-calendar"></i>
                    <span>{{ formatDate(date) }}</span>
                    <span class="booking-summary__separator">at</span>
                    <span>{{ formatSlotTime(slot) }}</span>
                </div>

                <hr />

                <!-- Price Breakdown -->
                <div class="booking-summary__pricing">
                    <div class="booking-summary__line">
                        <span>Service Total</span>
                        <span class="booking-summary__amount">{{ formattedServicePrice(service.price) }}</span>
                    </div>

                    <template v-if="fees?.requires_deposit">
                        <div class="booking-summary__line booking-summary__line--highlight">
                            <span>Deposit ({{ fees.deposit_percentage }}%)</span>
                            <span class="booking-summary__amount">{{ formattedDepositAmount(fees.deposit_amount) }}</span>
                        </div>
                        <p class="booking-summary__note">
                            Pay {{ formattedDepositAmount(fees.deposit_amount) }} now, rest at appointment
                        </p>
                    </template>

                    <template v-if="fees && fees.processing_fee > 0 && fees.processing_fee_payer === 'client'">
                        <div class="booking-summary__line booking-summary__line--muted">
                            <span>Processing Fee</span>
                            <span>${{ fees.processing_fee.toFixed(2) }}</span>
                        </div>
                    </template>
                </div>

                <hr />

                <!-- Due Now -->
                <div class="booking-summary__total">
                    <span>Due Now</span>
                    <span class="booking-summary__total-amount">
                        {{ fees?.requires_deposit
                            ? formattedDepositAmount(fees.deposit_amount)
                            : formattedServicePrice(service.price)
                        }}
                    </span>
                </div>

                <!-- Submit Button -->
                <Button
                    :label="fees?.requires_deposit ? 'Continue to Payment' : 'Confirm Booking'"
                    icon="pi pi-arrow-right"
                    iconPos="right"
                    class="w-full !bg-[#106B4F] !border-[#106B4F]"
                    :disabled="!canSubmit"
                    :loading="loading"
                    @click="emit('submit')"
                />

                <p class="booking-summary__terms">
                    By booking, you agree to our Terms of Service
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.booking-summary {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    position: sticky;
    top: 5rem;
}

.booking-summary__header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
}

.booking-summary__header h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
}

.booking-summary__content {
    padding: 1.25rem;
}

.booking-summary__empty {
    text-align: center;
    padding: 2rem 0;
    color: #9ca3af;
}

.booking-summary__empty i {
    font-size: 1.875rem;
    margin-bottom: 0.5rem;
    display: block;
}

.booking-summary__empty p {
    margin: 0;
    font-size: 0.875rem;
}

.booking-summary__details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.booking-summary__service h3 {
    margin: 0;
    font-weight: 500;
    color: #0D1F1B;
}

.booking-summary__service p {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}

.booking-summary__datetime {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.booking-summary__datetime i {
    color: #106B4F;
}

.booking-summary__separator {
    color: #9ca3af;
}

.booking-summary__details hr {
    margin: 0;
    border: none;
    border-top: 1px solid #e5e7eb;
}

.booking-summary__pricing {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.booking-summary__line {
    display: flex;
    justify-content: space-between;
}

.booking-summary__line--highlight {
    color: #106B4F;
}

.booking-summary__line--muted {
    color: #6b7280;
}

.booking-summary__amount {
    font-weight: 500;
}

.booking-summary__note {
    margin: 0;
    font-size: 0.75rem;
    color: #6b7280;
}

.booking-summary__total {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.booking-summary__total span:first-child {
    font-weight: 600;
    color: #0D1F1B;
}

.booking-summary__total-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: #106B4F;
}

.booking-summary__terms {
    margin: 0;
    font-size: 0.75rem;
    text-align: center;
    color: #9ca3af;
}
</style>

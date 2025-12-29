<script setup lang="ts">
import { computed } from 'vue';
import Button from 'primevue/button';
import type { BookingFees } from '@/types/models/booking';

interface Slot {
    start_time: string;
    end_time: string;
    display: string;
}

interface Service {
    name: string;
    duration_display: string;
    price: number;
}

interface TeamMember {
    id: number;
    name: string;
    avatar?: string | null;
}

interface Props {
    service: Service | null;
    date: Date | null;
    slot: Slot | null;
    fees: BookingFees | null;
    teamMember?: TeamMember | null;
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

const formatCurrency = (amount: number) => `$${amount.toFixed(2)}`;

const formatDate = (date: Date) => {
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
};

// Check if service fee should be shown (when fee_payer is client and there's a convenience fee)
const showServiceFee = computed(() => {
    return props.fees && props.fees.convenience_fee > 0 && props.fees.fee_payer === 'client';
});

// Calculate due now amount
const dueNowAmount = computed(() => {
    if (!props.fees || !props.service) return 0;

    if (props.fees.requires_deposit) {
        // For deposits, add the full service fee upfront (fee is based on full service price)
        if (props.fees.fee_payer === 'client' && props.fees.convenience_fee > 0) {
            return props.fees.deposit_amount + props.fees.convenience_fee;
        }
        return props.fees.deposit_amount;
    }

    // Full payment - use client_pays which includes convenience fee if applicable
    return props.fees.client_pays;
});
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

                <!-- Team Member -->
                <div v-if="teamMember" class="booking-summary__team-member">
                    <i class="pi pi-user"></i>
                    <span>with {{ teamMember.name }}</span>
                </div>

                <hr />

                <!-- Price Breakdown -->
                <div class="booking-summary__pricing">
                    <div class="booking-summary__line">
                        <span>Service Total</span>
                        <span class="booking-summary__amount">{{ formatCurrency(service.price) }}</span>
                    </div>

                    <template v-if="fees?.requires_deposit">
                        <div class="booking-summary__line booking-summary__line--highlight">
                            <span>Deposit ({{ fees.deposit_percentage }}%)</span>
                            <span class="booking-summary__amount">{{ formatCurrency(fees.deposit_amount) }}</span>
                        </div>
                        <p class="booking-summary__note">
                            Pay deposit now, rest at appointment
                        </p>
                    </template>

                    <template v-if="showServiceFee">
                        <div class="booking-summary__line booking-summary__line--muted">
                            <span>Service Fee</span>
                            <span>{{ formatCurrency(fees!.convenience_fee) }}</span>
                        </div>
                    </template>
                </div>

                <hr />

                <!-- Due Now -->
                <div class="booking-summary__total">
                    <span>Due Now</span>
                    <span class="booking-summary__total-amount">
                        {{ formatCurrency(dueNowAmount) }}
                    </span>
                </div>

                <!-- Submit Button -->
                <Button
                    :label="fees?.requires_deposit ? 'Continue to Payment' : 'Confirm Booking'"
                    icon="pi pi-arrow-right"
                    iconPos="right"
                    class="w-full btn-primary"
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
    color: var(--provider-text);
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
    color: var(--provider-text);
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
    color: var(--provider-primary);
}

.booking-summary__separator {
    color: #9ca3af;
}

.booking-summary__team-member {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
}

.booking-summary__team-member i {
    color: var(--provider-primary);
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
    color: var(--provider-primary);
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
    color: var(--provider-text);
}

.booking-summary__total-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-primary);
}

:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.booking-summary__terms {
    margin: 0;
    font-size: 0.75rem;
    text-align: center;
    color: #9ca3af;
}
</style>

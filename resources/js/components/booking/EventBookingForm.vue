<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';

import StepCard from './StepCard.vue';

interface EventOccurrence {
    id: number;
    uuid: string;
    start_datetime: string;
    end_datetime: string;
    formatted_date: string;
    formatted_time: string;
    capacity: number;
    spots_remaining: number;
    is_sold_out: boolean;
    status: string;
}

interface EventData {
    id: number;
    uuid: string;
    slug: string;
    name: string;
    description?: string;
    price: number;
    price_display: string;
    duration_minutes: number;
    duration_display: string;
    capacity: number;
    event_type: 'one_time' | 'recurring';
    location_type: 'virtual' | 'in_person';
    location?: string;
    display_image?: string;
}

interface Props {
    event: EventData;
    occurrences: EventOccurrence[];
    preselectedOccurrence?: number | null;
    isAuthenticated: boolean;
    user: {
        name: string;
        email: string;
        phone?: string;
    } | null;
}

const props = defineProps<Props>();
const toast = useToast();

// Find preselected occurrence or first available
const getInitialOccurrence = () => {
    if (props.preselectedOccurrence) {
        return props.occurrences.find(o => o.id === props.preselectedOccurrence) || null;
    }
    return props.occurrences.find(o => !o.is_sold_out) || null;
};

const selectedOccurrence = ref<EventOccurrence | null>(getInitialOccurrence());
const spots = ref(1);

// Form for submission
const form = useForm({
    event_id: props.event.id,
    occurrence_id: selectedOccurrence.value?.id ?? null,
    spots: 1,
    notes: '',
    guest_email: '',
    guest_name: props.user?.name || '',
    guest_phone: props.user?.phone || '',
});

// Update form when occurrence changes
watch(selectedOccurrence, (occurrence) => {
    form.occurrence_id = occurrence?.id ?? null;
    // Reset spots if new occurrence has fewer spots
    if (occurrence && spots.value > occurrence.spots_remaining) {
        spots.value = Math.min(spots.value, occurrence.spots_remaining);
    }
});

watch(spots, (value) => {
    form.spots = value;
});

// Computed
const maxSpots = computed(() => {
    if (!selectedOccurrence.value) return 1;
    return Math.min(selectedOccurrence.value.spots_remaining, 10); // Cap at 10
});

const totalPrice = computed(() => {
    return props.event.price * spots.value;
});

const canSubmit = computed(() => {
    if (!selectedOccurrence.value || selectedOccurrence.value.is_sold_out) {
        return false;
    }
    if (!props.isAuthenticated) {
        return !!form.guest_email && !!form.guest_name && !!form.guest_phone;
    }
    return true;
});

const hasAvailableOccurrences = computed(() => {
    return props.occurrences.some(o => !o.is_sold_out);
});

// Methods
const selectOccurrence = (occurrence: EventOccurrence) => {
    if (!occurrence.is_sold_out) {
        selectedOccurrence.value = occurrence;
    }
};

const formatCurrency = (amount: number) => `$${amount.toFixed(2)}`;

const submit = () => {
    form.post('/book/event', {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Event registration created!',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to create registration. Please try again.',
                life: 3000,
            });
        },
    });
};
</script>

<template>
    <div class="event-booking-form">
        <div class="max-w-5xl mx-auto px-4 py-8">
            <!-- Page Header -->
            <div class="page-header mb-6">
                <h1>Register for Event</h1>
                <p>Complete your registration for {{ event.name }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form Section -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Event Info Card -->
                    <div class="event-info-card">
                        <div v-if="event.display_image" class="event-image">
                            <img :src="event.display_image" :alt="event.name" />
                        </div>
                        <div class="event-details">
                            <h2>{{ event.name }}</h2>
                            <div class="event-meta">
                                <span class="meta-item">
                                    <i class="pi pi-clock"></i>
                                    {{ event.duration_display }}
                                </span>
                                <span class="meta-item">
                                    <i :class="event.location_type === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker'"></i>
                                    {{ event.location_type === 'virtual' ? 'Virtual Event' : (event.location || 'In Person') }}
                                </span>
                            </div>
                            <p v-if="event.description" class="event-description">{{ event.description }}</p>
                        </div>
                    </div>

                    <!-- Step 1: Select Date -->
                    <StepCard :step="1" title="Select Date & Time" :active="true">
                        <div v-if="hasAvailableOccurrences" class="occurrence-grid">
                            <button
                                v-for="occurrence in occurrences"
                                :key="occurrence.id"
                                type="button"
                                class="occurrence-card"
                                :class="{
                                    selected: selectedOccurrence?.id === occurrence.id,
                                    'sold-out': occurrence.is_sold_out
                                }"
                                :disabled="occurrence.is_sold_out"
                                @click="selectOccurrence(occurrence)"
                            >
                                <div class="occurrence-date">{{ occurrence.formatted_date }}</div>
                                <div class="occurrence-time">{{ occurrence.formatted_time }}</div>
                                <div class="occurrence-status">
                                    <Tag v-if="occurrence.is_sold_out" value="Sold Out" severity="danger" />
                                    <Tag v-else-if="occurrence.spots_remaining <= 5" :value="`${occurrence.spots_remaining} spots left`" severity="warn" />
                                    <span v-else class="spots-text">{{ occurrence.spots_remaining }} spots available</span>
                                </div>
                            </button>
                        </div>
                        <div v-else class="no-dates-message">
                            <i class="pi pi-calendar-times"></i>
                            <p>No dates available for this event.</p>
                        </div>
                    </StepCard>

                    <!-- Step 2: Number of Spots -->
                    <StepCard :step="2" title="Number of Spots" :active="!!selectedOccurrence" :disabled="!selectedOccurrence">
                        <div class="spots-selector">
                            <label>How many spots do you need?</label>
                            <div class="spots-input-group">
                                <InputNumber
                                    v-model="spots"
                                    :min="1"
                                    :max="maxSpots"
                                    showButtons
                                    buttonLayout="horizontal"
                                    decrementButtonClass="p-button-secondary"
                                    incrementButtonClass="p-button-secondary"
                                    incrementButtonIcon="pi pi-plus"
                                    decrementButtonIcon="pi pi-minus"
                                />
                                <span class="spots-limit-text">
                                    Max {{ maxSpots }} spots
                                </span>
                            </div>
                            <p class="price-per-spot">
                                {{ event.price_display }} per person
                            </p>
                        </div>
                    </StepCard>

                    <!-- Step 3: Your Information -->
                    <StepCard :step="3" title="Your Information" :active="!!selectedOccurrence" :disabled="!selectedOccurrence">
                        <div class="guest-info-form">
                            <template v-if="isAuthenticated && user">
                                <div class="authenticated-info">
                                    <i class="pi pi-check-circle"></i>
                                    <div>
                                        <p class="user-name">{{ user.name }}</p>
                                        <p class="user-email">{{ user.email }}</p>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label for="guest_name">Full Name *</label>
                                        <InputText
                                            id="guest_name"
                                            v-model="form.guest_name"
                                            placeholder="Your full name"
                                            :class="{ 'p-invalid': form.errors.guest_name }"
                                        />
                                        <small v-if="form.errors.guest_name" class="p-error">{{ form.errors.guest_name }}</small>
                                    </div>
                                    <div class="form-field">
                                        <label for="guest_email">Email *</label>
                                        <InputText
                                            id="guest_email"
                                            v-model="form.guest_email"
                                            type="email"
                                            placeholder="your@email.com"
                                            :class="{ 'p-invalid': form.errors.guest_email }"
                                        />
                                        <small v-if="form.errors.guest_email" class="p-error">{{ form.errors.guest_email }}</small>
                                    </div>
                                    <div class="form-field">
                                        <label for="guest_phone">Phone *</label>
                                        <InputText
                                            id="guest_phone"
                                            v-model="form.guest_phone"
                                            placeholder="Your phone number"
                                            :class="{ 'p-invalid': form.errors.guest_phone }"
                                        />
                                        <small v-if="form.errors.guest_phone" class="p-error">{{ form.errors.guest_phone }}</small>
                                    </div>
                                </div>
                            </template>
                            <div class="form-field full-width">
                                <label for="notes">Notes (optional)</label>
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="Any special requests or notes for the host..."
                                />
                            </div>
                        </div>
                    </StepCard>
                </div>

                <!-- Booking Summary Sidebar -->
                <div class="lg:col-span-1">
                    <div class="event-summary">
                        <div class="summary-header">
                            <h2>Registration Summary</h2>
                        </div>
                        <div class="summary-content">
                            <!-- Empty state -->
                            <div v-if="!selectedOccurrence" class="summary-empty">
                                <i class="pi pi-calendar"></i>
                                <p>Select a date to see summary</p>
                            </div>

                            <!-- Summary content -->
                            <div v-else class="summary-details">
                                <div class="summary-event">
                                    <h3>{{ event.name }}</h3>
                                    <p>{{ event.duration_display }}</p>
                                </div>

                                <div class="summary-datetime">
                                    <i class="pi pi-calendar"></i>
                                    <div>
                                        <span class="date">{{ selectedOccurrence.formatted_date }}</span>
                                        <span class="time">{{ selectedOccurrence.formatted_time }}</span>
                                    </div>
                                </div>

                                <hr />

                                <!-- Price Breakdown -->
                                <div class="summary-pricing">
                                    <div class="pricing-line">
                                        <span>{{ event.price_display }} × {{ spots }} {{ spots === 1 ? 'spot' : 'spots' }}</span>
                                        <span class="amount">{{ formatCurrency(totalPrice) }}</span>
                                    </div>
                                </div>

                                <hr />

                                <!-- Total -->
                                <div class="summary-total">
                                    <span>Total</span>
                                    <span class="total-amount">{{ formatCurrency(totalPrice) }}</span>
                                </div>

                                <!-- Submit Button -->
                                <Button
                                    label="Complete Registration"
                                    icon="pi pi-arrow-right"
                                    iconPos="right"
                                    class="w-full btn-primary"
                                    :disabled="!canSubmit"
                                    :loading="form.processing"
                                    @click="submit"
                                />

                                <p class="summary-terms">
                                    By registering, you agree to our Terms of Service
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.event-booking-form {
    min-height: 100%;
}

.page-header {
    text-align: center;
}

.page-header h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--provider-text);
}

.page-header p {
    margin: 0;
    color: #6b7280;
}

/* Event Info Card */
.event-info-card {
    background: white;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.event-image {
    height: 180px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-details {
    padding: 1.25rem;
}

.event-details h2 {
    margin: 0 0 0.75rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text);
}

.event-meta {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 0.75rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.meta-item i {
    color: var(--provider-primary);
}

.event-description {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
}

/* Occurrence Grid */
.occurrence-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
}

.occurrence-card {
    padding: 1rem;
    background: #f9fafb;
    border: 2px solid transparent;
    border-radius: 0.5rem;
    text-align: left;
    cursor: pointer;
    transition: all 0.15s;
}

.occurrence-card:hover:not(:disabled) {
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border-color: var(--provider-primary-10, rgba(16, 107, 79, 0.1));
}

.occurrence-card.selected {
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border-color: var(--provider-primary);
}

.occurrence-card.sold-out {
    opacity: 0.6;
    cursor: not-allowed;
}

.occurrence-date {
    font-weight: 600;
    color: var(--provider-text);
    margin-bottom: 0.25rem;
}

.occurrence-time {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.occurrence-status .spots-text {
    font-size: 0.75rem;
    color: #10b981;
}

.no-dates-message {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.no-dates-message i {
    font-size: 2rem;
    color: #d1d5db;
    margin-bottom: 0.5rem;
}

/* Spots Selector */
.spots-selector {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.spots-selector label {
    font-weight: 500;
    color: var(--provider-text);
}

.spots-input-group {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.spots-limit-text {
    font-size: 0.875rem;
    color: #6b7280;
}

.price-per-spot {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}

/* Guest Info Form */
.guest-info-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.authenticated-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border-radius: 0.5rem;
}

.authenticated-info i {
    font-size: 1.5rem;
    color: var(--provider-primary);
}

.user-name {
    margin: 0;
    font-weight: 500;
    color: var(--provider-text);
}

.user-email {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-field.full-width {
    grid-column: 1 / -1;
}

.form-field label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text);
}

/* Event Summary */
.event-summary {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    position: sticky;
    top: 5rem;
}

.summary-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
}

.summary-header h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text);
}

.summary-content {
    padding: 1.25rem;
}

.summary-empty {
    text-align: center;
    padding: 2rem 0;
    color: #9ca3af;
}

.summary-empty i {
    font-size: 1.875rem;
    margin-bottom: 0.5rem;
    display: block;
}

.summary-empty p {
    margin: 0;
    font-size: 0.875rem;
}

.summary-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.summary-event h3 {
    margin: 0;
    font-weight: 500;
    color: var(--provider-text);
}

.summary-event p {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}

.summary-datetime {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border-radius: 0.5rem;
}

.summary-datetime i {
    color: var(--provider-primary);
}

.summary-datetime .date {
    display: block;
    font-weight: 500;
    color: var(--provider-text);
    font-size: 0.9375rem;
}

.summary-datetime .time {
    display: block;
    font-size: 0.8125rem;
    color: #6b7280;
}

.summary-details hr {
    margin: 0;
    border: none;
    border-top: 1px solid #e5e7eb;
}

.summary-pricing {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.pricing-line {
    display: flex;
    justify-content: space-between;
}

.pricing-line .amount {
    font-weight: 500;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-total span:first-child {
    font-weight: 600;
    color: var(--provider-text);
}

.total-amount {
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

.summary-terms {
    margin: 0;
    font-size: 0.75rem;
    text-align: center;
    color: #9ca3af;
}

@media (max-width: 768px) {
    .occurrence-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';

interface ServiceData {
    id: number;
    name: string;
    description?: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    category: {
        id: number;
        name: string;
        icon?: string;
    };
}

interface ProviderData {
    id: number;
    business_name: string;
    slug: string;
    avatar?: string;
    location?: string;
}

interface TimeSlot {
    start_time: string;
    end_time: string;
    display: string;
}

const props = defineProps<{
    provider: ProviderData;
    services: ServiceData[];
    availableDates: string[];
    preselectedService?: number;
}>();

const currentStep = ref(1);
const selectedService = ref<number | null>(props.preselectedService || null);
const selectedDate = ref<Date | null>(null);
const selectedSlot = ref<TimeSlot | null>(null);
const availableSlots = ref<TimeSlot[]>([]);
const loadingSlots = ref(false);
const slotsError = ref('');

const form = useForm({
    provider_id: props.provider.id,
    service_id: null as number | null,
    date: '',
    start_time: '',
    notes: '',
});

const selectedServiceData = computed(() => {
    return props.services.find(s => s.id === selectedService.value);
});

const disabledDates = computed(() => {
    const allDates: Date[] = [];
    const today = new Date();
    const endDate = new Date();
    endDate.setDate(endDate.getDate() + 30);

    const current = new Date(today);
    while (current <= endDate) {
        const dateStr = current.toISOString().split('T')[0];
        if (!props.availableDates.includes(dateStr)) {
            allDates.push(new Date(current));
        }
        current.setDate(current.getDate() + 1);
    }
    return allDates;
});

const minDate = computed(() => new Date());
const maxDate = computed(() => {
    const date = new Date();
    date.setDate(date.getDate() + 30);
    return date;
});

const fetchSlots = async () => {
    if (!selectedService.value || !selectedDate.value) return;

    loadingSlots.value = true;
    slotsError.value = '';
    availableSlots.value = [];
    selectedSlot.value = null;

    try {
        const dateStr = selectedDate.value.toISOString().split('T')[0];
        const response = await fetch(route('booking.slots', {
            provider_id: props.provider.id,
            service_id: selectedService.value,
            date: dateStr,
        }));
        const data = await response.json();
        availableSlots.value = data.slots;

        if (data.slots.length === 0) {
            slotsError.value = 'No available time slots for this date.';
        }
    } catch {
        slotsError.value = 'Failed to load time slots. Please try again.';
    } finally {
        loadingSlots.value = false;
    }
};

watch(selectedDate, () => {
    if (selectedDate.value) {
        fetchSlots();
    }
});

const selectService = (serviceId: number) => {
    selectedService.value = serviceId;
    currentStep.value = 2;
};

const selectSlot = (slot: TimeSlot) => {
    selectedSlot.value = slot;
    currentStep.value = 3;
};

const goBack = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        if (currentStep.value === 1) {
            selectedDate.value = null;
            selectedSlot.value = null;
        } else if (currentStep.value === 2) {
            selectedSlot.value = null;
        }
    }
};

const submit = () => {
    if (!selectedService.value || !selectedDate.value || !selectedSlot.value) return;

    form.service_id = selectedService.value;
    form.date = selectedDate.value.toISOString().split('T')[0];
    form.start_time = selectedSlot.value.start_time;

    form.post(route('booking.store'));
};

const formatPrice = (price: number): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(price);
};

const getInitials = (name: string): string => {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <PublicLayout title="Book Appointment">
        <div class="booking-page">
            <div class="booking-container">
                <!-- Provider Header -->
                <div class="provider-header">
                    <Link :href="route('provider.public', provider.slug)" class="back-link">
                        <i class="pi pi-arrow-left"></i>
                    </Link>
                    <div class="provider-info">
                        <img v-if="provider.avatar" :src="provider.avatar" :alt="provider.business_name" class="provider-avatar" />
                        <div v-else class="provider-avatar-placeholder">{{ getInitials(provider.business_name) }}</div>
                        <div>
                            <h1 class="provider-name">{{ provider.business_name }}</h1>
                            <p class="provider-location" v-if="provider.location">{{ provider.location }}</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="steps">
                    <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
                        <span class="step-number">1</span>
                        <span class="step-label">Service</span>
                    </div>
                    <div class="step-connector" :class="{ active: currentStep > 1 }"></div>
                    <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
                        <span class="step-number">2</span>
                        <span class="step-label">Date & Time</span>
                    </div>
                    <div class="step-connector" :class="{ active: currentStep > 2 }"></div>
                    <div class="step" :class="{ active: currentStep >= 3 }">
                        <span class="step-number">3</span>
                        <span class="step-label">Confirm</span>
                    </div>
                </div>

                <!-- Step 1: Select Service -->
                <div v-if="currentStep === 1" class="step-content">
                    <h2 class="step-title">Select a Service</h2>
                    <div class="services-list">
                        <div
                            v-for="service in services"
                            :key="service.id"
                            class="service-option"
                            :class="{ selected: selectedService === service.id }"
                            @click="selectService(service.id)"
                        >
                            <div class="service-details">
                                <div class="service-category">
                                    <i v-if="service.category.icon" :class="`pi ${service.category.icon}`"></i>
                                    {{ service.category.name }}
                                </div>
                                <h3 class="service-name">{{ service.name }}</h3>
                                <p v-if="service.description" class="service-description">{{ service.description }}</p>
                                <div class="service-meta">
                                    <span class="duration"><i class="pi pi-clock"></i> {{ service.duration_display }}</span>
                                </div>
                            </div>
                            <div class="service-price">{{ service.price_display }}</div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Date & Time -->
                <div v-if="currentStep === 2" class="step-content">
                    <Button label="Back" icon="pi pi-arrow-left" text @click="goBack" class="back-btn" />
                    <h2 class="step-title">Select Date & Time</h2>

                    <div class="datetime-grid">
                        <div class="date-section">
                            <h3 class="section-label">Choose a date</h3>
                            <Calendar
                                v-model="selectedDate"
                                inline
                                :minDate="minDate"
                                :maxDate="maxDate"
                                :disabledDates="disabledDates"
                                class="booking-calendar"
                            />
                        </div>

                        <div class="time-section">
                            <h3 class="section-label">Available times</h3>

                            <div v-if="!selectedDate" class="time-placeholder">
                                <i class="pi pi-calendar"></i>
                                <p>Select a date to see available times</p>
                            </div>

                            <div v-else-if="loadingSlots" class="time-loading">
                                <ProgressSpinner style="width: 40px; height: 40px" />
                                <p>Loading available times...</p>
                            </div>

                            <Message v-else-if="slotsError" severity="warn" :closable="false">
                                {{ slotsError }}
                            </Message>

                            <div v-else class="time-slots">
                                <button
                                    v-for="slot in availableSlots"
                                    :key="slot.start_time"
                                    type="button"
                                    class="time-slot"
                                    :class="{ selected: selectedSlot?.start_time === slot.start_time }"
                                    @click="selectSlot(slot)"
                                >
                                    {{ slot.display }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Confirm -->
                <div v-if="currentStep === 3" class="step-content">
                    <Button label="Back" icon="pi pi-arrow-left" text @click="goBack" class="back-btn" />
                    <h2 class="step-title">Confirm Booking</h2>

                    <div class="booking-summary">
                        <div class="summary-card">
                            <h3 class="summary-title">Booking Details</h3>

                            <div class="summary-row">
                                <span class="summary-label">Service</span>
                                <span class="summary-value">{{ selectedServiceData?.name }}</span>
                            </div>

                            <div class="summary-row">
                                <span class="summary-label">Date</span>
                                <span class="summary-value">
                                    {{ selectedDate?.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) }}
                                </span>
                            </div>

                            <div class="summary-row">
                                <span class="summary-label">Time</span>
                                <span class="summary-value">{{ selectedSlot?.display }}</span>
                            </div>

                            <div class="summary-row">
                                <span class="summary-label">Duration</span>
                                <span class="summary-value">{{ selectedServiceData?.duration_display }}</span>
                            </div>

                            <div class="summary-divider"></div>

                            <div class="summary-row total">
                                <span class="summary-label">Total</span>
                                <span class="summary-value">{{ selectedServiceData?.price_display }}</span>
                            </div>
                        </div>

                        <div class="notes-section">
                            <label class="notes-label">Add a note (optional)</label>
                            <Textarea
                                v-model="form.notes"
                                placeholder="Any special requests or information for the provider..."
                                rows="3"
                                class="w-full"
                            />
                        </div>

                        <Message v-if="form.errors.slot" severity="error" :closable="false">
                            {{ form.errors.slot }}
                        </Message>

                        <Button
                            label="Confirm Booking"
                            class="w-full confirm-btn"
                            :loading="form.processing"
                            @click="submit"
                        />

                        <p class="booking-note">
                            <i class="pi pi-info-circle"></i>
                            Your booking will be pending until the provider confirms it.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.booking-page {
    min-height: calc(100vh - 64px);
    background-color: var(--p-surface-50);
    padding: 1.5rem;
}

.booking-container {
    max-width: 800px;
    margin: 0 auto;
}

/* Provider Header */
.provider-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.back-link {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 10px;
    color: var(--p-surface-600);
    text-decoration: none;
    transition: all 0.2s;
}

.back-link:hover {
    border-color: var(--p-primary-color);
    color: var(--p-primary-color);
}

.provider-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.provider-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    object-fit: cover;
}

.provider-avatar-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--p-primary-color), var(--p-primary-400));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.provider-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0;
}

.provider-location {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
    margin: 0;
}

/* Progress Steps */
.steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
    padding: 1.25rem;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
}

.step {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--p-surface-400);
}

.step.active {
    color: var(--p-primary-color);
}

.step.completed {
    color: var(--p-green-500);
}

.step-number {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--p-surface-100);
    font-size: 0.8125rem;
    font-weight: 600;
}

.step.active .step-number {
    background: var(--p-primary-color);
    color: white;
}

.step.completed .step-number {
    background: var(--p-green-500);
    color: white;
}

.step-label {
    font-size: 0.875rem;
    font-weight: 500;
}

.step-connector {
    width: 40px;
    height: 2px;
    background: var(--p-surface-200);
}

.step-connector.active {
    background: var(--p-primary-color);
}

@media (max-width: 640px) {
    .step-label {
        display: none;
    }
}

/* Step Content */
.step-content {
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 16px;
    padding: 1.5rem;
}

.back-btn {
    margin-bottom: 1rem;
}

.step-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 1.25rem 0;
}

/* Services List */
.services-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.service-option {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem 1.25rem;
    border: 2px solid var(--p-surface-200);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.service-option:hover {
    border-color: var(--p-primary-200);
    background-color: var(--p-primary-50);
}

.service-option.selected {
    border-color: var(--p-primary-color);
    background-color: var(--p-primary-50);
}

.service-category {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: var(--p-surface-500);
    margin-bottom: 0.25rem;
}

.service-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.25rem 0;
}

.service-description {
    font-size: 0.8125rem;
    color: var(--p-surface-600);
    margin: 0 0 0.5rem 0;
}

.service-meta {
    display: flex;
    gap: 0.75rem;
}

.duration {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.service-price {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--p-surface-900);
    white-space: nowrap;
}

/* Date & Time */
.datetime-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .datetime-grid {
        grid-template-columns: 1fr;
    }
}

.section-label {
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--p-surface-700);
    margin: 0 0 0.75rem 0;
}

.booking-calendar {
    width: 100%;
}

.time-section {
    min-height: 300px;
}

.time-placeholder,
.time-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 200px;
    color: var(--p-surface-400);
    text-align: center;
}

.time-placeholder i,
.time-loading i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.time-slots {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.time-slot {
    padding: 0.75rem;
    border: 2px solid var(--p-surface-200);
    border-radius: 8px;
    background: white;
    font-size: 0.875rem;
    color: var(--p-surface-700);
    cursor: pointer;
    transition: all 0.2s;
}

.time-slot:hover {
    border-color: var(--p-primary-200);
    background-color: var(--p-primary-50);
}

.time-slot.selected {
    border-color: var(--p-primary-color);
    background-color: var(--p-primary-color);
    color: white;
}

/* Summary */
.booking-summary {
    max-width: 480px;
    margin: 0 auto;
}

.summary-card {
    background: var(--p-surface-50);
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}

.summary-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 1rem 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.summary-label {
    font-size: 0.875rem;
    color: var(--p-surface-500);
}

.summary-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-900);
    text-align: right;
}

.summary-divider {
    height: 1px;
    background: var(--p-surface-200);
    margin: 0.75rem 0;
}

.summary-row.total .summary-label,
.summary-row.total .summary-value {
    font-size: 1rem;
    font-weight: 600;
}

.notes-section {
    margin-bottom: 1.25rem;
}

.notes-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-700);
    margin-bottom: 0.5rem;
}

.confirm-btn {
    margin-bottom: 1rem;
}

.booking-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--p-surface-500);
    margin: 0;
}
</style>

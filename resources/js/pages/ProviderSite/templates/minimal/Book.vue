<script setup lang="ts">
import { computed } from 'vue';
import MinimalLayout from './components/MinimalLayout.vue';
import Calendar from 'primevue/calendar';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import type { BookingPageProps } from '@/types/providersite';
import { useProviderSiteBooking } from '@/composables/providersite';

const props = defineProps<BookingPageProps>();
const toast = useToast();

const {
    selectedService,
    selectedTeamMember,
    selectedDate,
    selectedSlot,
    availableSlots,
    loadingSlots,
    form,
    hasTeamMembers,
    currentFees,
    minDate,
    maxDate,
    disabledDates,
    canSubmit,
    submit,
} = useProviderSiteBooking(props);

const handleSubmit = () => {
    submit();
};
</script>

<template>
    <MinimalLayout title="Book">
        <div class="minimal-booking">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>Book Appointment</h1>
                </div>

                <div class="booking-grid">
                    <!-- Main Form -->
                    <div class="booking-form">
                        <!-- Service Selection -->
                        <div class="form-section">
                            <h2 class="section-title">Select Service</h2>
                            <div class="service-options">
                                <label v-for="service in services" :key="service.id" class="service-option"
                                    :class="{ 'is-selected': selectedService?.id === service.id }">
                                    <input type="radio" name="service" :value="service" v-model="selectedService"
                                        class="sr-only" />
                                    <div class="option-content">
                                        <span class="option-name">{{ service.name }}</span>
                                        <span class="option-duration">{{ service.duration_display }}</span>
                                    </div>
                                    <span class="option-price">{{ service.price_display }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Team Member Selection (if applicable) -->
                        <div v-if="hasTeamMembers" class="form-section">
                            <h2 class="section-title">Select Staff <span class="optional">(Optional)</span></h2>
                            <div class="team-options">
                                <label v-for="member in teamMembers" :key="member.id" class="team-option"
                                    :class="{ 'is-selected': selectedTeamMember?.id === member.id }">
                                    <input type="radio" name="team_member" :value="member" v-model="selectedTeamMember"
                                        class="sr-only" />
                                    <span class="team-name">{{ member.name }}</span>
                                </label>
                                <label class="team-option" :class="{ 'is-selected': selectedTeamMember === null }">
                                    <input type="radio" name="team_member" :value="null" v-model="selectedTeamMember"
                                        class="sr-only" />
                                    <span class="team-name">Any available</span>
                                </label>
                            </div>
                        </div>

                        <!-- Date & Time Selection -->
                        <div class="form-section" :class="{ 'is-disabled': !selectedService }">
                            <h2 class="section-title">Select Date & Time</h2>
                            <div class="datetime-grid">
                                <div class="calendar-wrapper">
                                    <Calendar v-model="selectedDate" inline :minDate="minDate" :maxDate="maxDate"
                                        :disabledDates="disabledDates" :disabled="!selectedService"
                                        class="minimal-calendar" />
                                </div>
                                <div class="slots-wrapper">
                                    <div v-if="!selectedDate" class="slots-empty">
                                        Select a date to see available times
                                    </div>
                                    <div v-else-if="loadingSlots" class="slots-loading">
                                        <i class="pi pi-spin pi-spinner"></i>
                                        Loading times...
                                    </div>
                                    <div v-else-if="availableSlots.length === 0" class="slots-empty">
                                        No available times on this date
                                    </div>
                                    <div v-else class="time-slots">
                                        <button v-for="slot in availableSlots" :key="slot.start_time" type="button"
                                            class="time-slot"
                                            :class="{ 'is-selected': selectedSlot?.start_time === slot.start_time }"
                                            @click="selectedSlot = slot">
                                            {{ slot.display }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="form-section" :class="{ 'is-disabled': !selectedSlot }">
                            <h2 class="section-title">Your Information</h2>

                            <div v-if="isAuthenticated && user" class="authenticated-user">
                                <div class="user-info">
                                    <span class="user-name">{{ user.name }}</span>
                                    <span class="user-email">{{ user.email }}</span>
                                </div>
                            </div>

                            <div v-else class="guest-form">
                                <div class="form-row">
                                    <div class="form-field">
                                        <label>Name *</label>
                                        <InputText v-model="form.guest_name" placeholder="Your name"
                                            :disabled="!selectedSlot"
                                            :class="{ 'p-invalid': form.errors.guest_name }" />
                                        <small v-if="form.errors.guest_name" class="p-error">
                                            {{ form.errors.guest_name }}
                                        </small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-field">
                                        <label>Email *</label>
                                        <InputText v-model="form.guest_email" type="email" placeholder="your@email.com"
                                            :disabled="!selectedSlot"
                                            :class="{ 'p-invalid': form.errors.guest_email }" />
                                        <small v-if="form.errors.guest_email" class="p-error">
                                            {{ form.errors.guest_email }}
                                        </small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-field">
                                        <label>Phone *</label>
                                        <InputText v-model="form.guest_phone" placeholder="Your phone number"
                                            :disabled="!selectedSlot"
                                            :class="{ 'p-invalid': form.errors.guest_phone }" />
                                        <small v-if="form.errors.guest_phone" class="p-error">
                                            {{ form.errors.guest_phone }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-field">
                                    <label>Notes <span class="optional">(Optional)</span></label>
                                    <Textarea v-model="form.notes" rows="2" placeholder="Any special requests?"
                                        :disabled="!selectedSlot" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Sidebar -->
                    <div class="booking-summary">
                        <div class="summary-card">
                            <h3>Summary</h3>

                            <div v-if="selectedService" class="summary-details">
                                <div class="summary-row">
                                    <span class="label">Service</span>
                                    <span class="value">{{ selectedService.name }}</span>
                                </div>
                                <div v-if="selectedTeamMember" class="summary-row">
                                    <span class="label">With</span>
                                    <span class="value">{{ selectedTeamMember.name }}</span>
                                </div>
                                <div v-if="selectedDate" class="summary-row">
                                    <span class="label">Date</span>
                                    <span class="value">{{ selectedDate.toLocaleDateString() }}</span>
                                </div>
                                <div v-if="selectedSlot" class="summary-row">
                                    <span class="label">Time</span>
                                    <span class="value">{{ selectedSlot.display }}</span>
                                </div>

                                <hr v-if="currentFees" class="summary-divider" />

                                <div v-if="currentFees" class="summary-pricing">
                                    <div class="summary-row">
                                        <span class="label">Service</span>
                                        <span class="value">${{ currentFees.service_price.toFixed(2) }}</span>
                                    </div>
                                    <div v-if="currentFees.fee_payer === 'client' && currentFees.convenience_fee > 0"
                                        class="summary-row">
                                        <span class="label">Service Fee</span>
                                        <span class="value">${{ currentFees.convenience_fee.toFixed(2) }}</span>
                                    </div>
                                    <div class="summary-row summary-total">
                                        <span class="label">Total</span>
                                        <span class="value">${{ currentFees.client_pays.toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="summary-empty">
                                Select a service to see summary
                            </div>

                            <Button label="Book Now" :disabled="!canSubmit" :loading="form.processing"
                                class="submit-btn" @click="handleSubmit" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MinimalLayout>
</template>

<style scoped>
.minimal-booking {
    min-height: 100%;
    background: #fafafa;
}

.page-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--provider-text);
}

.booking-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
    align-items: start;
}

.booking-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-section {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.form-section.is-disabled {
    opacity: 0.6;
    pointer-events: none;
}

.section-title {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text);
}

.optional {
    font-weight: 400;
    color: #9ca3af;
}

.service-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.service-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

.service-option:hover {
    border-color: #d1d5db;
}

.service-option.is-selected {
    border-color: var(--provider-primary);
    background: var(--provider-primary-05);
}

.option-content {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.option-name {
    font-weight: 500;
    color: var(--provider-text);
}

.option-duration {
    font-size: 0.75rem;
    color: #9ca3af;
}

.option-price {
    font-weight: 600;
    color: var(--provider-primary);
}

.team-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.team-option {
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.15s;
}

.team-option:hover {
    border-color: #d1d5db;
}

.team-option.is-selected {
    border-color: var(--provider-primary);
    background: var(--provider-primary-05);
    color: var(--provider-primary);
}

.team-name {
    font-size: 0.875rem;
}

.datetime-grid {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1.5rem;
}

.calendar-wrapper {
    max-width: 320px;
    overflow: hidden;
}

:deep(.minimal-calendar) {
    border: none;
}

:deep(.p-datepicker-day-selected) {
    background-color: var(--provider-primary) !important;
}

:deep(.p-datepicker-today > .p-datepicker-day:not(.p-datepicker-day-selected)) {
    border-color: var(--provider-primary) !important;
    color: var(--provider-primary) !important;
}

.slots-wrapper {
    min-height: 200px;
}

.slots-empty,
.slots-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #9ca3af;
    font-size: 0.875rem;
}

.slots-loading i {
    margin-right: 0.5rem;
}

.time-slots {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 0.5rem;
}

.time-slot {
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    background: white;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.15s;
}

.time-slot:hover {
    border-color: #d1d5db;
}

.time-slot.is-selected {
    border-color: var(--provider-primary);
    background: var(--provider-primary);
    color: white;
}

.authenticated-user {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.user-name {
    font-weight: 500;
    color: var(--provider-text);
}

.user-email {
    font-size: 0.875rem;
    color: #6b7280;
}

.guest-form {
    margin-bottom: 1rem;
}

.form-row {
    margin-bottom: 1rem;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-field label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text);
    margin-bottom: 0.375rem;
}

.form-field :deep(input),
.form-field :deep(textarea) {
    width: 100%;
}

.p-error {
    font-size: 0.75rem;
    color: #ef4444;
}

.booking-summary {
    position: sticky;
    top: 1rem;
}

.summary-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.summary-card h3 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text);
}

.summary-details {
    margin-bottom: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.375rem 0;
}

.summary-row .label {
    font-size: 0.875rem;
    color: #6b7280;
}

.summary-row .value {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text);
}

.summary-divider {
    margin: 0.75rem 0;
    border: none;
    border-top: 1px solid #f3f4f6;
}

.summary-total {
    padding-top: 0.75rem;
    border-top: 1px solid #f3f4f6;
}

.summary-total .label,
.summary-total .value {
    font-weight: 600;
    color: var(--provider-text);
}

.summary-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.875rem;
}

:deep(.submit-btn) {
    width: 100%;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.submit-btn:hover:not(:disabled)) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

@media (max-width: 768px) {
    .booking-grid {
        grid-template-columns: 1fr;
    }

    .datetime-grid {
        grid-template-columns: 1fr;
    }

    .calendar-wrapper {
        max-width: 100%;
    }

    .booking-summary {
        position: static;
    }
}
</style>

<script setup lang="ts">
import GrandHorizonLayout from './components/GrandHorizonLayout.vue';
import Calendar from 'primevue/calendar';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Avatar from 'primevue/avatar';
import type { BookingPageProps } from '@/types/providersite';
import { useProviderSiteBooking } from '@/composables/providersite';

const props = defineProps<BookingPageProps>();

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

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <GrandHorizonLayout title="Reserve">
        <div class="booking-page">
            <!-- Page Header -->
            <section class="page-header">
                <div class="header-content">
                    <h4 class="header-label">Reservations</h4>
                    <h1>Book Your Experience</h1>
                    <p>Select your preferred service and time</p>
                </div>
            </section>

            <!-- Booking Content -->
            <section class="booking-content">
                <div class="content-container">
                    <div class="booking-grid">
                        <!-- Left Column - Selection -->
                        <div class="booking-form">
                            <!-- Step 1: Service Selection -->
                            <div class="booking-step">
                                <div class="step-header">
                                    <span class="step-number">01</span>
                                    <h2>Select Experience</h2>
                                </div>
                                <div class="service-options">
                                    <label
                                        v-for="service in services"
                                        :key="service.id"
                                        class="service-option"
                                        :class="{ 'is-selected': selectedService?.id === service.id }"
                                    >
                                        <input
                                            type="radio"
                                            name="service"
                                            :value="service"
                                            v-model="selectedService"
                                            class="sr-only"
                                        />
                                        <div class="option-info">
                                            <span class="option-name">{{ service.name }}</span>
                                            <span class="option-duration">{{ service.duration_display }}</span>
                                        </div>
                                        <span class="option-price">{{ service.price_display }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Step 2: Team Member (if applicable) -->
                            <div v-if="hasTeamMembers" class="booking-step">
                                <div class="step-header">
                                    <span class="step-number">02</span>
                                    <h2>Choose Host</h2>
                                    <span class="step-optional">Optional</span>
                                </div>
                                <div class="team-options">
                                    <label
                                        class="team-option"
                                        :class="{ 'is-selected': selectedTeamMember === null }"
                                    >
                                        <input
                                            type="radio"
                                            name="team_member"
                                            :value="null"
                                            v-model="selectedTeamMember"
                                            class="sr-only"
                                        />
                                        <div class="any-avatar">
                                            <i class="pi pi-users"></i>
                                        </div>
                                        <span class="team-name">Any available</span>
                                    </label>
                                    <label
                                        v-for="member in teamMembers"
                                        :key="member.id"
                                        class="team-option"
                                        :class="{ 'is-selected': selectedTeamMember?.id === member.id }"
                                    >
                                        <input
                                            type="radio"
                                            name="team_member"
                                            :value="member"
                                            v-model="selectedTeamMember"
                                            class="sr-only"
                                        />
                                        <Avatar
                                            v-if="member.avatar"
                                            :image="member.avatar"
                                            shape="circle"
                                            class="team-avatar"
                                        />
                                        <Avatar
                                            v-else
                                            :label="getInitials(member.name)"
                                            shape="circle"
                                            class="team-avatar team-avatar--fallback"
                                        />
                                        <span class="team-name">{{ member.name }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Step 3: Date & Time -->
                            <div class="booking-step" :class="{ 'is-disabled': !selectedService }">
                                <div class="step-header">
                                    <span class="step-number">{{ hasTeamMembers ? '03' : '02' }}</span>
                                    <h2>Date & Time</h2>
                                </div>
                                <div class="datetime-section">
                                    <div class="calendar-wrapper">
                                        <Calendar
                                            v-model="selectedDate"
                                            inline
                                            :minDate="minDate"
                                            :maxDate="maxDate"
                                            :disabledDates="disabledDates"
                                            :disabled="!selectedService"
                                            class="booking-calendar"
                                        />
                                    </div>
                                    <div class="slots-wrapper">
                                        <div v-if="!selectedDate" class="slots-placeholder">
                                            <i class="pi pi-calendar"></i>
                                            <span>Select a date to view times</span>
                                        </div>
                                        <div v-else-if="loadingSlots" class="slots-placeholder">
                                            <i class="pi pi-spin pi-spinner"></i>
                                            <span>Loading times...</span>
                                        </div>
                                        <div v-else-if="availableSlots.length === 0" class="slots-placeholder">
                                            <i class="pi pi-times-circle"></i>
                                            <span>No availability</span>
                                        </div>
                                        <div v-else class="time-slots">
                                            <button
                                                v-for="slot in availableSlots"
                                                :key="slot.start_time"
                                                type="button"
                                                class="time-slot"
                                                :class="{ 'is-selected': selectedSlot?.start_time === slot.start_time }"
                                                @click="selectedSlot = slot"
                                            >
                                                {{ slot.display }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Your Information -->
                            <div class="booking-step" :class="{ 'is-disabled': !selectedSlot }">
                                <div class="step-header">
                                    <span class="step-number">{{ hasTeamMembers ? '04' : '03' }}</span>
                                    <h2>Guest Information</h2>
                                </div>

                                <div v-if="isAuthenticated && user" class="authenticated-user">
                                    <i class="pi pi-check-circle"></i>
                                    <div class="user-details">
                                        <span class="user-name">{{ user.name }}</span>
                                        <span class="user-email">{{ user.email }}</span>
                                    </div>
                                </div>

                                <div v-else class="guest-form">
                                    <div class="form-row">
                                        <label class="form-label">Full Name</label>
                                        <InputText
                                            v-model="form.guest_name"
                                            placeholder="Enter your name"
                                            :disabled="!selectedSlot"
                                            :class="{ 'p-invalid': form.errors.guest_name }"
                                            class="form-input"
                                        />
                                        <small v-if="form.errors.guest_name" class="form-error">
                                            {{ form.errors.guest_name }}
                                        </small>
                                    </div>
                                    <div class="form-row">
                                        <label class="form-label">Email Address</label>
                                        <InputText
                                            v-model="form.guest_email"
                                            type="email"
                                            placeholder="your@email.com"
                                            :disabled="!selectedSlot"
                                            :class="{ 'p-invalid': form.errors.guest_email }"
                                            class="form-input"
                                        />
                                        <small v-if="form.errors.guest_email" class="form-error">
                                            {{ form.errors.guest_email }}
                                        </small>
                                    </div>
                                    <div class="form-row">
                                        <label class="form-label">Phone Number</label>
                                        <InputText
                                            v-model="form.guest_phone"
                                            placeholder="Your phone number"
                                            :disabled="!selectedSlot"
                                            :class="{ 'p-invalid': form.errors.guest_phone }"
                                            class="form-input"
                                        />
                                        <small v-if="form.errors.guest_phone" class="form-error">
                                            {{ form.errors.guest_phone }}
                                        </small>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <label class="form-label">Special Requests <span class="optional-label">(optional)</span></label>
                                    <Textarea
                                        v-model="form.notes"
                                        rows="3"
                                        placeholder="Any special requirements or requests..."
                                        :disabled="!selectedSlot"
                                        class="form-input"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Summary -->
                        <div class="booking-sidebar">
                            <div class="booking-summary" :class="{ 'is-active': selectedService }">
                                <h3>Reservation Summary</h3>

                                <div v-if="selectedService" class="summary-content">
                                    <div class="summary-row">
                                        <span class="summary-label">Experience</span>
                                        <span class="summary-value">{{ selectedService.name }}</span>
                                    </div>
                                    <div v-if="selectedTeamMember" class="summary-row">
                                        <span class="summary-label">Host</span>
                                        <span class="summary-value">{{ selectedTeamMember.name }}</span>
                                    </div>
                                    <div v-if="selectedDate" class="summary-row">
                                        <span class="summary-label">Date</span>
                                        <span class="summary-value">{{ selectedDate.toLocaleDateString('en-US', { weekday: 'short', month: 'long', day: 'numeric' }) }}</span>
                                    </div>
                                    <div v-if="selectedSlot" class="summary-row">
                                        <span class="summary-label">Time</span>
                                        <span class="summary-value">{{ selectedSlot.display }}</span>
                                    </div>

                                    <div v-if="currentFees" class="summary-pricing">
                                        <div class="summary-row">
                                            <span class="summary-label">Service</span>
                                            <span class="summary-value">${{ currentFees.service_price.toFixed(2) }}</span>
                                        </div>
                                        <div
                                            v-if="currentFees.fee_payer === 'client' && currentFees.convenience_fee > 0"
                                            class="summary-row"
                                        >
                                            <span class="summary-label">Service fee</span>
                                            <span class="summary-value">${{ currentFees.convenience_fee.toFixed(2) }}</span>
                                        </div>
                                        <div class="summary-row summary-total">
                                            <span class="summary-label">Total</span>
                                            <span class="summary-value">${{ currentFees.client_pays.toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="summary-empty">
                                    <span>Select an experience to begin</span>
                                </div>

                                <Button
                                    label="Complete Reservation"
                                    :disabled="!canSubmit"
                                    :loading="form.processing"
                                    class="submit-btn"
                                    @click="handleSubmit"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </GrandHorizonLayout>
</template>

<style scoped>
.booking-page {
    min-height: 100vh;
}

/* Page Header */
.page-header {
    padding: 6rem 2rem;
    text-align: center;
    background: var(--provider-dark, #1a1a1a);
}

.header-content {
    max-width: 700px;
    margin: 0 auto;
}

.header-label {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--provider-primary, #c9a87c);
    margin-bottom: 1rem;
}

.page-header h1 {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 500;
    color: #ffffff;
    margin: 0 0 1rem 0;
}

.page-header p {
    margin: 0;
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.7);
}

/* Booking Content */
.booking-content {
    padding: 4rem 0 6rem;
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.booking-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 3rem;
    align-items: start;
}

/* Booking Form */
.booking-form {
    display: flex;
    flex-direction: column;
    gap: 2.5rem;
}

/* Booking Steps */
.booking-step {
    padding-bottom: 2.5rem;
    border-bottom: 1px solid var(--provider-border, #e5e0d8);
}

.booking-step:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.booking-step.is-disabled {
    opacity: 0.5;
    pointer-events: none;
}

.step-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.step-number {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: var(--provider-primary, #c9a87c);
}

.step-header h2 {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.375rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    margin: 0;
}

.step-optional {
    margin-left: auto;
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.05em;
    color: var(--provider-secondary, #6a6a6a);
}

/* Service Options */
.service-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.service-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: var(--provider-surface, #ffffff);
    border: 1px solid var(--provider-border, #e5e0d8);
    cursor: pointer;
    transition: all 0.2s;
}

.service-option:hover {
    border-color: var(--provider-dark, #1a1a1a);
}

.service-option.is-selected {
    border-color: var(--provider-dark, #1a1a1a);
    background: var(--provider-dark, #1a1a1a);
}

.service-option.is-selected .option-name,
.service-option.is-selected .option-price {
    color: #ffffff;
}

.service-option.is-selected .option-duration {
    color: rgba(255, 255, 255, 0.7);
}

.option-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.option-name {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.125rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
}

.option-duration {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--provider-secondary, #6a6a6a);
}

.option-price {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.375rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
}

/* Team Options */
.team-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 1rem;
}

.team-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem 1rem;
    background: var(--provider-surface, #ffffff);
    border: 1px solid var(--provider-border, #e5e0d8);
    cursor: pointer;
    transition: all 0.2s;
}

.team-option:hover {
    border-color: var(--provider-dark, #1a1a1a);
}

.team-option.is-selected {
    border-color: var(--provider-dark, #1a1a1a);
    background: var(--provider-dark, #1a1a1a);
}

.team-option.is-selected .team-name {
    color: #ffffff;
}

.any-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    background: var(--provider-background, #f8f6f3);
    border: 2px dashed var(--provider-border, #e5e0d8);
    border-radius: 50%;
}

.any-avatar i {
    font-size: 1.25rem;
    color: var(--provider-secondary, #6a6a6a);
}

.team-option.is-selected .any-avatar {
    background: var(--provider-primary, #c9a87c);
    border-style: solid;
    border-color: var(--provider-primary, #c9a87c);
}

.team-option.is-selected .any-avatar i {
    color: #ffffff;
}

:deep(.team-avatar) {
    width: 52px !important;
    height: 52px !important;
}

:deep(.team-avatar--fallback) {
    background: var(--provider-dark, #1a1a1a) !important;
    color: #ffffff !important;
}

.team-name {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.05em;
    color: var(--provider-text, #1a1a1a);
    text-align: center;
}

/* DateTime Section */
.datetime-section {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 2rem;
}

.calendar-wrapper {
    max-width: 320px;
    overflow: hidden;
}

:deep(.booking-calendar) {
    background: var(--provider-surface, #ffffff);
    border: 1px solid var(--provider-border, #e5e0d8);
    padding: 1.25rem;
}

:deep(.booking-calendar .p-datepicker-header) {
    background: transparent;
    border-bottom: 1px solid var(--provider-border, #e5e0d8);
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}

:deep(.booking-calendar .p-datepicker-title button) {
    font-family: var(--font-heading, 'Playfair Display', serif);
    color: var(--provider-text, #1a1a1a);
}

:deep(.booking-calendar .p-datepicker-calendar td > span) {
    border-radius: 0;
}

:deep(.booking-calendar .p-datepicker-day-selected) {
    background: var(--provider-dark, #1a1a1a) !important;
    color: #ffffff !important;
}

:deep(.booking-calendar .p-datepicker-today > .p-datepicker-day:not(.p-datepicker-day-selected)) {
    border-color: var(--provider-primary, #c9a87c) !important;
    color: var(--provider-primary, #c9a87c) !important;
}

.slots-wrapper {
    min-height: 220px;
}

.slots-placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    color: var(--provider-secondary, #6a6a6a);
    font-size: 0.875rem;
}

.slots-placeholder i {
    font-size: 2rem;
    color: var(--provider-border, #e5e0d8);
}

.time-slots {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(85px, 1fr));
    gap: 0.5rem;
}

.time-slot {
    padding: 0.875rem 0.5rem;
    background: var(--provider-surface, #ffffff);
    border: 1px solid var(--provider-border, #e5e0d8);
    cursor: pointer;
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    transition: all 0.2s;
}

.time-slot:hover {
    border-color: var(--provider-dark, #1a1a1a);
}

.time-slot.is-selected {
    background: var(--provider-dark, #1a1a1a);
    border-color: var(--provider-dark, #1a1a1a);
    color: #ffffff;
}

/* User Info */
.authenticated-user {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: rgba(124, 183, 152, 0.1);
    border: 1px solid rgba(124, 183, 152, 0.3);
    margin-bottom: 1.5rem;
}

.authenticated-user i {
    color: var(--provider-success, #7cb798);
    font-size: 1.5rem;
}

.user-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-name {
    font-weight: 600;
    color: var(--provider-text, #1a1a1a);
}

.user-email {
    font-size: 0.875rem;
    color: var(--provider-secondary, #6a6a6a);
}

/* Guest Form */
.guest-form {
    margin-bottom: 1.5rem;
}

.form-row {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--provider-text, #1a1a1a);
    margin-bottom: 0.5rem;
}

.optional-label {
    font-weight: 400;
    text-transform: none;
    letter-spacing: normal;
    color: var(--provider-secondary, #6a6a6a);
}

:deep(.form-input) {
    width: 100%;
    border-radius: 0;
    border-color: var(--provider-border, #e5e0d8);
}

:deep(.form-input:focus) {
    border-color: var(--provider-dark, #1a1a1a);
    box-shadow: none;
}

.form-error {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: var(--provider-danger, #d4726a);
}

/* Booking Sidebar */
.booking-sidebar {
    position: sticky;
    top: 120px;
}

/* Booking Summary */
.booking-summary {
    padding: 2rem;
    background: var(--provider-surface, #ffffff);
    border: 1px solid var(--provider-border, #e5e0d8);
}

.booking-summary h3 {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--provider-text, #1a1a1a);
    margin: 0 0 1.5rem 0;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--provider-border, #e5e0d8);
}

.summary-content {
    margin-bottom: 2rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.625rem 0;
}

.summary-label {
    font-size: 0.875rem;
    color: var(--provider-secondary, #6a6a6a);
}

.summary-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text, #1a1a1a);
    text-align: right;
}

.summary-pricing {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--provider-border, #e5e0d8);
}

.summary-total {
    padding-top: 1rem;
    margin-top: 0.75rem;
    border-top: 1px solid var(--provider-border, #e5e0d8);
}

.summary-total .summary-label {
    font-weight: 600;
    color: var(--provider-text, #1a1a1a);
}

.summary-total .summary-value {
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.5rem;
    font-weight: 500;
}

.summary-empty {
    padding: 3rem 1rem;
    text-align: center;
    color: var(--provider-secondary, #6a6a6a);
    font-size: 0.875rem;
}

:deep(.submit-btn) {
    width: 100%;
    font-family: var(--font-body, 'Montserrat', sans-serif) !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    background-color: var(--provider-dark, #1a1a1a) !important;
    border-color: var(--provider-dark, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 1rem 2rem;
}

:deep(.submit-btn:hover:not(:disabled)) {
    background-color: var(--provider-primary, #c9a87c) !important;
    border-color: var(--provider-primary, #c9a87c) !important;
}

:deep(.submit-btn:disabled) {
    opacity: 0.5;
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

/* Responsive */
@media (max-width: 1024px) {
    .booking-grid {
        grid-template-columns: 1fr;
    }

    .booking-sidebar {
        position: static;
    }
}

@media (max-width: 768px) {
    .page-header {
        padding: 4rem 1.5rem;
    }

    .content-container {
        padding: 0 1.5rem;
    }

    .booking-content {
        padding: 3rem 0 4rem;
    }

    .datetime-section {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .calendar-wrapper {
        max-width: 100%;
    }

    .team-options {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 480px) {
    .team-options {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script setup lang="ts">
import BoutiqueLayout from './components/BoutiqueLayout.vue';
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

/**
 * Format slot time to show only start time in 12-hour format.
 */
const formatSlotTime = (startTime: string): string => {
    const [hours, minutes] = startTime.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};
</script>

<template>
    <BoutiqueLayout title="Book">
        <div class="booking-page">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-container">
                    <h1>Book an Appointment</h1>
                    <p>Select a service and choose your preferred time</p>
                </div>
            </div>

            <!-- Booking Content - Single Column Flow -->
            <div class="booking-content">
                <div class="content-container">
                    <!-- Step 1: Service Selection -->
                    <div class="booking-step">
                        <div class="step-header">
                            <span class="step-number">1</span>
                            <h2>Choose a Service</h2>
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
                            <span class="step-number">2</span>
                            <h2>Choose a Team Member</h2>
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
                            <span class="step-number">{{ hasTeamMembers ? '3' : '2' }}</span>
                            <h2>Select Date & Time</h2>
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
                                    <span>Select a date to see available times</span>
                                </div>
                                <div v-else-if="loadingSlots" class="slots-placeholder">
                                    <i class="pi pi-spin pi-spinner"></i>
                                    <span>Loading available times...</span>
                                </div>
                                <div v-else-if="availableSlots.length === 0" class="slots-placeholder">
                                    <i class="pi pi-times-circle"></i>
                                    <span>No times available on this date</span>
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
                                        {{ formatSlotTime(slot.start_time) }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Your Information -->
                    <div class="booking-step" :class="{ 'is-disabled': !selectedSlot }">
                        <div class="step-header">
                            <span class="step-number">{{ hasTeamMembers ? '4' : '3' }}</span>
                            <h2>Your Information</h2>
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
                                <label class="form-label">Name</label>
                                <InputText
                                    v-model="form.guest_name"
                                    placeholder="Your full name"
                                    :disabled="!selectedSlot"
                                    :class="{ 'p-invalid': form.errors.guest_name }"
                                    class="form-input"
                                />
                                <small v-if="form.errors.guest_name" class="form-error">
                                    {{ form.errors.guest_name }}
                                </small>
                            </div>
                            <div class="form-row">
                                <label class="form-label">Email</label>
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
                                <label class="form-label">Phone</label>
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
                            <label class="form-label">Notes <span class="optional-label">(optional)</span></label>
                            <Textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Any special requests or notes..."
                                :disabled="!selectedSlot"
                                class="form-input"
                            />
                        </div>
                    </div>

                    <!-- Inline Summary & Submit -->
                    <div class="booking-summary" :class="{ 'is-active': selectedService }">
                        <h3>Booking Summary</h3>

                        <div v-if="selectedService" class="summary-content">
                            <div class="summary-row">
                                <span class="summary-label">Service</span>
                                <span class="summary-value">{{ selectedService.name }}</span>
                            </div>
                            <div v-if="selectedTeamMember" class="summary-row">
                                <span class="summary-label">With</span>
                                <span class="summary-value">{{ selectedTeamMember.name }}</span>
                            </div>
                            <div v-if="selectedDate" class="summary-row">
                                <span class="summary-label">Date</span>
                                <span class="summary-value">{{ selectedDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }) }}</span>
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
                            <span>Select a service to see your booking summary</span>
                        </div>

                        <Button
                            label="Confirm Booking"
                            :disabled="!canSubmit"
                            :loading="form.processing"
                            class="submit-btn"
                            @click="handleSubmit"
                        />
                    </div>
                </div>
            </div>
        </div>
    </BoutiqueLayout>
</template>

<style scoped>
.booking-page {
    min-height: 100vh;
}

/* Page Header */
.page-header {
    padding: 4rem 2rem;
    text-align: center;
    background: var(--provider-surface, #fff);
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

.header-container {
    max-width: 600px;
    margin: 0 auto;
}

.page-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: clamp(2rem, 4vw, 2.75rem);
    color: var(--provider-text, #3d3d3d);
}

.page-header p {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-secondary, #8a8a8a);
}

/* Booking Content */
.booking-content {
    padding: 3rem 0 5rem;
}

.content-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Booking Steps */
.booking-step {
    margin-bottom: 2.5rem;
    padding-bottom: 2.5rem;
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

.booking-step.is-disabled {
    opacity: 0.5;
    pointer-events: none;
}

.step-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.step-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: var(--provider-primary, #8b7355);
    color: #fff;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 50%;
}

.step-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: var(--provider-text, #3d3d3d);
}

.step-optional {
    margin-left: auto;
    font-size: 0.8125rem;
    color: var(--provider-secondary, #8a8a8a);
    font-style: italic;
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
    padding: 1.25rem;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #ebe8e4);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}

.service-option:hover {
    border-color: var(--provider-primary, #8b7355);
}

.service-option.is-selected {
    border-color: var(--provider-primary, #8b7355);
    background: var(--provider-primary-05, rgba(139, 115, 85, 0.05));
}

.option-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.option-name {
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 1.125rem;
    font-weight: 500;
    color: var(--provider-text, #3d3d3d);
}

.option-duration {
    font-size: 0.8125rem;
    color: var(--provider-secondary, #8a8a8a);
}

.option-price {
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 1.25rem;
    font-weight: 500;
    color: var(--provider-text, #3d3d3d);
}

/* Team Options */
.team-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}

.team-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 1rem;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #ebe8e4);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}

.team-option:hover {
    border-color: var(--provider-primary, #8b7355);
}

.team-option.is-selected {
    border-color: var(--provider-primary, #8b7355);
    background: var(--provider-primary-05, rgba(139, 115, 85, 0.05));
}

.any-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: var(--provider-background, #fdfcfb);
    border: 2px dashed var(--provider-border, #ebe8e4);
    border-radius: 50%;
}

.any-avatar i {
    font-size: 1.25rem;
    color: var(--provider-secondary, #8a8a8a);
}

.team-option.is-selected .any-avatar {
    background: var(--provider-primary, #8b7355);
    border-style: solid;
    border-color: var(--provider-primary, #8b7355);
}

.team-option.is-selected .any-avatar i {
    color: #fff;
}

:deep(.team-avatar) {
    width: 48px !important;
    height: 48px !important;
}

:deep(.team-avatar--fallback) {
    background: var(--provider-primary, #8b7355) !important;
    color: #fff !important;
}

.team-name {
    font-size: 0.8125rem;
    font-weight: 400;
    color: var(--provider-text, #3d3d3d);
    text-align: center;
}

/* DateTime Section */
.datetime-section {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 2rem;
}

.calendar-wrapper {
    max-width: 300px;
    overflow: hidden;
}

:deep(.booking-calendar .p-datepicker-header) {
    background: transparent;
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

:deep(.booking-calendar .p-datepicker-title button) {
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    color: var(--provider-text, #3d3d3d);
}

:deep(.booking-calendar .p-datepicker-calendar td > span) {
    border-radius: 50%;
}

:deep(.booking-calendar .p-datepicker-day-selected) {
    background: var(--provider-primary, #8b7355) !important;
    color: #fff !important;
}

:deep(.booking-calendar .p-datepicker-today > .p-datepicker-day:not(.p-datepicker-day-selected)) {
    border-color: var(--provider-primary, #8b7355) !important;
    color: var(--provider-primary, #8b7355) !important;
}

.slots-wrapper {
    min-height: 200px;
}

.slots-placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    color: var(--provider-secondary, #8a8a8a);
    font-size: 0.9375rem;
}

.slots-placeholder i {
    font-size: 2rem;
    color: var(--provider-border, #ebe8e4);
}

.time-slots {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 0.5rem;
}

.time-slot {
    padding: 0.75rem;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #ebe8e4);
    border-radius: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
    color: var(--provider-text, #3d3d3d);
    transition: all 0.2s;
}

.time-slot:hover {
    border-color: var(--provider-primary, #8b7355);
}

.time-slot.is-selected {
    background: var(--provider-primary, #8b7355);
    border-color: var(--provider-primary, #8b7355);
    color: #fff;
}

/* User Info */
.authenticated-user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--provider-primary-05, rgba(139, 115, 85, 0.05));
    border-radius: 0.75rem;
    margin-bottom: 1rem;
}

.authenticated-user i {
    color: var(--provider-success, #7cb798);
    font-size: 1.25rem;
}

.user-details {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.user-name {
    font-weight: 500;
    color: var(--provider-text, #3d3d3d);
}

.user-email {
    font-size: 0.875rem;
    color: var(--provider-secondary, #8a8a8a);
}

/* Guest Form */
.guest-form {
    margin-bottom: 1rem;
}

.form-row {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 400;
    color: var(--provider-text, #3d3d3d);
    margin-bottom: 0.5rem;
}

.optional-label {
    font-weight: 300;
    font-style: italic;
    color: var(--provider-secondary, #8a8a8a);
}

:deep(.form-input) {
    width: 100%;
    border-radius: 0.5rem;
}

:deep(.form-input:focus) {
    border-color: var(--provider-primary, #8b7355);
    box-shadow: 0 0 0 2px var(--provider-primary-10, rgba(139, 115, 85, 0.1));
}

.form-error {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--provider-danger, #d4726a);
}

/* Booking Summary */
.booking-summary {
    padding: 1.5rem;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #ebe8e4);
    border-radius: 1rem;
}

.booking-summary h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    color: var(--provider-text, #3d3d3d);
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--provider-border, #ebe8e4);
}

.summary-content {
    margin-bottom: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.summary-label {
    font-size: 0.875rem;
    color: var(--provider-secondary, #8a8a8a);
}

.summary-value {
    font-size: 0.875rem;
    color: var(--provider-text, #3d3d3d);
}

.summary-pricing {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--provider-border, #ebe8e4);
}

.summary-total {
    padding-top: 0.75rem;
    margin-top: 0.5rem;
    border-top: 1px solid var(--provider-border, #ebe8e4);
}

.summary-total .summary-label,
.summary-total .summary-value {
    font-weight: 500;
}

.summary-total .summary-value {
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 1.25rem;
    color: var(--provider-text, #3d3d3d);
}

.summary-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--provider-secondary, #8a8a8a);
    font-size: 0.9375rem;
}

:deep(.submit-btn) {
    width: 100%;
    font-family: var(--font-body, 'Nunito Sans', sans-serif) !important;
    font-weight: 500;
    font-size: 1rem;
    letter-spacing: 0.02em;
    background-color: var(--provider-primary, #8b7355) !important;
    border-color: var(--provider-primary, #8b7355) !important;
    border-radius: 2rem !important;
    padding: 0.875rem 2rem;
}

:deep(.submit-btn:hover:not(:disabled)) {
    background-color: var(--provider-primary-hover, #6d5a43) !important;
    border-color: var(--provider-primary-hover, #6d5a43) !important;
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
@media (max-width: 768px) {
    .page-header {
        padding: 3rem 1rem;
    }

    .content-container {
        padding: 0 1rem;
    }

    .booking-content {
        padding: 2rem 0 4rem;
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

<script setup lang="ts">
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
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
    <ProviderSiteLayout title="Book">
        <div class="booking-page">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>{{ provider.business_name }}</h1>
                    <p>Select a service and pick your preferred time</p>
                </div>

                <div class="booking-grid">
                    <!-- Main Form -->
                    <div class="booking-form">
                        <!-- Service Selection -->
                        <div class="form-section">
                            <h2 class="section-title">
                                <span class="step-number">1</span>
                                Select Service
                            </h2>
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
                                    <div class="option-content">
                                        <span class="option-name">{{ service.name }}</span>
                                        <span class="option-duration">
                                            <i class="pi pi-clock"></i>
                                            {{ service.duration_display }}
                                        </span>
                                    </div>
                                    <span class="option-price">{{ service.price_display }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Team Member Selection (if applicable) -->
                        <div v-if="hasTeamMembers" class="form-section">
                            <h2 class="section-title">
                                <span class="step-number">2</span>
                                Choose Team Member
                                <span class="optional">(Optional)</span>
                            </h2>
                            <div class="team-options">
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
                                <label
                                    class="team-option team-option--any"
                                    :class="{ 'is-selected': selectedTeamMember === null }"
                                >
                                    <input
                                        type="radio"
                                        name="team_member"
                                        :value="null"
                                        v-model="selectedTeamMember"
                                        class="sr-only"
                                    />
                                    <div class="any-icon">
                                        <i class="pi pi-users"></i>
                                    </div>
                                    <span class="team-name">Any available</span>
                                </label>
                            </div>
                        </div>

                        <!-- Date & Time Selection -->
                        <div
                            class="form-section"
                            :class="{ 'is-disabled': !selectedService }"
                        >
                            <h2 class="section-title">
                                <span class="step-number">{{ hasTeamMembers ? '3' : '2' }}</span>
                                Select Date & Time
                            </h2>
                            <div class="datetime-grid">
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
                                    <div v-if="!selectedDate" class="slots-empty">
                                        <i class="pi pi-calendar"></i>
                                        <span>Select a date to see available times</span>
                                    </div>
                                    <div v-else-if="loadingSlots" class="slots-loading">
                                        <i class="pi pi-spin pi-spinner"></i>
                                        <span>Loading times...</span>
                                    </div>
                                    <div v-else-if="availableSlots.length === 0" class="slots-empty">
                                        <i class="pi pi-times-circle"></i>
                                        <span>No available times on this date</span>
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

                        <!-- Contact Information -->
                        <div
                            class="form-section"
                            :class="{ 'is-disabled': !selectedSlot }"
                        >
                            <h2 class="section-title">
                                <span class="step-number">{{ hasTeamMembers ? '4' : '3' }}</span>
                                Your Information
                            </h2>

                            <div v-if="isAuthenticated && user" class="authenticated-user">
                                <div class="user-badge">
                                    <i class="pi pi-check-circle"></i>
                                    <span>Logged in</span>
                                </div>
                                <div class="user-info">
                                    <span class="user-name">{{ user.name }}</span>
                                    <span class="user-email">{{ user.email }}</span>
                                </div>
                            </div>

                            <div v-else class="guest-form">
                                <div class="form-row">
                                    <div class="form-field">
                                        <label>Name *</label>
                                        <InputText
                                            v-model="form.guest_name"
                                            placeholder="Your name"
                                            :disabled="!selectedSlot"
                                            :class="{ 'p-invalid': form.errors.guest_name }"
                                        />
                                        <small v-if="form.errors.guest_name" class="p-error">
                                            {{ form.errors.guest_name }}
                                        </small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-field">
                                        <label>Email *</label>
                                        <InputText
                                            v-model="form.guest_email"
                                            type="email"
                                            placeholder="your@email.com"
                                            :disabled="!selectedSlot"
                                            :class="{ 'p-invalid': form.errors.guest_email }"
                                        />
                                        <small v-if="form.errors.guest_email" class="p-error">
                                            {{ form.errors.guest_email }}
                                        </small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-field">
                                        <label>Phone *</label>
                                        <InputText
                                            v-model="form.guest_phone"
                                            placeholder="Your phone number"
                                            :disabled="!selectedSlot"
                                            :class="{ 'p-invalid': form.errors.guest_phone }"
                                        />
                                        <small v-if="form.errors.guest_phone" class="p-error">
                                            {{ form.errors.guest_phone }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-field">
                                    <label>Notes <span class="optional">(Optional)</span></label>
                                    <Textarea
                                        v-model="form.notes"
                                        rows="2"
                                        placeholder="Any special requests?"
                                        :disabled="!selectedSlot"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Sidebar -->
                    <div class="booking-summary">
                        <div class="summary-card">
                            <h3>Booking Summary</h3>

                            <div v-if="selectedService" class="summary-details">
                                <div class="summary-row">
                                    <span class="label">Service</span>
                                    <span class="value">{{ selectedService.name }}</span>
                                </div>
                                <div v-if="selectedTeamMember" class="summary-row">
                                    <span class="label">Team Member</span>
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
                                    <div
                                        v-if="currentFees.fee_payer === 'client' && currentFees.convenience_fee > 0"
                                        class="summary-row"
                                    >
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
                                <i class="pi pi-shopping-cart"></i>
                                <span>Select a service to see summary</span>
                            </div>

                            <Button
                                label="Confirm Booking"
                                icon="pi pi-check"
                                :disabled="!canSubmit"
                                :loading="form.processing"
                                class="submit-btn"
                                @click="handleSubmit"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ProviderSiteLayout>
</template>

<style scoped>
.booking-page {
    min-height: 100vh;
    background: var(--provider-background, #f9fafb);
}

.page-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
}

.page-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.page-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-text, #1f2937);
}

.page-header p {
    margin: 0;
    color: var(--provider-text-muted, #6b7280);
}

.booking-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 2rem;
    align-items: start;
}

.booking-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-section {
    background: var(--provider-surface, #fff);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-section.is-disabled {
    opacity: 0.5;
    pointer-events: none;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 1.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.step-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: var(--provider-primary, #3b82f6);
    color: #fff;
    border-radius: 50%;
    font-size: 0.875rem;
    font-weight: 700;
}

.optional {
    font-weight: 400;
    color: var(--provider-text-muted, #6b7280);
    margin-left: auto;
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
    padding: 1rem 1.25rem;
    background: var(--provider-background, #f9fafb);
    border: 2px solid transparent;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
}

.service-option:hover {
    border-color: var(--provider-border, #d1d5db);
}

.service-option.is-selected {
    border-color: var(--provider-primary, #3b82f6);
    background: var(--provider-primary-10, rgba(59, 130, 246, 0.1));
}

.option-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.option-name {
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.option-duration {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.75rem;
    color: var(--provider-text-muted, #6b7280);
}

.option-duration i {
    color: var(--provider-primary, #3b82f6);
}

.option-price {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--provider-primary, #3b82f6);
}

.team-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
}

.team-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: var(--provider-background, #f9fafb);
    border: 2px solid transparent;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
}

.team-option:hover {
    border-color: var(--provider-border, #d1d5db);
}

.team-option.is-selected {
    border-color: var(--provider-primary, #3b82f6);
    background: var(--provider-primary-10, rgba(59, 130, 246, 0.1));
}

:deep(.team-avatar) {
    width: 56px !important;
    height: 56px !important;
    border: 2px solid var(--provider-primary, #3b82f6);
}

:deep(.team-avatar--fallback) {
    background: var(--provider-primary, #3b82f6) !important;
    color: #fff !important;
}

.any-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--provider-surface, #fff);
    border: 2px dashed var(--provider-border, #d1d5db);
}

.any-icon i {
    font-size: 1.25rem;
    color: var(--provider-text-muted, #6b7280);
}

.team-option.is-selected .any-icon {
    border-color: var(--provider-primary, #3b82f6);
    background: var(--provider-primary, #3b82f6);
}

.team-option.is-selected .any-icon i {
    color: #fff;
}

.team-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text, #1f2937);
    text-align: center;
}

.datetime-grid {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1.5rem;
}

.calendar-wrapper {
    max-width: 320px;
}

/* Calendar Styling */
:deep(.booking-calendar) {
    background: var(--provider-background, #f9fafb);
    border: none;
    border-radius: 0.5rem;
    padding: 0.5rem;
}

:deep(.booking-calendar .p-datepicker-header) {
    background: transparent;
    border-bottom: 1px solid var(--provider-border, #e5e7eb);
    color: var(--provider-text, #1f2937);
}

:deep(.booking-calendar .p-datepicker-title button) {
    color: var(--provider-text, #1f2937);
}

:deep(.booking-calendar .p-datepicker-prev,
.booking-calendar .p-datepicker-next) {
    color: var(--provider-text-muted, #6b7280);
}

:deep(.booking-calendar .p-datepicker-calendar th) {
    color: var(--provider-text-muted, #6b7280);
}

:deep(.booking-calendar .p-datepicker-calendar td > span) {
    color: var(--provider-text, #1f2937);
}

:deep(.booking-calendar .p-datepicker-calendar td > span.p-disabled) {
    color: var(--provider-border, #d1d5db);
}

:deep(.booking-calendar .p-datepicker-day-selected) {
    background: var(--provider-primary, #3b82f6) !important;
    color: #fff !important;
}

:deep(.booking-calendar .p-datepicker-today > .p-datepicker-day:not(.p-datepicker-day-selected)) {
    border-color: var(--provider-primary, #3b82f6) !important;
    color: var(--provider-primary, #3b82f6) !important;
    background: transparent !important;
}

.slots-wrapper {
    min-height: 200px;
}

.slots-empty,
.slots-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    gap: 0.75rem;
    color: var(--provider-text-muted, #6b7280);
    font-size: 0.875rem;
}

.slots-empty i,
.slots-loading i {
    font-size: 2rem;
}

.time-slots {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 0.5rem;
}

.time-slot {
    padding: 0.75rem 0.5rem;
    background: var(--provider-background, #f9fafb);
    border: 2px solid transparent;
    border-radius: 0.375rem;
    color: var(--provider-text, #1f2937);
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.time-slot:hover {
    border-color: var(--provider-border, #d1d5db);
}

.time-slot.is-selected {
    background: var(--provider-primary, #3b82f6);
    border-color: var(--provider-primary, #3b82f6);
    color: #fff;
}

.authenticated-user {
    padding: 1rem;
    background: var(--provider-background, #f9fafb);
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.user-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #22c55e;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.user-name {
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.user-email {
    font-size: 0.875rem;
    color: var(--provider-text-muted, #6b7280);
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
    color: var(--provider-text, #1f2937);
    margin-bottom: 0.5rem;
}

.form-field :deep(input),
.form-field :deep(textarea) {
    width: 100%;
}

.form-field :deep(input:focus),
.form-field :deep(textarea:focus) {
    border-color: var(--provider-primary, #3b82f6);
    box-shadow: 0 0 0 1px var(--provider-primary, #3b82f6);
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
    background: var(--provider-surface, #fff);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.summary-card h3 {
    margin: 0 0 1.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text, #1f2937);
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--provider-primary, #3b82f6);
}

.summary-details {
    margin-bottom: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.summary-row .label {
    font-size: 0.875rem;
    color: var(--provider-text-muted, #6b7280);
}

.summary-row .value {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text, #1f2937);
}

.summary-divider {
    margin: 0.75rem 0;
    border: none;
    border-top: 1px solid var(--provider-border, #e5e7eb);
}

.summary-total {
    padding-top: 0.75rem;
    border-top: 1px solid var(--provider-border, #e5e7eb);
}

.summary-total .label,
.summary-total .value {
    font-weight: 700;
    font-size: 1rem;
}

.summary-total .value {
    color: var(--provider-primary, #3b82f6);
}

.summary-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 2rem 1rem;
    text-align: center;
    color: var(--provider-text-muted, #6b7280);
    font-size: 0.875rem;
}

.summary-empty i {
    font-size: 2rem;
}

:deep(.submit-btn) {
    width: 100%;
    background: var(--provider-primary, #3b82f6) !important;
    border-color: var(--provider-primary, #3b82f6) !important;
}

:deep(.submit-btn:hover:not(:disabled)) {
    background: var(--provider-primary-hover, #2563eb) !important;
    border-color: var(--provider-primary-hover, #2563eb) !important;
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

@media (max-width: 900px) {
    .booking-grid {
        grid-template-columns: 1fr;
    }

    .booking-summary {
        position: static;
        order: -1;
    }
}

@media (max-width: 640px) {
    .datetime-grid {
        grid-template-columns: 1fr;
    }

    .calendar-wrapper {
        max-width: 100%;
    }

    .team-options {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

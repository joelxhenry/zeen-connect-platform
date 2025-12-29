<script setup lang="ts">
import DefaultLayout from './components/DefaultLayout.vue';
import Calendar from 'primevue/calendar';
import type { BookingPageProps } from '@/types/providersite';
import { useProviderSiteBooking } from '@/composables/providersite';

// Booking components
import StepCard from '@/components/booking/StepCard.vue';
import ServiceSelector from '@/components/booking/ServiceSelector.vue';
import TeamMemberSelector from '@/components/booking/TeamMemberSelector.vue';
import TimeSlotPicker from '@/components/booking/TimeSlotPicker.vue';
import GuestInfoForm from '@/components/booking/GuestInfoForm.vue';
import BookingSummary from '@/components/booking/BookingSummary.vue';

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
</script>

<template>
    <DefaultLayout title="Book Appointment" show-banner>
        <div class="booking-page">
            <div class="max-w-5xl mx-auto px-4 py-8">
                <!-- Page Header -->
                <div class="page-header mb-6">
                    <h1>Book an Appointment</h1>
                    <p>Choose a service, date, and time that works for you</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Form Section -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Step 1: Select Service -->
                        <StepCard :step="1" title="Select Service" :active="true">
                            <ServiceSelector v-model="selectedService" :services="services" />
                        </StepCard>

                        <!-- Step 1.5: Select Team Member (optional, only shown if team members exist) -->
                        <StepCard v-if="hasTeamMembers" :step="2" title="Select Team Member" subtitle="(Optional)"
                            :active="!!selectedService" :disabled="!selectedService">
                            <TeamMemberSelector v-model="selectedTeamMember" :team-members="teamMembers" />
                        </StepCard>

                        <!-- Step 2/3: Select Date & Time -->
                        <StepCard :step="hasTeamMembers ? 3 : 2" title="Select Date & Time" :active="!!selectedService"
                            :disabled="!selectedService">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Calendar -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                                    <Calendar v-model="selectedDate" inline :minDate="minDate" :maxDate="maxDate"
                                        :disabledDates="disabledDates" class="w-full" />
                                </div>

                                <!-- Time Slots -->
                                <div>
                                    <TimeSlotPicker v-model="selectedSlot" :slots="availableSlots" :loading="loadingSlots"
                                        :has-date="!!selectedDate" />
                                </div>
                            </div>
                        </StepCard>

                        <!-- Step 3/4: Your Information -->
                        <StepCard :step="hasTeamMembers ? 4 : 3" title="Your Information" :active="!!selectedSlot"
                            :disabled="!selectedSlot">
                            <GuestInfoForm :isAuthenticated="isAuthenticated" :user="user" :guestName="form.guest_name"
                                :guestEmail="form.guest_email" :guestPhone="form.guest_phone" :notes="form.notes"
                                :errors="form.errors" @update:guestName="form.guest_name = $event"
                                @update:guestEmail="form.guest_email = $event"
                                @update:guestPhone="form.guest_phone = $event" @update:notes="form.notes = $event" />
                        </StepCard>
                    </div>

                    <!-- Booking Summary Sidebar -->
                    <div class="lg:col-span-1">
                        <BookingSummary :service="selectedService" :date="selectedDate" :slot="selectedSlot"
                            :fees="currentFees" :team-member="selectedTeamMember" :canSubmit="canSubmit"
                            :loading="form.processing" @submit="submit" />
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

<style scoped>
.booking-page {
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

/* Calendar branding overrides */
:deep(.p-datepicker-day-selected) {
    background-color: var(--provider-primary) !important;
    color: white !important;
}

:deep(.p-datepicker-day-selected:hover) {
    background-color: var(--provider-primary-hover) !important;
}

:deep(.p-datepicker-day:not(.p-datepicker-day-selected):not(.p-disabled):hover) {
    background-color: var(--provider-primary-10) !important;
}

:deep(.p-datepicker-today > .p-datepicker-day:not(.p-datepicker-day-selected)) {
    border-color: var(--provider-primary) !important;
    color: var(--provider-primary) !important;
}

:deep(.p-datepicker-header button:hover) {
    color: var(--provider-primary) !important;
    background-color: var(--provider-primary-10) !important;
}

:deep(.p-datepicker-title button:hover) {
    color: var(--provider-primary) !important;
}
</style>

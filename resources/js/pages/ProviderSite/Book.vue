<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
import Calendar from 'primevue/calendar';
import { useToast } from 'primevue/usetoast';
import { useApi } from '@/composables/useApi';
import { ApiError } from '@/types/api';
import type { ServiceForBooking } from '@/types/models/service';

// Booking components
import StepCard from '@/components/booking/StepCard.vue';
import ServiceSelector from '@/components/booking/ServiceSelector.vue';
import TimeSlotPicker from '@/components/booking/TimeSlotPicker.vue';
import GuestInfoForm from '@/components/booking/GuestInfoForm.vue';
import BookingSummary from '@/components/booking/BookingSummary.vue';

interface Props {
    provider: {
        id: number;
        business_name: string;
        slug: string;
        avatar?: string;
        location?: string;
        tier: string;
        tier_label: string;
    };
    services: ServiceForBooking[];
    availableDates: string[];
    preselectedService: number | null;
    isAuthenticated: boolean;
    user: {
        name: string;
        email: string;
        phone?: string;
    } | null;
}


interface Slot {
    start_time: string;
    end_time: string;
    display: string;
}

interface SlotsResponse {
    slots: Slot[];
}

const props = defineProps<Props>();
const toast = useToast();
const api = useApi({ showErrorToast: false });

// Form state
const preselectedServiceData = props.preselectedService
    ? props.services.find(s => s.id === props.preselectedService) || null
    : null;

const selectedService = ref<ServiceForBooking | null>(preselectedServiceData);
const selectedDate = ref<Date | null>(null);
const selectedSlot = ref<Slot | null>(null);
const availableSlots = ref<Slot[]>([]);
const loadingSlots = ref(false);

// Form for submission
const form = useForm({
    provider_id: props.provider.id,
    service_id: preselectedServiceData?.id ?? null,
    date: '',
    start_time: '',
    notes: '',
    guest_email: '',
    guest_name: props.user?.name || '',
    guest_phone: props.user?.phone || '',
});

// Computed
const currentFees = computed(() => selectedService.value?.fees || null);

const availableDatesSet = computed(() => new Set(props.availableDates));

const minDate = computed(() => new Date());
const maxDate = computed(() => {
    const date = new Date();
    date.setDate(date.getDate() + 30);
    return date;
});

// Format date to YYYY-MM-DD in local timezone (avoids UTC shift issues)
const formatDateLocal = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Generate disabled dates for the calendar (dates NOT in availableDates)
const disabledDates = computed(() => {
    const disabled: Date[] = [];
    const current = new Date(minDate.value);
    const end = new Date(maxDate.value);

    while (current <= end) {
        const dateStr = formatDateLocal(current);
        if (!availableDatesSet.value.has(dateStr)) {
            disabled.push(new Date(current));
        }
        current.setDate(current.getDate() + 1);
    }

    return disabled;
});

const canSubmit = computed(() => {
    if (!selectedService.value || !selectedDate.value || !selectedSlot.value) {
        return false;
    }
    if (!props.isAuthenticated) {
        return !!form.guest_email && !!form.guest_name && !!form.guest_phone;
    }
    return true;
});

// Watchers
watch(selectedService, (service) => {
    form.service_id = service?.id ?? null;
    selectedDate.value = null;
    selectedSlot.value = null;
    availableSlots.value = [];
});

watch(selectedDate, async (date) => {
    if (!date || !selectedService.value) {
        availableSlots.value = [];
        selectedSlot.value = null;
        return;
    }

    form.date = formatDateLocal(date);
    await fetchSlots();
});

watch(selectedSlot, (slot) => {
    form.start_time = slot?.start_time || '';
});

// Methods
const fetchSlots = async () => {
    if (!selectedDate.value || !selectedService.value) return;

    loadingSlots.value = true;
    try {
        // Use relative path since we're already on the provider's subdomain
        const result = await api.get<SlotsResponse>('/book/slots', {
            params: {
                service_id: selectedService.value.id,
                date: form.date,
            },
        });
        availableSlots.value = result.slots || [];
        selectedSlot.value = null;
    } catch (e) {
        const message = e instanceof ApiError ? e.message : 'Failed to load available time slots';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 3000,
        });
    } finally {
        loadingSlots.value = false;
    }
};

const submit = () => {
    // Use relative path since we're already on the provider's subdomain
    form.post('/book', {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Booking created successfully!',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to create booking. Please try again.',
                life: 3000,
            });
        },
    });
};

</script>

<template>
    <ProviderSiteLayout title="Book Appointment" show-banner>
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

                        <!-- Step 2: Select Date & Time -->
                        <StepCard :step="2" title="Select Date & Time" :active="!!selectedService"
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
                                    <TimeSlotPicker v-model="selectedSlot" :slots="availableSlots"
                                        :loading="loadingSlots" :has-date="!!selectedDate" />
                                </div>
                            </div>
                        </StepCard>

                        <!-- Step 3: Your Information -->
                        <StepCard :step="3" title="Your Information" :active="!!selectedSlot" :disabled="!selectedSlot">
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
                            :fees="currentFees" :canSubmit="canSubmit" :loading="form.processing" @submit="submit" />
                    </div>
                </div>
            </div>
        </div>
    </ProviderSiteLayout>
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
    color: #0D1F1B;
}

.page-header p {
    margin: 0;
    color: #6b7280;
}
</style>

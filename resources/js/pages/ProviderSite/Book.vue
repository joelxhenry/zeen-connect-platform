<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
import Calendar from 'primevue/calendar';
import { useToast } from 'primevue/usetoast';
import ProviderSiteBookingController from '@/actions/App/Http/Controllers/ProviderSite/ProviderSiteBookingController';

// Booking components
import StepCard from '@/components/booking/StepCard.vue';
import ServiceSelector from '@/components/booking/ServiceSelector.vue';
import TimeSlotPicker from '@/components/booking/TimeSlotPicker.vue';
import GuestInfoForm from '@/components/booking/GuestInfoForm.vue';
import BookingSummary from '@/components/booking/BookingSummary.vue';

interface Service {
    id: number;
    name: string;
    description: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    category: {
        id: number;
        name: string;
        icon: string;
    } | null;
    fees: {
        tier: string;
        tier_label: string;
        service_price: number;
        deposit_amount: number;
        deposit_percentage: number;
        platform_fee: number;
        platform_fee_rate: number;
        processing_fee: number;
        processing_fee_payer: string | null;
        requires_deposit: boolean;
    };
}

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
    services: Service[];
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

const props = defineProps<Props>();
const toast = useToast();

// Form state
const selectedService = ref<Service | null>(
    props.preselectedService
        ? props.services.find(s => s.id === props.preselectedService) || null
        : null
);
const selectedDate = ref<Date | null>(null);
const selectedSlot = ref<Slot | null>(null);
const availableSlots = ref<Slot[]>([]);
const loadingSlots = ref(false);

// Form for submission
const form = useForm({
    provider_id: props.provider.id,
    service_id: null as number | null,
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

const isDateDisabled = (date: Date) => {
    const dateStr = date.toISOString().split('T')[0];
    return !availableDatesSet.value.has(dateStr);
};

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
    form.service_id = service?.id || null;
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

    form.date = date.toISOString().split('T')[0];
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
        // Use provider site route for slots
        const response = await fetch(ProviderSiteBookingController.getSlots({ provider: props.provider.slug }).url + '?' + new URLSearchParams({
            service_id: selectedService.value.id.toString(),
            date: form.date,
        }));
        const data = await response.json();
        availableSlots.value = data.slots || [];
        selectedSlot.value = null;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load available time slots',
            life: 3000,
        });
    } finally {
        loadingSlots.value = false;
    }
};

const submit = () => {
    // Use provider site route for booking
    form.post(ProviderSiteBookingController.store({ provider: props.provider.slug }).url, {
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
                                        :disabledDates="[]"
                                        :dateTemplate="({ date }) => isDateDisabled(new Date(date.year, date.month, date.day)) ? 'disabled' : null"
                                        class="w-full" />
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

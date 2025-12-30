<script setup lang="ts">
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import { router } from '@inertiajs/vue3';

interface Service {
    id: number;
    name: string;
    price_display: string;
}

const props = defineProps<{
    services: Service[];
    bookingUrl: string;
}>();

const selectedService = ref<number | null>(null);
const selectedDate = ref<Date | null>(null);

const serviceOptions = computed(() => {
    return props.services.map(s => ({
        label: s.name,
        value: s.id,
    }));
});

const minDate = computed(() => new Date());

const handleBookNow = () => {
    let url = props.bookingUrl;
    const params: string[] = [];

    if (selectedService.value) {
        params.push(`service=${selectedService.value}`);
    }
    if (selectedDate.value) {
        const dateStr = selectedDate.value.toISOString().split('T')[0];
        params.push(`date=${dateStr}`);
    }

    if (params.length > 0) {
        url += (url.includes('?') ? '&' : '?') + params.join('&');
    }

    router.visit(url);
};
</script>

<template>
    <div class="booking-bar">
        <div class="booking-bar__content">
            <!-- Service Selector -->
            <div class="booking-field">
                <label class="booking-label">Experience</label>
                <Select
                    v-model="selectedService"
                    :options="serviceOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select service"
                    class="booking-select"
                />
            </div>

            <!-- Date Picker -->
            <div class="booking-field">
                <label class="booking-label">Date</label>
                <DatePicker
                    v-model="selectedDate"
                    :minDate="minDate"
                    dateFormat="M d, yy"
                    placeholder="Choose date"
                    class="booking-datepicker"
                />
            </div>

            <!-- Book Button -->
            <Button
                label="Book Now"
                icon="pi pi-arrow-right"
                iconPos="right"
                class="booking-btn"
                @click="handleBookNow"
            />
        </div>
    </div>
</template>

<style scoped>
.booking-bar {
    position: absolute;
    bottom: 3rem;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 4rem);
    max-width: 900px;
    background: rgba(255, 255, 255, 0.97);
    backdrop-filter: blur(12px);
    border-radius: 0;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    z-index: 10;
}

.booking-bar__content {
    display: flex;
    align-items: flex-end;
    gap: 1.5rem;
    padding: 1.5rem 2rem;
}

.booking-field {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.booking-label {
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--provider-secondary, #6a6a6a);
}

:deep(.booking-select),
:deep(.booking-datepicker) {
    width: 100%;
}

:deep(.booking-select .p-select),
:deep(.booking-datepicker .p-datepicker-input) {
    width: 100%;
    border: none;
    border-bottom: 2px solid var(--provider-border, #e5e0d8);
    border-radius: 0;
    background: transparent;
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.9375rem;
    font-weight: 500;
    padding: 0.75rem 0;
    color: var(--provider-text, #1a1a1a);
}

:deep(.booking-select .p-select:focus),
:deep(.booking-datepicker .p-datepicker-input:focus) {
    border-bottom-color: var(--provider-primary, #c9a87c);
    box-shadow: none;
}

:deep(.booking-select .p-select-dropdown) {
    background: transparent;
    color: var(--provider-secondary, #6a6a6a);
}

:deep(.booking-btn) {
    font-family: var(--font-body, 'Montserrat', sans-serif) !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    background-color: var(--provider-dark, #1a1a1a) !important;
    border-color: var(--provider-dark, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 1rem 2rem;
    white-space: nowrap;
    flex-shrink: 0;
}

:deep(.booking-btn:hover) {
    background-color: var(--provider-primary, #c9a87c) !important;
    border-color: var(--provider-primary, #c9a87c) !important;
}

/* Responsive */
@media (max-width: 768px) {
    .booking-bar {
        position: relative;
        bottom: auto;
        left: auto;
        transform: none;
        width: 100%;
        max-width: none;
        margin: 0;
        border-radius: 0;
        box-shadow: none;
        border-top: 1px solid var(--provider-border, #e5e0d8);
    }

    .booking-bar__content {
        flex-direction: column;
        align-items: stretch;
        gap: 1.25rem;
        padding: 1.5rem;
    }

    .booking-field {
        width: 100%;
    }

    :deep(.booking-btn) {
        width: 100%;
        justify-content: center;
    }
}
</style>

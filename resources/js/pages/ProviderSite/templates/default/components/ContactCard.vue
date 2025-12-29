<script setup lang="ts">
import Button from 'primevue/button';

interface BusinessHour {
    day: string;
    day_of_week?: number;
    start_time: string;
    end_time: string;
    is_closed?: boolean;
}

interface Props {
    hours?: BusinessHour[];
    email?: string;
    phone?: string;
    address?: string;
    bookingUrl?: string;
    variant?: 'light' | 'dark';
    showHours?: boolean;
    showContact?: boolean;
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'light',
    showHours: true,
    showContact: true,
    bookingUrl: '/book',
    title: 'Working Hours',
});

const formatTime = (time: string): string => {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}${minutes !== '00' ? ':' + minutes : ''}${ampm}`;
};

const getDayLabel = (day: string): string => {
    const labels: Record<string, string> = {
        monday: 'Monday',
        tuesday: 'Tuesday',
        wednesday: 'Wednesday',
        thursday: 'Thursday',
        friday: 'Friday',
        saturday: 'Saturday',
        sunday: 'Sunday',
    };
    return labels[day.toLowerCase()] || day;
};

// Group consecutive days with same hours
const groupedHours = (() => {
    if (!props.hours || props.hours.length === 0) return [];

    // Simple display - just show each day's hours
    return props.hours.map(h => ({
        day: getDayLabel(h.day),
        hours: h.is_closed ? 'Closed' : `${formatTime(h.start_time)} - ${formatTime(h.end_time)}`,
        isClosed: h.is_closed,
    }));
})();
</script>

<template>
    <div class="contact-card" :class="`contact-card--${variant}`">
        <!-- Working Hours -->
        <div v-if="showHours && hours && hours.length > 0" class="contact-card__section">
            <h3 class="contact-card__title">{{ title }}</h3>
            <div class="contact-card__hours">
                <div
                    v-for="(item, index) in groupedHours"
                    :key="index"
                    class="hours-row"
                    :class="{ 'hours-row--closed': item.isClosed }"
                >
                    <span class="hours-row__day">{{ item.day }}</span>
                    <span class="hours-row__time">{{ item.hours }}</span>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div v-if="showContact && (email || phone || address)" class="contact-card__section">
            <h3 class="contact-card__title">Book Your Visit</h3>
            <div class="contact-card__info">
                <div v-if="email" class="contact-item">
                    <i class="pi pi-envelope"></i>
                    <a :href="`mailto:${email}`">{{ email }}</a>
                </div>
                <div v-if="phone" class="contact-item">
                    <i class="pi pi-phone"></i>
                    <a :href="`tel:${phone}`">{{ phone }}</a>
                </div>
                <div v-if="address" class="contact-item contact-item--address">
                    <i class="pi pi-map-marker"></i>
                    <span>{{ address }}</span>
                </div>
            </div>
        </div>

        <!-- Book Now Button -->
        <AppLink :href="bookingUrl" class="contact-card__cta">
            <Button label="Book Now" class="w-full cta-button" />
        </AppLink>
    </div>
</template>

<style scoped>
.contact-card {
    padding: 1.5rem;
    border-radius: 0.75rem;
}

.contact-card--light {
    background: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.contact-card--dark {
    background: var(--barber-dark-secondary, #3D3024);
}

.contact-card__section {
    margin-bottom: 1.5rem;
}

.contact-card__section:last-of-type {
    margin-bottom: 1.5rem;
}

.contact-card__title {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid;
}

.contact-card--light .contact-card__title {
    color: var(--provider-text, #1f2937);
    border-color: #e5e7eb;
}

.contact-card--dark .contact-card__title {
    color: #fff;
    border-color: var(--provider-primary, #C4A962);
}

.contact-card__hours {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.hours-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
}

.contact-card--light .hours-row {
    color: #4b5563;
}

.contact-card--dark .hours-row {
    color: var(--barber-text-muted, #A69F94);
}

.hours-row--closed .hours-row__time {
    color: #ef4444;
}

.hours-row__day {
    font-weight: 500;
}

.contact-card--light .hours-row__day {
    color: var(--provider-text, #1f2937);
}

.contact-card--dark .hours-row__day {
    color: #fff;
}

.contact-card__info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
}

.contact-item--address {
    align-items: flex-start;
}

.contact-item i {
    font-size: 1rem;
    color: var(--provider-primary, #C4A962);
}

.contact-card--light .contact-item a,
.contact-card--light .contact-item span {
    color: #4b5563;
}

.contact-card--dark .contact-item a,
.contact-card--dark .contact-item span {
    color: var(--barber-text-muted, #A69F94);
}

.contact-item a {
    text-decoration: none;
}

.contact-item a:hover {
    color: var(--provider-primary, #C4A962);
}

.contact-card__cta {
    display: block;
    text-decoration: none;
}

:deep(.cta-button) {
    background: var(--provider-primary, #C4A962) !important;
    border-color: var(--provider-primary, #C4A962) !important;
    color: var(--barber-dark, #2A2018) !important;
    font-weight: 600;
}

:deep(.cta-button:hover) {
    background: var(--provider-primary-hover, #B39952) !important;
    border-color: var(--provider-primary-hover, #B39952) !important;
}
</style>

<script setup lang="ts">
import { computed } from 'vue';
import Button from 'primevue/button';

interface Availability {
    day: string;
    day_of_week: number;
    start_time: string;
    end_time: string;
}

interface Props {
    hours: Availability[];
    bookingUrl?: string;
}

const props = defineProps<Props>();

const today = new Date().getDay();

const formatTime = (time: string): string => {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const formattedHour = hour % 12 || 12;
    return `${formattedHour}:${minutes} ${ampm}`;
};

const isCurrentlyOpen = computed(() => {
    const todaySlot = props.hours.find(h => h.day_of_week === today);
    if (!todaySlot) return false;

    const now = new Date();
    const currentTime = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;

    return currentTime >= todaySlot.start_time && currentTime <= todaySlot.end_time;
});

const todayHours = computed(() => {
    return props.hours.find(h => h.day_of_week === today);
});

const isToday = (dayOfWeek: number) => dayOfWeek === today;
</script>

<template>
    <div class="business-hours">
        <!-- Left: Status Card -->
        <div class="business-hours__summary">
            <div class="business-hours__status-card">
                <div class="business-hours__status-badge" :class="isCurrentlyOpen ? 'business-hours__status-badge--open' : 'business-hours__status-badge--closed'">
                    <span class="business-hours__status-dot"></span>
                    <span class="business-hours__status-text">{{ isCurrentlyOpen ? 'Open Now' : 'Closed' }}</span>
                </div>
                <div class="business-hours__today-info">
                    <span class="business-hours__today-label">Today's Hours</span>
                    <span v-if="todayHours" class="business-hours__today-time">
                        {{ formatTime(todayHours.start_time) }} - {{ formatTime(todayHours.end_time) }}
                    </span>
                    <span v-else class="business-hours__today-time business-hours__today-time--closed">
                        Closed
                    </span>
                </div>
                <AppLink v-if="bookingUrl" :href="bookingUrl" class="business-hours__book-link">
                    <Button
                        label="Book Now"
                        icon="pi pi-calendar"
                        class="btn-primary w-full"
                    />
                </AppLink>
            </div>
        </div>

        <!-- Right: Full Schedule -->
        <div class="business-hours__schedule">
            <h3 class="business-hours__schedule-title">Weekly Schedule</h3>
            <div class="business-hours__grid">
                <div
                    v-for="slot in hours"
                    :key="slot.day_of_week"
                    class="business-hours__row"
                    :class="{ 'business-hours__row--today': isToday(slot.day_of_week) }"
                >
                    <div class="business-hours__day-wrapper">
                        <span v-if="isToday(slot.day_of_week)" class="business-hours__today-indicator"></span>
                        <span class="business-hours__day">{{ slot.day }}</span>
                        <span v-if="isToday(slot.day_of_week)" class="business-hours__today-tag">Today</span>
                    </div>
                    <span class="business-hours__time">
                        {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.business-hours {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.5rem;
    background: var(--provider-surface, #ffffff);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--provider-shadow-sm, 0 1px 3px rgba(0, 0, 0, 0.08));
}

.business-hours__summary {
    display: flex;
    flex-direction: column;
}

.business-hours__status-card {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding: 1.5rem;
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border-radius: 0.75rem;
    border: 1px solid var(--provider-primary-10, rgba(16, 107, 79, 0.1));
    height: 100%;
}

.business-hours__status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    width: fit-content;
}

.business-hours__status-badge--open {
    background: var(--provider-primary);
    color: white;
}

.business-hours__status-badge--closed {
    background: var(--provider-text-muted, #6b7280);
    color: white;
}

.business-hours__status-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: white;
    animation: pulse 2s infinite;
}

.business-hours__status-text {
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}

.business-hours__today-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.business-hours__today-label {
    font-size: 0.75rem;
    color: var(--provider-text-muted, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.business-hours__today-time {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text);
}

.business-hours__today-time--closed {
    color: var(--provider-text-muted, #6b7280);
}

.business-hours__book-link {
    margin-top: auto;
}

.business-hours__schedule {
    display: flex;
    flex-direction: column;
}

.business-hours__schedule-title {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--provider-text-muted, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.business-hours__grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.business-hours__row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 1rem;
    border-radius: 0.5rem;
    background: var(--provider-background, #f9fafb);
    transition: all 0.15s;
}

.business-hours__row:hover {
    background: var(--provider-background-alt, #f3f4f6);
}

.business-hours__row--today {
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border: 1px solid var(--provider-primary-10, rgba(16, 107, 79, 0.1));
}

.business-hours__row--today:hover {
    background: var(--provider-primary-10, rgba(16, 107, 79, 0.1));
}

.business-hours__day-wrapper {
    display: flex;
    align-items: center;
    gap: 0.625rem;
}

.business-hours__today-indicator {
    width: 0.5rem;
    height: 0.5rem;
    background: var(--provider-primary);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.business-hours__day {
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--provider-text);
}

/* Primary button styling */
:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.business-hours__row--today .business-hours__day {
    color: var(--provider-primary);
    font-weight: 600;
}

.business-hours__today-tag {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--provider-primary);
    background: var(--provider-primary-10, rgba(16, 107, 79, 0.1));
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.business-hours__time {
    font-size: 0.9375rem;
    color: var(--provider-text-body, #4b5563);
    font-variant-numeric: tabular-nums;
}

.business-hours__row--today .business-hours__time {
    color: var(--provider-primary);
    font-weight: 600;
}

@media (max-width: 768px) {
    .business-hours {
        grid-template-columns: 1fr;
    }

    .business-hours__status-card {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .business-hours__today-info {
        flex: 1;
    }

    .business-hours__book-link {
        width: 100%;
        margin-top: 0;
    }
}

@media (max-width: 480px) {
    .business-hours {
        padding: 1rem;
    }

    .business-hours__status-card {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

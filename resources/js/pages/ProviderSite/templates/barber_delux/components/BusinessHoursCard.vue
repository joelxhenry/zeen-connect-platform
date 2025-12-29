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
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Business Hours',
});

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
    <div class="hours-card">
        <div class="hours-header">
            <h3 class="hours-title">{{ title }}</h3>
            <div class="status-badge" :class="isCurrentlyOpen ? 'status-badge--open' : 'status-badge--closed'">
                <span class="status-dot"></span>
                <span>{{ isCurrentlyOpen ? 'Open' : 'Closed' }}</span>
            </div>
        </div>

        <!-- Today's Hours Highlight -->
        <div v-if="todayHours" class="today-highlight">
            <span class="today-label">Today</span>
            <span class="today-time">
                {{ formatTime(todayHours.start_time) }} - {{ formatTime(todayHours.end_time) }}
            </span>
        </div>

        <!-- Full Schedule -->
        <div class="schedule">
            <div
                v-for="slot in hours"
                :key="slot.day_of_week"
                class="schedule-row"
                :class="{ 'schedule-row--today': isToday(slot.day_of_week) }"
            >
                <span class="schedule-day">{{ slot.day }}</span>
                <span class="schedule-time">
                    {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                </span>
            </div>
        </div>

        <!-- CTA Button -->
        <AppLink v-if="bookingUrl" :href="bookingUrl" class="book-link">
            <Button label="Book Appointment" icon="pi pi-calendar" class="book-btn" />
        </AppLink>
    </div>
</template>

<style scoped>
.hours-card {
    background: var(--provider-surface, #fff);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.hours-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--provider-primary, #3b82f6);
}

.hours-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.status-badge--open {
    background: var(--provider-primary-10, rgba(59, 130, 246, 0.1));
    color: var(--provider-primary, #3b82f6);
}

.status-badge--closed {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.today-highlight {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    margin-bottom: 1rem;
    background: var(--provider-primary-10, rgba(59, 130, 246, 0.1));
    border-radius: 0.5rem;
    border-left: 3px solid var(--provider-primary, #3b82f6);
}

.today-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--provider-primary, #3b82f6);
}

.today-time {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--provider-text, #1f2937);
}

.schedule {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.schedule-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0;
    border-bottom: 1px solid var(--provider-border, #e5e7eb);
}

.schedule-row:last-child {
    border-bottom: none;
}

.schedule-row--today {
    padding: 0.625rem 0.75rem;
    margin: 0 -0.75rem;
    background: var(--provider-background, #f9fafb);
    border-radius: 0.375rem;
    border-bottom: none;
}

.schedule-day {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text, #1f2937);
}

.schedule-row--today .schedule-day {
    color: var(--provider-primary, #3b82f6);
    font-weight: 600;
}

.schedule-time {
    font-size: 0.875rem;
    color: var(--provider-text-muted, #6b7280);
    font-variant-numeric: tabular-nums;
}

.schedule-row--today .schedule-time {
    color: var(--provider-text, #1f2937);
    font-weight: 500;
}

.book-link {
    display: block;
    text-decoration: none;
}

:deep(.book-btn) {
    width: 100%;
    background: var(--provider-primary, #3b82f6) !important;
    border-color: var(--provider-primary, #3b82f6) !important;
}

:deep(.book-btn:hover) {
    background: var(--provider-primary-hover, #2563eb) !important;
    border-color: var(--provider-primary-hover, #2563eb) !important;
}
</style>

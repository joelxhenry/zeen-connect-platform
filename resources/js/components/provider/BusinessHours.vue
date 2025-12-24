<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
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
                <Link v-if="bookingUrl" :href="bookingUrl" class="business-hours__book-link">
                    <Button
                        label="Book Now"
                        icon="pi pi-calendar"
                        class="!bg-[#106B4F] !border-[#106B4F] w-full"
                    />
                </Link>
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
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border-radius: 0.75rem;
    border: 1px solid #d1fae5;
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
    background: #22c55e;
    color: white;
}

.business-hours__status-badge--closed {
    background: #ef4444;
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
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.business-hours__today-time {
    font-size: 1.25rem;
    font-weight: 600;
    color: #0D1F1B;
}

.business-hours__today-time--closed {
    color: #991b1b;
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
    color: #6b7280;
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
    background: #f9fafb;
    transition: all 0.15s;
}

.business-hours__row:hover {
    background: #f3f4f6;
}

.business-hours__row--today {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #bbf7d0;
}

.business-hours__row--today:hover {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
}

.business-hours__day-wrapper {
    display: flex;
    align-items: center;
    gap: 0.625rem;
}

.business-hours__today-indicator {
    width: 0.5rem;
    height: 0.5rem;
    background: #22c55e;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.business-hours__day {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #0D1F1B;
}

.business-hours__row--today .business-hours__day {
    color: #166534;
    font-weight: 600;
}

.business-hours__today-tag {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #166534;
    background: #bbf7d0;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.business-hours__time {
    font-size: 0.9375rem;
    color: #4b5563;
    font-variant-numeric: tabular-nums;
}

.business-hours__row--today .business-hours__time {
    color: #15803d;
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

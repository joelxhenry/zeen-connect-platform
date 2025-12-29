<script setup lang="ts">
import { ref, computed } from 'vue';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { ConsoleEmptyState } from '@/components/console';
import { useToast } from 'primevue/usetoast';

export interface Break {
    id?: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    label: string | null;
}

interface Props {
    breaks: Break[];
    processing?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    processing: false,
});

const emit = defineEmits<{
    'update:breaks': [breaks: Break[]];
    save: [];
}>();

const toast = useToast();

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const dayOptions = dayNames.map((name, index) => ({
    label: name,
    value: index,
}));

const timeOptions = computed(() => {
    const options = [];
    for (let hour = 6; hour <= 22; hour++) {
        for (const minute of [0, 30]) {
            const time = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            options.push({ label: formatTime(time), value: time });
        }
    }
    return options;
});

const formatTime = (time: string): string => {
    const [hours, minutes] = time.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    return `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};

// New break form state
const newBreak = ref({
    day_of_week: 1, // Monday default
    start_time: '12:00',
    end_time: '13:00',
    label: '',
});

// Group breaks by day for display
const breaksByDay = computed(() => {
    const grouped: Record<number, Break[]> = {};
    props.breaks.forEach((b) => {
        if (!grouped[b.day_of_week]) {
            grouped[b.day_of_week] = [];
        }
        grouped[b.day_of_week].push(b);
    });
    // Sort breaks within each day by start time
    Object.values(grouped).forEach((dayBreaks) => {
        dayBreaks.sort((a, b) => a.start_time.localeCompare(b.start_time));
    });
    return grouped;
});

// Sorted day keys for ordered display
const sortedDays = computed(() => {
    return Object.keys(breaksByDay.value)
        .map(Number)
        .sort((a, b) => a - b);
});

const addBreak = () => {
    if (!newBreak.value.start_time || !newBreak.value.end_time) {
        toast.add({
            severity: 'warn',
            summary: 'Missing Times',
            detail: 'Please select both start and end times',
            life: 3000,
        });
        return;
    }

    if (newBreak.value.start_time >= newBreak.value.end_time) {
        toast.add({
            severity: 'warn',
            summary: 'Invalid Time Range',
            detail: 'End time must be after start time',
            life: 3000,
        });
        return;
    }

    // Check for overlapping breaks on the same day
    const dayBreaks = props.breaks.filter((b) => b.day_of_week === newBreak.value.day_of_week);
    const hasOverlap = dayBreaks.some((existing) => {
        return (
            (newBreak.value.start_time >= existing.start_time && newBreak.value.start_time < existing.end_time) ||
            (newBreak.value.end_time > existing.start_time && newBreak.value.end_time <= existing.end_time) ||
            (newBreak.value.start_time <= existing.start_time && newBreak.value.end_time >= existing.end_time)
        );
    });

    if (hasOverlap) {
        toast.add({
            severity: 'warn',
            summary: 'Overlapping Break',
            detail: 'This break overlaps with an existing break on the same day',
            life: 3000,
        });
        return;
    }

    const updatedBreaks = [
        ...props.breaks,
        {
            day_of_week: newBreak.value.day_of_week,
            start_time: newBreak.value.start_time,
            end_time: newBreak.value.end_time,
            label: newBreak.value.label || null,
        },
    ];

    emit('update:breaks', updatedBreaks);

    // Reset form
    newBreak.value = {
        day_of_week: newBreak.value.day_of_week,
        start_time: '12:00',
        end_time: '13:00',
        label: '',
    };
};

const removeBreak = (breakToRemove: Break) => {
    const updatedBreaks = props.breaks.filter(
        (b) =>
            !(
                b.day_of_week === breakToRemove.day_of_week &&
                b.start_time === breakToRemove.start_time &&
                b.end_time === breakToRemove.end_time
            )
    );
    emit('update:breaks', updatedBreaks);
};

const handleSave = () => {
    emit('save');
};
</script>

<template>
    <div class="breaks-manager">
        <!-- Add New Break Form -->
        <div class="add-break-form">
            <div class="form-row">
                <Select
                    v-model="newBreak.day_of_week"
                    :options="dayOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Day"
                    class="day-select"
                />
                <Select
                    v-model="newBreak.start_time"
                    :options="timeOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Start"
                    class="time-select"
                />
                <span class="time-separator">to</span>
                <Select
                    v-model="newBreak.end_time"
                    :options="timeOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="End"
                    class="time-select"
                />
            </div>
            <div class="form-row-secondary">
                <InputText
                    v-model="newBreak.label"
                    placeholder="Label (e.g., Lunch)"
                    class="label-input"
                />
                <Button
                    icon="pi pi-plus"
                    @click="addBreak"
                    v-tooltip="'Add Break'"
                    severity="secondary"
                />
            </div>
        </div>

        <!-- Breaks List -->
        <ConsoleEmptyState
            v-if="breaks.length === 0"
            icon="pi pi-clock"
            title="No breaks configured"
            description="Add breaks for lunch, personal time, or other recurring unavailable periods"
            size="compact"
        />

        <div v-else class="breaks-list">
            <div v-for="day in sortedDays" :key="day" class="day-group">
                <div class="day-header">{{ dayNames[day] }}</div>
                <div class="day-breaks">
                    <div
                        v-for="(breakItem, index) in breaksByDay[day]"
                        :key="`${day}-${index}`"
                        class="break-item group"
                    >
                        <div class="break-info">
                            <span class="break-time">
                                {{ formatTime(breakItem.start_time) }} - {{ formatTime(breakItem.end_time) }}
                            </span>
                            <span v-if="breakItem.label" class="break-label">
                                {{ breakItem.label }}
                            </span>
                        </div>
                        <Button
                            icon="pi pi-times"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="removeBreak(breakItem)"
                            class="remove-btn"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button (optional slot for external control) -->
        <slot name="actions">
            <div class="save-actions">
                <Button
                    label="Save Breaks"
                    icon="pi pi-check"
                    :loading="processing"
                    @click="handleSave"
                    severity="primary"
                />
            </div>
        </slot>
    </div>
</template>

<style scoped>
.breaks-manager {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.add-break-form {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.form-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.form-row-secondary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.day-select {
    width: 140px;
}

.time-select {
    flex: 1;
    min-width: 100px;
}

.time-separator {
    color: #9ca3af;
    font-size: 0.875rem;
}

.label-input {
    flex: 1;
}

.breaks-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 300px;
    overflow-y: auto;
}

.day-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.day-header {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    letter-spacing: 0.05em;
}

.day-breaks {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.break-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.625rem 0.75rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
}

.break-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.break-time {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0d1f1b;
}

.break-label {
    font-size: 0.75rem;
    color: #6b7280;
    background-color: #e5e7eb;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
}

.remove-btn {
    opacity: 0;
    transition: opacity 0.15s;
}

.group:hover .remove-btn {
    opacity: 1;
}

.save-actions {
    display: none;
}

@media (max-width: 640px) {
    .form-row {
        flex-direction: column;
        align-items: stretch;
    }

    .day-select {
        width: 100%;
    }

    .form-row-secondary {
        flex-direction: column;
        align-items: stretch;
    }

    .form-row-secondary .label-input {
        width: 100%;
    }

    .remove-btn {
        opacity: 1;
    }
}
</style>

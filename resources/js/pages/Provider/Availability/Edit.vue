<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleEmptyState,
    ConsoleButton,
} from '@/components/console';
import InputSwitch from 'primevue/inputswitch';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { useToast } from 'primevue/usetoast';
import AvailabilityController from '@/actions/App/Domains/Provider/Controllers/AvailabilityController';
import type { Break } from '@/components/availability';

interface ScheduleDay {
    day_of_week: number;
    start_time: string;
    end_time: string;
    is_available: boolean;
}

interface BlockedDate {
    id?: number;
    date: string;
    reason: string | null;
}

interface Props {
    weeklySchedule: ScheduleDay[];
    blockedDates: BlockedDate[];
    breaks: Break[];
    bufferMinutes: number;
}

const props = defineProps<Props>();
const toast = useToast();

const activeTab = ref('0');

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const shortDayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

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

const dayOptions = dayNames.map((name, index) => ({
    label: name,
    value: index,
}));

const bufferOptions = [
    { label: 'No buffer', value: 0 },
    { label: '5 minutes', value: 5 },
    { label: '10 minutes', value: 10 },
    { label: '15 minutes', value: 15 },
    { label: '30 minutes', value: 30 },
    { label: '45 minutes', value: 45 },
    { label: '1 hour', value: 60 },
    { label: '1.5 hours', value: 90 },
    { label: '2 hours', value: 120 },
];

const formatTime = (time: string): string => {
    const [hours, minutes] = time.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    return `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};

const formatDate = (dateStr: string): string => {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

// Forms
const scheduleForm = useForm({
    schedule: props.weeklySchedule.map(day => ({
        day_of_week: day.day_of_week,
        start_time: day.start_time,
        end_time: day.end_time,
        is_available: day.is_available,
    })),
});

const blockedDatesForm = useForm({
    blocked_dates: props.blockedDates.map(bd => ({
        date: bd.date,
        reason: bd.reason,
    })),
});

const breaksForm = useForm({
    breaks: props.breaks.map(b => ({
        day_of_week: b.day_of_week,
        start_time: b.start_time,
        end_time: b.end_time,
        label: b.label,
    })),
});

const bufferForm = useForm({
    buffer_minutes: props.bufferMinutes,
});

// New blocked date form
const newBlockedDate = ref<Date | null>(null);
const newBlockedReason = ref('');

// New break form
const newBreak = ref({
    day_of_week: 1,
    start_time: '12:00',
    end_time: '13:00',
    label: '',
});

// Computed
const availableDaysCount = computed(() => {
    return scheduleForm.schedule.filter(d => d.is_available).length;
});

const breaksByDay = computed(() => {
    const grouped: Record<number, Break[]> = {};
    breaksForm.breaks.forEach((b) => {
        if (!grouped[b.day_of_week]) {
            grouped[b.day_of_week] = [];
        }
        grouped[b.day_of_week].push(b);
    });
    Object.values(grouped).forEach((dayBreaks) => {
        dayBreaks.sort((a, b) => a.start_time.localeCompare(b.start_time));
    });
    return grouped;
});

const sortedBreakDays = computed(() => {
    return Object.keys(breaksByDay.value)
        .map(Number)
        .sort((a, b) => a - b);
});

// Actions
const saveSchedule = () => {
    scheduleForm.put(AvailabilityController.updateSchedule().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Schedule saved',
                detail: 'Your weekly schedule has been updated',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update schedule',
                life: 3000,
            });
        },
    });
};

const saveBlockedDates = () => {
    blockedDatesForm.put(AvailabilityController.updateBlockedDates().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Blocked dates saved',
                detail: 'Your blocked dates have been updated',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update blocked dates',
                life: 3000,
            });
        },
    });
};

const addBlockedDate = () => {
    if (!newBlockedDate.value) return;

    const dateStr = newBlockedDate.value.toISOString().split('T')[0];

    const exists = blockedDatesForm.blocked_dates.some(bd => bd.date === dateStr);
    if (exists) {
        toast.add({
            severity: 'warn',
            summary: 'Already blocked',
            detail: 'This date is already in your blocked dates',
            life: 3000,
        });
        return;
    }

    blockedDatesForm.blocked_dates.push({
        date: dateStr,
        reason: newBlockedReason.value || null,
    });

    newBlockedDate.value = null;
    newBlockedReason.value = '';
};

const removeBlockedDate = (index: number) => {
    blockedDatesForm.blocked_dates.splice(index, 1);
};

const saveBreaks = () => {
    breaksForm.put(AvailabilityController.updateBreaks().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Breaks saved',
                detail: 'Your breaks have been updated',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update breaks',
                life: 3000,
            });
        },
    });
};

const addBreak = () => {
    if (!newBreak.value.start_time || !newBreak.value.end_time) {
        toast.add({
            severity: 'warn',
            summary: 'Missing times',
            detail: 'Please select both start and end times',
            life: 3000,
        });
        return;
    }

    if (newBreak.value.start_time >= newBreak.value.end_time) {
        toast.add({
            severity: 'warn',
            summary: 'Invalid range',
            detail: 'End time must be after start time',
            life: 3000,
        });
        return;
    }

    const dayBreaks = breaksForm.breaks.filter((b) => b.day_of_week === newBreak.value.day_of_week);
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
            summary: 'Overlap detected',
            detail: 'This break overlaps with an existing break',
            life: 3000,
        });
        return;
    }

    breaksForm.breaks.push({
        day_of_week: newBreak.value.day_of_week,
        start_time: newBreak.value.start_time,
        end_time: newBreak.value.end_time,
        label: newBreak.value.label || null,
    });

    newBreak.value = {
        day_of_week: newBreak.value.day_of_week,
        start_time: '12:00',
        end_time: '13:00',
        label: '',
    };
};

const removeBreak = (breakToRemove: Break) => {
    const index = breaksForm.breaks.findIndex(
        (b) =>
            b.day_of_week === breakToRemove.day_of_week &&
            b.start_time === breakToRemove.start_time &&
            b.end_time === breakToRemove.end_time
    );
    if (index !== -1) {
        breaksForm.breaks.splice(index, 1);
    }
};

const saveBuffer = () => {
    bufferForm.put(AvailabilityController.updateBuffer().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Buffer saved',
                detail: 'Buffer time has been updated',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update buffer time',
                life: 3000,
            });
        },
    });
};
</script>

<template>
    <ConsoleLayout title="Availability">
        <div class="w-full max-w-4xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Availability"
                subtitle="Set when you're available for bookings"
            />

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat-item">
                    <span class="stat-value">{{ availableDaysCount }}</span>
                    <span class="stat-label">Working days</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">{{ blockedDatesForm.blocked_dates.length }}</span>
                    <span class="stat-label">Blocked dates</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">{{ breaksForm.breaks.length }}</span>
                    <span class="stat-label">Breaks</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">{{ bufferForm.buffer_minutes }}m</span>
                    <span class="stat-label">Buffer</span>
                </div>
            </div>

            <!-- Tabbed Content -->
            <Tabs v-model:value="activeTab" class="availability-tabs">
                <TabList>
                    <Tab value="0">
                        <i class="pi pi-calendar mr-2"></i>
                        <span class="hidden sm:inline">Weekly Schedule</span>
                        <span class="sm:hidden">Schedule</span>
                    </Tab>
                    <Tab value="1">
                        <i class="pi pi-ban mr-2"></i>
                        <span class="hidden sm:inline">Blocked Dates</span>
                        <span class="sm:hidden">Blocked</span>
                    </Tab>
                    <Tab value="2">
                        <i class="pi pi-clock mr-2"></i>
                        <span>Breaks</span>
                    </Tab>
                    <Tab value="3">
                        <i class="pi pi-stopwatch mr-2"></i>
                        <span>Buffer</span>
                    </Tab>
                </TabList>

                <TabPanels>
                    <!-- Weekly Schedule Panel -->
                    <TabPanel value="0">
                        <div class="tab-content">
                            <div class="tab-header">
                                <div>
                                    <h3 class="tab-title">Weekly Schedule</h3>
                                    <p class="tab-description">Set your regular working hours for each day of the week</p>
                                </div>
                                <ConsoleButton
                                    label="Save Schedule"
                                    icon="pi pi-check"
                                    size="small"
                                    :loading="scheduleForm.processing"
                                    @click="saveSchedule"
                                />
                            </div>

                            <div class="schedule-grid">
                                <div
                                    v-for="day in scheduleForm.schedule"
                                    :key="day.day_of_week"
                                    class="schedule-row"
                                    :class="{ 'schedule-row--disabled': !day.is_available }"
                                >
                                    <div class="schedule-day">
                                        <InputSwitch v-model="day.is_available" />
                                        <span class="day-name">{{ dayNames[day.day_of_week] }}</span>
                                        <span class="day-name-short">{{ shortDayNames[day.day_of_week] }}</span>
                                    </div>

                                    <div class="schedule-times" :class="{ 'schedule-times--disabled': !day.is_available }">
                                        <template v-if="day.is_available">
                                            <Select
                                                v-model="day.start_time"
                                                :options="timeOptions"
                                                optionLabel="label"
                                                optionValue="value"
                                                placeholder="Start"
                                                class="time-select"
                                            />
                                            <span class="time-separator">to</span>
                                            <Select
                                                v-model="day.end_time"
                                                :options="timeOptions"
                                                optionLabel="label"
                                                optionValue="value"
                                                placeholder="End"
                                                class="time-select"
                                            />
                                        </template>
                                        <span v-else class="closed-label">Closed</span>
                                    </div>
                                </div>
                            </div>

                            <small v-if="scheduleForm.errors.schedule" class="error-text">
                                {{ scheduleForm.errors.schedule }}
                            </small>
                        </div>
                    </TabPanel>

                    <!-- Blocked Dates Panel -->
                    <TabPanel value="1">
                        <div class="tab-content">
                            <div class="tab-header">
                                <div>
                                    <h3 class="tab-title">Blocked Dates</h3>
                                    <p class="tab-description">Block specific dates when you're unavailable for appointments</p>
                                </div>
                                <ConsoleButton
                                    label="Save Changes"
                                    icon="pi pi-check"
                                    size="small"
                                    :loading="blockedDatesForm.processing"
                                    @click="saveBlockedDates"
                                />
                            </div>

                            <!-- Add New Blocked Date -->
                            <div class="add-form">
                                <div class="blocked-add-row">
                                    <div class="blocked-add-field">
                                        <label class="blocked-add-label">Date</label>
                                        <DatePicker
                                            v-model="newBlockedDate"
                                            :minDate="new Date()"
                                            placeholder="Select a date"
                                            dateFormat="M dd, yy"
                                            showIcon
                                        />
                                    </div>
                                    <div class="blocked-add-field blocked-add-field--reason">
                                        <label class="blocked-add-label">Reason (optional)</label>
                                        <InputText
                                            v-model="newBlockedReason"
                                            placeholder="e.g., Vacation, Holiday"
                                        />
                                    </div>
                                    <Button
                                        label="Add Date"
                                        icon="pi pi-plus"
                                        @click="addBlockedDate"
                                        :disabled="!newBlockedDate"
                                        severity="secondary"
                                        class="blocked-add-btn"
                                    />
                                </div>
                            </div>

                            <!-- Blocked Dates List -->
                            <ConsoleEmptyState
                                v-if="blockedDatesForm.blocked_dates.length === 0"
                                icon="pi pi-calendar-times"
                                title="No blocked dates"
                                description="Add dates when you won't be available for bookings"
                                size="compact"
                            />
                            <div v-else class="blocked-list">
                                <div
                                    v-for="(blocked, index) in blockedDatesForm.blocked_dates"
                                    :key="index"
                                    class="blocked-item group"
                                >
                                    <div class="blocked-info">
                                        <i class="pi pi-calendar blocked-icon"></i>
                                        <div class="blocked-details">
                                            <span class="blocked-date">{{ formatDate(blocked.date) }}</span>
                                            <span v-if="blocked.reason" class="blocked-reason">{{ blocked.reason }}</span>
                                        </div>
                                    </div>
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        size="small"
                                        @click="removeBlockedDate(index)"
                                        class="remove-btn"
                                        v-tooltip="'Remove'"
                                    />
                                </div>
                            </div>

                            <small v-if="blockedDatesForm.errors.blocked_dates" class="error-text">
                                {{ blockedDatesForm.errors.blocked_dates }}
                            </small>
                        </div>
                    </TabPanel>

                    <!-- Breaks Panel -->
                    <TabPanel value="2">
                        <div class="tab-content">
                            <div class="tab-header">
                                <div>
                                    <h3 class="tab-title">Recurring Breaks</h3>
                                    <p class="tab-description">Schedule regular breaks like lunch or personal time</p>
                                </div>
                                <ConsoleButton
                                    label="Save Changes"
                                    icon="pi pi-check"
                                    size="small"
                                    :loading="breaksForm.processing"
                                    @click="saveBreaks"
                                />
                            </div>

                            <!-- Add New Break -->
                            <div class="add-form">
                                <div class="breaks-add-row">
                                    <div class="breaks-add-field">
                                        <label class="breaks-add-label">Day</label>
                                        <Select
                                            v-model="newBreak.day_of_week"
                                            :options="dayOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Select day"
                                        />
                                    </div>
                                    <div class="breaks-add-field breaks-add-field--time">
                                        <label class="breaks-add-label">Time</label>
                                        <div class="breaks-time-range">
                                            <Select
                                                v-model="newBreak.start_time"
                                                :options="timeOptions"
                                                optionLabel="label"
                                                optionValue="value"
                                                placeholder="Start"
                                            />
                                            <span class="time-separator">to</span>
                                            <Select
                                                v-model="newBreak.end_time"
                                                :options="timeOptions"
                                                optionLabel="label"
                                                optionValue="value"
                                                placeholder="End"
                                            />
                                        </div>
                                    </div>
                                    <div class="breaks-add-field breaks-add-field--label">
                                        <label class="breaks-add-label">Label (optional)</label>
                                        <InputText
                                            v-model="newBreak.label"
                                            placeholder="e.g., Lunch"
                                        />
                                    </div>
                                    <Button
                                        label="Add Break"
                                        icon="pi pi-plus"
                                        @click="addBreak"
                                        severity="secondary"
                                        class="breaks-add-btn"
                                    />
                                </div>
                            </div>

                            <!-- Breaks List -->
                            <ConsoleEmptyState
                                v-if="breaksForm.breaks.length === 0"
                                icon="pi pi-clock"
                                title="No breaks scheduled"
                                description="Add recurring breaks to block time for lunch, meetings, or personal time"
                                size="compact"
                            />
                            <div v-else class="breaks-list">
                                <div v-for="day in sortedBreakDays" :key="day" class="breaks-day-group">
                                    <div class="breaks-day-header">{{ dayNames[day] }}</div>
                                    <div class="breaks-day-items">
                                        <div
                                            v-for="(breakItem, index) in breaksByDay[day]"
                                            :key="`${day}-${index}`"
                                            class="break-item group"
                                        >
                                            <div class="break-info">
                                                <span class="break-time">
                                                    {{ formatTime(breakItem.start_time) }} - {{ formatTime(breakItem.end_time) }}
                                                </span>
                                                <span v-if="breakItem.label" class="break-label">{{ breakItem.label }}</span>
                                            </div>
                                            <Button
                                                icon="pi pi-trash"
                                                severity="danger"
                                                text
                                                rounded
                                                size="small"
                                                @click="removeBreak(breakItem)"
                                                class="remove-btn"
                                                v-tooltip="'Remove'"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <small v-if="breaksForm.errors.breaks" class="error-text">
                                {{ breaksForm.errors.breaks }}
                            </small>
                        </div>
                    </TabPanel>

                    <!-- Buffer Panel -->
                    <TabPanel value="3">
                        <div class="tab-content">
                            <div class="tab-header">
                                <div>
                                    <h3 class="tab-title">Buffer Time</h3>
                                    <p class="tab-description">Add time between appointments for preparation or cleanup</p>
                                </div>
                            </div>

                            <div class="buffer-section">
                                <div class="buffer-card">
                                    <div class="buffer-icon">
                                        <i class="pi pi-stopwatch"></i>
                                    </div>
                                    <div class="buffer-content">
                                        <label class="buffer-label">Time between bookings</label>
                                        <p class="buffer-description">
                                            Buffer time is automatically added after each appointment, preventing back-to-back bookings.
                                        </p>
                                        <div class="buffer-controls">
                                            <Select
                                                v-model="bufferForm.buffer_minutes"
                                                :options="bufferOptions"
                                                optionLabel="label"
                                                optionValue="value"
                                                class="buffer-select"
                                            />
                                            <ConsoleButton
                                                label="Save"
                                                icon="pi pi-check"
                                                size="small"
                                                :loading="bufferForm.processing"
                                                @click="saveBuffer"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="buffer-example">
                                    <div class="example-title">
                                        <i class="pi pi-info-circle"></i>
                                        Example
                                    </div>
                                    <p class="example-text">
                                        With a {{ bufferForm.buffer_minutes || 0 }} minute buffer, if a 1-hour appointment ends at 2:00 PM,
                                        the next available slot will be at {{ bufferForm.buffer_minutes ? '2:' + (bufferForm.buffer_minutes < 10 ? '0' : '') + bufferForm.buffer_minutes : '2:00' }} PM.
                                    </p>
                                </div>
                            </div>

                            <small v-if="bufferForm.errors.buffer_minutes" class="error-text">
                                {{ bufferForm.errors.buffer_minutes }}
                            </small>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
/* Quick Stats */
.quick-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    background: white;
    border-radius: 0.75rem;
    padding: 1rem;
    text-align: center;
    border: 1px solid #e5e7eb;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #106B4F;
    line-height: 1.2;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

/* Tabs */
.availability-tabs {
    background: white;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.availability-tabs :deep(.p-tablist) {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    padding: 0.5rem 0.5rem 0;
}

.availability-tabs :deep(.p-tab) {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    border-radius: 0.5rem 0.5rem 0 0;
    margin-right: 0.25rem;
    border: none;
    background: transparent;
}

.availability-tabs :deep(.p-tab:hover) {
    color: #0D1F1B;
    background: rgba(16, 107, 79, 0.05);
}

.availability-tabs :deep(.p-tab-active) {
    color: #106B4F;
    background: white;
    border: 1px solid #e5e7eb;
    border-bottom-color: white;
    margin-bottom: -1px;
}

.availability-tabs :deep(.p-tabpanels) {
    padding: 0;
}

/* Tab Content */
.tab-content {
    padding: 1.5rem;
}

.tab-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.tab-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0 0 0.25rem 0;
}

.tab-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

/* Schedule Grid */
.schedule-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.schedule-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: #f9fafb;
    border-radius: 0.75rem;
    transition: all 0.15s ease;
    flex-wrap: wrap;
}

.schedule-row--disabled {
    background: #f3f4f6;
}

.schedule-row--disabled .day-name,
.schedule-row--disabled .day-name-short {
    color: #9ca3af;
}

.schedule-day {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 150px;
    flex-shrink: 0;
}

.day-name {
    font-weight: 500;
    color: #0D1F1B;
    min-width: 90px;
}

.day-name-short {
    display: none;
    font-weight: 500;
    color: #0D1F1B;
}

.schedule-times {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

.schedule-times--disabled {
    opacity: 0.5;
}

.time-select {
    width: 130px;
    flex-shrink: 0;
}

.time-separator {
    color: #9ca3af;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.closed-label {
    font-size: 0.875rem;
    color: #9ca3af;
    font-style: italic;
}

/* Add Form */
.add-form {
    background: #f9fafb;
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

/* Blocked Dates Add Form */
.blocked-add-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.blocked-add-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.blocked-add-field--reason {
    flex: 1;
    min-width: 200px;
}

.blocked-add-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.blocked-add-btn {
    flex-shrink: 0;
}

/* Breaks Add Form */
.breaks-add-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.breaks-add-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.breaks-add-field--time {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.breaks-add-field--label {
    flex: 1;
    min-width: 150px;
}

.breaks-add-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.breaks-time-range {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.breaks-time-range :deep(.p-select) {
    width: 130px;
}

.breaks-add-btn {
    flex-shrink: 0;
}

/* Legacy - keeping for compatibility */
.add-form-fields {
    display: grid;
    grid-template-columns: 200px 1fr auto;
    gap: 1rem;
    align-items: end;
}

.add-form-fields--breaks {
    grid-template-columns: 140px auto 1fr auto;
}

.add-form-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.add-form-field--grow {
    min-width: 0;
}

.add-form-field--button {
    align-self: end;
}

.add-form-field-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.add-form-time-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.add-form-time-range :deep(.p-select) {
    width: 115px;
}

.add-form-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.add-form-date {
    width: 220px;
    flex-shrink: 0;
}

.add-form-reason {
    flex: 1;
    min-width: 180px;
}

.add-form-grid {
    display: grid;
    grid-template-columns: 140px auto 160px auto;
    gap: 1rem;
    align-items: end;
}

.add-form-day {
    width: 100%;
}

.add-form-times {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.add-form-times :deep(.p-select) {
    width: 120px;
}

.add-form-label {
    width: 100%;
}

/* Blocked List */
.blocked-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 400px;
    overflow-y: auto;
}

.blocked-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    transition: background-color 0.15s;
}

.blocked-item:hover {
    background: #f3f4f6;
}

.blocked-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.blocked-icon {
    color: #ef4444;
    font-size: 1rem;
}

.blocked-details {
    display: flex;
    flex-direction: column;
}

.blocked-date {
    font-weight: 500;
    color: #0D1F1B;
    font-size: 0.875rem;
}

.blocked-reason {
    font-size: 0.75rem;
    color: #6b7280;
}

/* Breaks List */
.breaks-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.breaks-day-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.breaks-day-header {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    letter-spacing: 0.05em;
    padding-left: 0.25rem;
}

.breaks-day-items {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.break-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
}

.break-item:hover {
    background: #f3f4f6;
}

.break-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.break-time {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0D1F1B;
}

.break-label {
    font-size: 0.75rem;
    color: #6b7280;
    background: #e5e7eb;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
}

/* Buffer Section */
.buffer-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.buffer-card {
    display: flex;
    gap: 1.25rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(16, 107, 79, 0.05) 0%, rgba(16, 107, 79, 0.02) 100%);
    border: 1px solid rgba(16, 107, 79, 0.1);
    border-radius: 0.75rem;
}

.buffer-icon {
    width: 48px;
    height: 48px;
    background: #106B4F;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.buffer-content {
    flex: 1;
}

.buffer-label {
    display: block;
    font-weight: 600;
    color: #0D1F1B;
    margin-bottom: 0.5rem;
}

.buffer-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0 0 1rem 0;
    line-height: 1.5;
}

.buffer-controls {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.buffer-select {
    width: 180px;
}

.buffer-example {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1rem;
}

.example-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.example-text {
    font-size: 0.875rem;
    color: #4b5563;
    margin: 0;
    line-height: 1.5;
}

/* Remove Button */
.remove-btn {
    opacity: 0;
    transition: opacity 0.15s;
}

.group:hover .remove-btn {
    opacity: 1;
}

/* Error Text */
.error-text {
    display: block;
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.5rem;
}

/* Responsive */
@media (max-width: 900px) {
    .schedule-row {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .schedule-day {
        min-width: auto;
    }

    .schedule-times {
        justify-content: flex-start;
    }

    .time-select {
        flex: 1;
        width: auto;
        min-width: 120px;
    }
}

@media (max-width: 768px) {
    .quick-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .stat-value {
        font-size: 1.25rem;
    }

    .tab-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .day-name {
        display: none;
    }

    .day-name-short {
        display: block;
    }

    /* Blocked dates add form responsive */
    .blocked-add-row {
        flex-direction: column;
        align-items: stretch;
    }

    .blocked-add-field {
        width: 100%;
    }

    .blocked-add-field--reason {
        min-width: auto;
    }

    .blocked-add-btn {
        width: 100%;
    }

    /* Breaks add form responsive */
    .breaks-add-row {
        flex-direction: column;
        align-items: stretch;
    }

    .breaks-add-field {
        width: 100%;
    }

    .breaks-add-field--label {
        min-width: auto;
    }

    .breaks-time-range {
        width: 100%;
    }

    .breaks-time-range :deep(.p-select) {
        flex: 1;
        width: auto;
    }

    .breaks-add-btn {
        width: 100%;
    }

    /* Legacy styles */
    .add-form-fields {
        grid-template-columns: 1fr;
    }

    .add-form-fields--breaks {
        grid-template-columns: 1fr;
    }

    .add-form-field--button {
        justify-self: stretch;
    }

    .add-form-field--button :deep(.p-button) {
        width: 100%;
    }

    .add-form-time-range {
        width: 100%;
    }

    .add-form-time-range :deep(.p-select) {
        flex: 1;
        width: auto;
    }

    .add-form-row {
        flex-direction: column;
        align-items: stretch;
    }

    .add-form-date {
        width: 100%;
    }

    .add-form-reason {
        min-width: auto;
    }

    .add-form-grid {
        grid-template-columns: 1fr;
    }

    .add-form-times {
        width: 100%;
    }

    .add-form-times :deep(.p-select) {
        flex: 1;
        width: auto;
    }

    .buffer-card {
        flex-direction: column;
    }

    .buffer-controls {
        flex-direction: column;
        align-items: stretch;
    }

    .buffer-select {
        width: 100%;
    }

    .remove-btn {
        opacity: 1;
    }
}

@media (max-width: 480px) {
    .tab-content {
        padding: 1rem;
    }

    .availability-tabs :deep(.p-tab) {
        padding: 0.625rem 0.5rem;
        font-size: 0.75rem;
    }

    .availability-tabs :deep(.p-tab i) {
        margin-right: 0.25rem;
    }
}
</style>

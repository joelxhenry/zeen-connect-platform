<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SettingsLayout from '@/components/layout/SettingsLayout.vue';
import ConsoleFormCard from '@/components/console/ConsoleFormCard.vue';
import { ConsoleButton } from '@/components/console';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import InputSwitch from 'primevue/inputswitch';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import InputText from 'primevue/inputtext';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

interface DaySchedule {
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

interface Break {
    id?: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    label: string | null;
}

const props = defineProps<{
    weeklySchedule: DaySchedule[];
    blockedDates: BlockedDate[];
    breaks: Break[];
    bufferMinutes: number;
}>();

const confirm = useConfirm();

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Schedule form
const scheduleForm = useForm({
    schedule: props.weeklySchedule.map(day => ({ ...day })),
});

// Blocked dates form
const blockedDatesForm = useForm({
    blocked_dates: props.blockedDates.map(d => ({ ...d })),
});

// Breaks form
const breaksForm = useForm({
    breaks: props.breaks.map(b => ({ ...b })),
});

// Buffer form
const bufferForm = useForm({
    buffer_minutes: props.bufferMinutes,
});

// New blocked date input
const newBlockedDate = ref<Date | null>(null);
const newBlockedReason = ref('');

// New break input
const showAddBreak = ref(false);
const newBreak = ref<Break>({
    day_of_week: 1,
    start_time: '12:00',
    end_time: '13:00',
    label: 'Lunch',
});

const bufferOptions = [
    { label: 'No buffer', value: 0 },
    { label: '5 minutes', value: 5 },
    { label: '10 minutes', value: 10 },
    { label: '15 minutes', value: 15 },
    { label: '30 minutes', value: 30 },
    { label: '45 minutes', value: 45 },
    { label: '60 minutes', value: 60 },
];

const saveSchedule = () => {
    scheduleForm.put(resolveUrl(provider.availability.schedule.url()), {
        preserveScroll: true,
        onSuccess: () => {
            scheduleForm.defaults({
                schedule: scheduleForm.schedule.map(day => ({ ...day })),
            });
            scheduleForm.reset();
        },
    });
};

const addBlockedDate = () => {
    if (!newBlockedDate.value) return;

    const dateStr = newBlockedDate.value.toISOString().split('T')[0];

    // Check if date already exists
    if (blockedDatesForm.blocked_dates.some(d => d.date === dateStr)) {
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

const saveBlockedDates = () => {
    blockedDatesForm.put(resolveUrl(provider.availability['blocked-dates'].url()), {
        preserveScroll: true,
        onSuccess: () => {
            blockedDatesForm.defaults({
                blocked_dates: blockedDatesForm.blocked_dates.map(d => ({ ...d })),
            });
            blockedDatesForm.reset();
        },
    });
};

const addBreak = () => {
    breaksForm.breaks.push({ ...newBreak.value });
    showAddBreak.value = false;
    newBreak.value = {
        day_of_week: 1,
        start_time: '12:00',
        end_time: '13:00',
        label: 'Lunch',
    };
};

const removeBreak = (index: number) => {
    breaksForm.breaks.splice(index, 1);
};

const saveBreaks = () => {
    breaksForm.put(resolveUrl(provider.availability.breaks.url()), {
        preserveScroll: true,
        onSuccess: () => {
            breaksForm.defaults({
                breaks: breaksForm.breaks.map(b => ({ ...b })),
            });
            breaksForm.reset();
        },
    });
};

const saveBuffer = () => {
    bufferForm.put(resolveUrl(provider.availability.buffer.url()), {
        preserveScroll: true,
        onSuccess: () => {
            bufferForm.defaults({
                buffer_minutes: bufferForm.buffer_minutes,
            });
            bufferForm.reset();
        },
    });
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

const getBreaksForDay = (dayOfWeek: number) => {
    return breaksForm.breaks.filter(b => b.day_of_week === dayOfWeek);
};
</script>

<template>
    <SettingsLayout title="Availability">
        <div class="availability-page">
            <!-- Weekly Schedule -->
            <ConsoleFormCard title="Working Hours">
                <template #header-actions>
                    <ConsoleButton
                        v-if="scheduleForm.isDirty"
                        label="Save"
                        variant="primary"
                        size="small"
                        :loading="scheduleForm.processing"
                        @click="saveSchedule"
                    />
                </template>

                <p class="section-description">
                    Set your regular working hours for each day of the week.
                </p>

                <div class="schedule-grid">
                    <div
                        v-for="(day, index) in scheduleForm.schedule"
                        :key="day.day_of_week"
                        class="day-row"
                        :class="{ inactive: !day.is_available }"
                    >
                        <div class="day-toggle">
                            <InputSwitch v-model="day.is_available" />
                        </div>
                        <div class="day-name">{{ dayNames[day.day_of_week] }}</div>
                        <div v-if="day.is_available" class="day-times">
                            <input
                                type="time"
                                v-model="day.start_time"
                                class="time-input"
                            />
                            <span class="time-separator">to</span>
                            <input
                                type="time"
                                v-model="day.end_time"
                                class="time-input"
                            />
                        </div>
                        <div v-else class="day-unavailable">Unavailable</div>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Breaks -->
            <ConsoleFormCard title="Breaks">
                <template #header-actions>
                    <ConsoleButton
                        v-if="breaksForm.isDirty"
                        label="Save"
                        variant="primary"
                        size="small"
                        :loading="breaksForm.processing"
                        @click="saveBreaks"
                        class="mr-2"
                    />
                    <ConsoleButton
                        label="Add Break"
                        icon="pi pi-plus"
                        variant="text"
                        size="small"
                        @click="showAddBreak = true"
                    />
                </template>

                <p class="section-description">
                    Add regular breaks like lunch or personal time. These slots will be blocked for bookings.
                </p>

                <div v-if="breaksForm.breaks.length === 0 && !showAddBreak" class="empty-state">
                    <p>No breaks configured. Add breaks to block off time for lunch or personal activities.</p>
                </div>

                <div v-else class="breaks-list">
                    <div
                        v-for="(breakItem, index) in breaksForm.breaks"
                        :key="index"
                        class="break-item"
                    >
                        <div class="break-day">{{ dayNames[breakItem.day_of_week] }}</div>
                        <div class="break-time">
                            {{ breakItem.start_time }} - {{ breakItem.end_time }}
                        </div>
                        <div class="break-label">{{ breakItem.label || 'Break' }}</div>
                        <ConsoleButton
                            icon="pi pi-trash"
                            variant="text"
                            severity="danger"
                            rounded
                            size="small"
                            @click="removeBreak(index)"
                        />
                    </div>
                </div>

                <!-- Add Break Form -->
                <div v-if="showAddBreak" class="add-break-form">
                    <div class="form-row">
                        <select v-model.number="newBreak.day_of_week" class="day-select">
                            <option v-for="(name, idx) in dayNames" :key="idx" :value="idx">
                                {{ name }}
                            </option>
                        </select>
                        <input type="time" v-model="newBreak.start_time" class="time-input" />
                        <span class="time-separator">to</span>
                        <input type="time" v-model="newBreak.end_time" class="time-input" />
                        <InputText
                            v-model="newBreak.label"
                            placeholder="Label (optional)"
                            class="label-input"
                        />
                    </div>
                    <div class="form-actions">
                        <ConsoleButton
                            label="Cancel"
                            variant="text"
                            severity="secondary"
                            size="small"
                            @click="showAddBreak = false"
                        />
                        <ConsoleButton
                            label="Add"
                            variant="primary"
                            size="small"
                            @click="addBreak"
                        />
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Blocked Dates -->
            <ConsoleFormCard title="Time Off">
                <template #header-actions>
                    <ConsoleButton
                        v-if="blockedDatesForm.isDirty"
                        label="Save"
                        variant="primary"
                        size="small"
                        :loading="blockedDatesForm.processing"
                        @click="saveBlockedDates"
                    />
                </template>

                <p class="section-description">
                    Block specific dates when you won't be available for bookings.
                </p>

                <div class="add-blocked-date">
                    <Calendar
                        v-model="newBlockedDate"
                        :minDate="new Date()"
                        dateFormat="M d, yy"
                        placeholder="Select date"
                        showIcon
                        class="date-picker"
                    />
                    <InputText
                        v-model="newBlockedReason"
                        placeholder="Reason (optional)"
                        class="reason-input"
                    />
                    <ConsoleButton
                        label="Add"
                        icon="pi pi-plus"
                        variant="primary"
                        size="small"
                        :disabled="!newBlockedDate"
                        @click="addBlockedDate"
                    />
                </div>

                <div v-if="blockedDatesForm.blocked_dates.length > 0" class="blocked-dates-list">
                    <div
                        v-for="(blocked, index) in blockedDatesForm.blocked_dates"
                        :key="index"
                        class="blocked-date-item"
                    >
                        <div class="blocked-date">{{ formatDate(blocked.date) }}</div>
                        <div class="blocked-reason">{{ blocked.reason || 'No reason' }}</div>
                        <ConsoleButton
                            icon="pi pi-trash"
                            variant="text"
                            severity="danger"
                            rounded
                            size="small"
                            @click="removeBlockedDate(index)"
                        />
                    </div>
                </div>

                <div v-else class="empty-state">
                    <p>No time off scheduled. Add dates above when you won't be available.</p>
                </div>
            </ConsoleFormCard>

            <!-- Buffer Time -->
            <ConsoleFormCard title="Buffer Time">
                <template #header-actions>
                    <ConsoleButton
                        v-if="bufferForm.isDirty"
                        label="Save"
                        variant="primary"
                        size="small"
                        :loading="bufferForm.processing"
                        @click="saveBuffer"
                    />
                </template>

                <p class="section-description">
                    Add buffer time between appointments for preparation or travel.
                </p>

                <div class="buffer-options">
                    <div
                        v-for="option in bufferOptions"
                        :key="option.value"
                        class="buffer-option"
                        :class="{ selected: bufferForm.buffer_minutes === option.value }"
                        @click="bufferForm.buffer_minutes = option.value"
                    >
                        {{ option.label }}
                    </div>
                </div>
            </ConsoleFormCard>
        </div>

        <ConfirmDialog />
    </SettingsLayout>
</template>

<style scoped>
.availability-page {
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.section-description {
    margin: 0 0 1.25rem;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.5;
}

/* Schedule Grid */
.schedule-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.day-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    transition: opacity 0.15s ease;
}

.day-row.inactive {
    opacity: 0.6;
}

.day-toggle {
    flex-shrink: 0;
}

.day-name {
    width: 100px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.day-times {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.time-input {
    padding: 0.5rem;
    font-size: 0.875rem;
    border: 1px solid var(--color-slate-300, #cbd5e1);
    border-radius: 0.375rem;
    background: white;
}

.time-input:focus {
    outline: none;
    border-color: #106B4F;
}

.time-separator {
    font-size: 0.875rem;
    color: var(--color-slate-400, #94a3b8);
}

.day-unavailable {
    font-size: 0.875rem;
    color: var(--color-slate-400, #94a3b8);
    font-style: italic;
}

/* Breaks */
.breaks-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.break-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.break-day {
    width: 100px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.break-time {
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
    font-family: ui-monospace, monospace;
}

.break-label {
    flex: 1;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.add-break-form {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.day-select {
    padding: 0.5rem;
    font-size: 0.875rem;
    border: 1px solid var(--color-slate-300, #cbd5e1);
    border-radius: 0.375rem;
    background: white;
}

.label-input {
    flex: 1;
    min-width: 120px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* Blocked Dates */
.add-blocked-date {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    margin-bottom: 1rem;
}

.date-picker {
    width: 180px;
}

.reason-input {
    flex: 1;
    min-width: 150px;
}

.blocked-dates-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.blocked-date-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.blocked-date {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.blocked-reason {
    flex: 1;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

/* Buffer Options */
.buffer-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.buffer-option {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    background: var(--color-slate-100, #f1f5f9);
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.buffer-option:hover {
    border-color: var(--color-slate-300, #cbd5e1);
}

.buffer-option.selected {
    background: #106B4F;
    border-color: #106B4F;
    color: white;
}

/* Empty State */
.empty-state {
    padding: 1.5rem;
    text-align: center;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.empty-state p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.mr-2 {
    margin-right: 0.5rem;
}
</style>

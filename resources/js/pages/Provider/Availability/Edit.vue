<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import type { ProviderAvailability, BlockedDate } from '@/types/models';
import InputSwitch from 'primevue/inputswitch';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Calendar from 'primevue/calendar';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';

interface Props {
    weeklySchedule: ProviderAvailability[];
    blockedDates: BlockedDate[];
}

const props = defineProps<Props>();
const page = usePage();

const DAYS = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
];

const timeOptions = generateTimeOptions();

function generateTimeOptions() {
    const options = [];
    for (let hour = 0; hour < 24; hour++) {
        for (let minute of [0, 30]) {
            const time = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            const label = formatTime(time);
            options.push({ label, value: time });
        }
    }
    return options;
}

function formatTime(time: string): string {
    const [hours, minutes] = time.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    return `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`;
}

// Weekly Schedule Form
const scheduleForm = useForm({
    schedule: props.weeklySchedule.map(day => ({
        day_of_week: day.day_of_week,
        start_time: day.start_time?.substring(0, 5) || '09:00',
        end_time: day.end_time?.substring(0, 5) || '17:00',
        is_available: day.is_available,
    })),
});

const submitSchedule = () => {
    scheduleForm.put(route('provider.availability.schedule'));
};

// Blocked Dates Form
const blockedDatesForm = useForm({
    blocked_dates: props.blockedDates.map(blocked => ({
        id: blocked.id,
        date: blocked.date,
        reason: blocked.reason || '',
    })),
});

const showAddDateDialog = ref(false);
const newBlockedDate = ref<Date | null>(null);
const newBlockedReason = ref('');

const addBlockedDate = () => {
    if (newBlockedDate.value) {
        const dateStr = newBlockedDate.value.toISOString().split('T')[0];

        // Check if date already exists
        const exists = blockedDatesForm.blocked_dates.some(d => d.date === dateStr);
        if (!exists) {
            blockedDatesForm.blocked_dates.push({
                id: null,
                date: dateStr,
                reason: newBlockedReason.value,
            });
        }

        newBlockedDate.value = null;
        newBlockedReason.value = '';
        showAddDateDialog.value = false;
    }
};

const removeBlockedDate = (index: number) => {
    blockedDatesForm.blocked_dates.splice(index, 1);
};

const submitBlockedDates = () => {
    blockedDatesForm.put(route('provider.availability.blocked-dates'));
};

const formatDisplayDate = (dateStr: string): string => {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const minDate = computed(() => new Date());

const sortedBlockedDates = computed(() => {
    return [...blockedDatesForm.blocked_dates].sort((a, b) =>
        new Date(a.date).getTime() - new Date(b.date).getTime()
    );
});
</script>

<template>
    <ConsoleLayout title="Availability">
        <div class="availability-page">
            <!-- Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="pi pi-calendar"></i>
                    </div>
                    <div>
                        <h1 class="header-title">Availability</h1>
                        <p class="header-subtitle">Set your working hours and block dates when you're unavailable</p>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <Message
                v-if="page.props.flash?.success"
                severity="success"
                :closable="true"
                class="flash-message"
            >
                {{ page.props.flash.success }}
            </Message>

            <!-- Weekly Schedule Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="pi pi-clock"></i>
                    </div>
                    <div>
                        <h2 class="section-title">Weekly Schedule</h2>
                        <p class="section-desc">Set your regular working hours for each day of the week</p>
                    </div>
                </div>

                <div class="section-content">
                    <div class="schedule-list">
                        <div
                            v-for="(day, index) in scheduleForm.schedule"
                            :key="day.day_of_week"
                            class="schedule-item"
                            :class="{ 'is-disabled': !day.is_available }"
                        >
                            <div class="day-toggle">
                                <InputSwitch v-model="day.is_available" :inputId="`day-${index}`" />
                                <label :for="`day-${index}`" class="day-name">{{ DAYS[day.day_of_week] }}</label>
                            </div>

                            <div class="time-selects" v-if="day.is_available">
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
                            </div>
                            <div v-else class="closed-label">
                                Closed
                            </div>
                        </div>
                    </div>

                    <div class="section-actions">
                        <Button
                            type="button"
                            label="Save Schedule"
                            :loading="scheduleForm.processing"
                            @click="submitSchedule"
                            :disabled="!scheduleForm.isDirty"
                        />
                    </div>
                </div>
            </section>

            <!-- Divider -->
            <div class="section-divider"></div>

            <!-- Blocked Dates Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="pi pi-ban"></i>
                    </div>
                    <div>
                        <h2 class="section-title">Blocked Dates</h2>
                        <p class="section-desc">Mark specific dates when you're unavailable (holidays, vacations, etc.)</p>
                    </div>
                </div>

                <div class="section-content">
                    <div class="blocked-dates-list" v-if="sortedBlockedDates.length > 0">
                        <div
                            v-for="(blocked, index) in sortedBlockedDates"
                            :key="blocked.date"
                            class="blocked-date-item"
                        >
                            <div class="blocked-date-info">
                                <span class="blocked-date-value">{{ formatDisplayDate(blocked.date) }}</span>
                                <span class="blocked-date-reason" v-if="blocked.reason">{{ blocked.reason }}</span>
                            </div>
                            <Button
                                type="button"
                                icon="pi pi-times"
                                severity="danger"
                                text
                                rounded
                                @click="removeBlockedDate(blockedDatesForm.blocked_dates.findIndex(d => d.date === blocked.date))"
                            />
                        </div>
                    </div>

                    <div class="empty-state" v-else>
                        <i class="pi pi-calendar-plus empty-icon"></i>
                        <p class="empty-text">No blocked dates added yet</p>
                    </div>

                    <div class="blocked-dates-actions">
                        <Button
                            type="button"
                            label="Add Blocked Date"
                            icon="pi pi-plus"
                            severity="secondary"
                            outlined
                            @click="showAddDateDialog = true"
                        />
                        <Button
                            type="button"
                            label="Save Changes"
                            :loading="blockedDatesForm.processing"
                            @click="submitBlockedDates"
                            :disabled="!blockedDatesForm.isDirty"
                        />
                    </div>
                </div>
            </section>

            <!-- Add Blocked Date Dialog -->
            <Dialog
                v-model:visible="showAddDateDialog"
                modal
                header="Add Blocked Date"
                :style="{ width: '400px' }"
            >
                <div class="dialog-content">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <Calendar
                            v-model="newBlockedDate"
                            :minDate="minDate"
                            dateFormat="DD, M d, yy"
                            placeholder="Select a date"
                            class="w-full"
                            showIcon
                            iconDisplay="input"
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reason (optional)</label>
                        <InputText
                            v-model="newBlockedReason"
                            placeholder="e.g., Holiday, Vacation, Personal day"
                            class="w-full"
                        />
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        text
                        @click="showAddDateDialog = false"
                    />
                    <Button
                        label="Add Date"
                        @click="addBlockedDate"
                        :disabled="!newBlockedDate"
                    />
                </template>
            </Dialog>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.availability-page {
    max-width: 900px;
    margin: 0 auto;
}

/* Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--p-surface-200);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--p-primary-color), var(--p-primary-400));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.header-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.header-subtitle {
    font-size: 0.875rem;
    color: var(--p-surface-500);
    margin: 0.25rem 0 0 0;
}

/* Flash Message */
.flash-message {
    margin-bottom: 1.5rem;
}

/* Form Sections */
.form-section {
    padding: 0.5rem 0;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    margin-bottom: 1.5rem;
}

.section-icon {
    width: 40px;
    height: 40px;
    background-color: var(--p-surface-100);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--p-surface-600);
    font-size: 1rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0;
}

.section-desc {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
    margin: 0.125rem 0 0 0;
}

.section-content {
    padding-left: 3.375rem;
}

.section-divider {
    height: 1px;
    background-color: var(--p-surface-200);
    margin: 1.5rem 0;
}

@media (max-width: 640px) {
    .section-content {
        padding-left: 0;
    }
}

/* Schedule List */
.schedule-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.schedule-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background-color: var(--p-surface-50);
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    transition: all 0.2s;
}

.schedule-item.is-disabled {
    background-color: var(--p-surface-100);
    opacity: 0.7;
}

.day-toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.day-name {
    font-weight: 500;
    color: var(--p-surface-900);
    min-width: 100px;
    cursor: pointer;
}

.time-selects {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.time-select {
    width: 130px;
}

.time-separator {
    color: var(--p-surface-500);
    font-size: 0.875rem;
}

.closed-label {
    font-size: 0.875rem;
    color: var(--p-surface-400);
    font-style: italic;
}

.section-actions {
    margin-top: 1.5rem;
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 640px) {
    .schedule-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .time-selects {
        width: 100%;
    }

    .time-select {
        flex: 1;
    }
}

/* Blocked Dates List */
.blocked-dates-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.blocked-date-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem;
    background-color: var(--p-surface-50);
    border: 1px solid var(--p-surface-200);
    border-radius: 10px;
}

.blocked-date-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.blocked-date-value {
    font-weight: 500;
    color: var(--p-surface-900);
}

.blocked-date-reason {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background-color: var(--p-surface-50);
    border: 1px dashed var(--p-surface-300);
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.empty-icon {
    font-size: 2rem;
    color: var(--p-surface-400);
    margin-bottom: 0.5rem;
}

.empty-text {
    font-size: 0.875rem;
    color: var(--p-surface-500);
    margin: 0;
}

.blocked-dates-actions {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
}

@media (max-width: 480px) {
    .blocked-dates-actions {
        flex-direction: column;
    }
}

/* Dialog */
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 0.5rem 0;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-700);
}
</style>

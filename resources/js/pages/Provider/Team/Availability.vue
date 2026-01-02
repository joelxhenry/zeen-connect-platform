<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SettingsLayout from '@/components/layout/SettingsLayout.vue';
import ConsoleFormCard from '@/components/console/ConsoleFormCard.vue';
import { ConsoleButton } from '@/components/console';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import InputSwitch from 'primevue/inputswitch';
import Calendar from 'primevue/calendar';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Avatar from 'primevue/avatar';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

interface TeamMember {
    id: number;
    uuid: string;
    name: string;
    email: string;
}

interface DaySchedule {
    day_of_week: number;
    start_time: string | null;
    end_time: string | null;
    is_available: boolean;
}

interface TeamMemberDaySchedule extends DaySchedule {
    use_provider_defaults: boolean;
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
    teamMember: TeamMember;
    providerSchedule: Record<number, DaySchedule>;
    weeklySchedule: TeamMemberDaySchedule[];
    blockedDates: BlockedDate[];
    teamMemberBlockedDates: BlockedDate[];
    breaks: Break[];
}>();

const confirm = useConfirm();

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Schedule form
const scheduleForm = useForm({
    schedule: props.weeklySchedule.map(day => ({
        day_of_week: day.day_of_week,
        use_provider_defaults: day.use_provider_defaults,
        is_available: day.is_available,
        start_time: day.start_time || '09:00',
        end_time: day.end_time || '17:00',
    })),
});

// Blocked dates form (only team member's own blocked dates)
const blockedDatesForm = useForm({
    blocked_dates: props.teamMemberBlockedDates.map(d => ({ ...d })),
});

// Breaks form
const breaksForm = useForm({
    breaks: props.breaks.map(b => ({ ...b })),
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

const getProviderScheduleForDay = (dayOfWeek: number): DaySchedule | null => {
    return props.providerSchedule[dayOfWeek] || null;
};

const getEffectiveScheduleDisplay = (day: typeof scheduleForm.schedule[0]): string => {
    if (day.use_provider_defaults) {
        const providerDay = getProviderScheduleForDay(day.day_of_week);
        if (!providerDay || !providerDay.is_available) {
            return 'Unavailable (provider default)';
        }
        return `${providerDay.start_time} - ${providerDay.end_time} (provider default)`;
    }
    if (!day.is_available) {
        return 'Unavailable';
    }
    return `${day.start_time} - ${day.end_time}`;
};

const saveSchedule = () => {
    scheduleForm.put(resolveUrl(provider.team.availability.schedule.url({ member: props.teamMember.id.toString() })), {
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
    blockedDatesForm.put(resolveUrl(provider.team.availability.blockedDates.url({ member: props.teamMember.id.toString() })), {
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
    breaksForm.put(resolveUrl(provider.team.availability.breaks.url({ member: props.teamMember.id.toString() })), {
        preserveScroll: true,
        onSuccess: () => {
            breaksForm.defaults({
                breaks: breaksForm.breaks.map(b => ({ ...b })),
            });
            breaksForm.reset();
        },
    });
};

const resetToDefaults = () => {
    confirm.require({
        message: `Reset ${props.teamMember.name}'s schedule to use provider defaults for all days?`,
        header: 'Reset to Defaults',
        icon: 'pi pi-refresh',
        rejectClass: 'p-button-secondary p-button-text',
        acceptClass: 'p-button-primary',
        acceptLabel: 'Reset',
        rejectLabel: 'Cancel',
        accept: () => {
            router.post(resolveUrl(provider.team.availability.reset.url({ member: props.teamMember.id.toString() })), {}, {
                preserveScroll: true,
            });
        },
    });
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

const goBack = () => {
    router.visit(resolveUrl(provider.team.index.url()));
};
</script>

<template>
    <SettingsLayout :title="`${teamMember.name}'s Schedule`">
        <div class="availability-page">
            <!-- Team Member Header -->
            <ConsoleFormCard>
                <div class="member-header">
                    <Avatar
                        :label="teamMember.name.charAt(0).toUpperCase()"
                        shape="circle"
                        size="large"
                        class="member-avatar"
                    />
                    <div class="member-info">
                        <h2 class="member-name">{{ teamMember.name }}</h2>
                        <p class="member-email">{{ teamMember.email }}</p>
                    </div>
                    <div class="header-actions">
                        <ConsoleButton
                            label="Reset to Defaults"
                            icon="pi pi-refresh"
                            variant="text"
                            severity="secondary"
                            size="small"
                            @click="resetToDefaults"
                        />
                        <ConsoleButton
                            label="Back to Team"
                            icon="pi pi-arrow-left"
                            variant="text"
                            severity="secondary"
                            size="small"
                            @click="goBack"
                        />
                    </div>
                </div>
            </ConsoleFormCard>

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
                    Set custom working hours or use provider defaults. Unchecking "Use Default" lets you customize this day.
                </p>

                <div class="schedule-grid">
                    <div
                        v-for="(day, index) in scheduleForm.schedule"
                        :key="day.day_of_week"
                        class="day-row"
                        :class="{ inactive: day.use_provider_defaults ? !getProviderScheduleForDay(day.day_of_week)?.is_available : !day.is_available }"
                    >
                        <div class="day-name">{{ dayNames[day.day_of_week] }}</div>

                        <div class="day-default-toggle">
                            <Checkbox
                                v-model="day.use_provider_defaults"
                                :inputId="'default_' + day.day_of_week"
                                :binary="true"
                            />
                            <label :for="'default_' + day.day_of_week" class="default-label">Use Default</label>
                        </div>

                        <template v-if="day.use_provider_defaults">
                            <div class="day-times provider-default">
                                {{ getEffectiveScheduleDisplay(day) }}
                            </div>
                        </template>
                        <template v-else>
                            <div class="day-toggle">
                                <InputSwitch v-model="day.is_available" />
                            </div>
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
                        </template>
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
                    Add regular breaks for this team member. These slots will be blocked for bookings.
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
                    Block specific dates when this team member won't be available. Provider-wide blocked dates are inherited automatically.
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
                    <p>No personal time off scheduled. Add dates above when this team member won't be available.</p>
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

/* Member Header */
.member-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.member-avatar {
    flex-shrink: 0;
    background: #106B4F;
    color: white;
}

.member-info {
    flex: 1;
    min-width: 150px;
}

.member-name {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.member-email {
    margin: 0.25rem 0 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.header-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
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
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    transition: opacity 0.15s ease;
    flex-wrap: wrap;
}

.day-row.inactive {
    opacity: 0.6;
}

.day-name {
    width: 90px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
    flex-shrink: 0;
}

.day-default-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.default-label {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
    cursor: pointer;
    white-space: nowrap;
}

.day-toggle {
    flex-shrink: 0;
}

.day-times {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.day-times.provider-default {
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
    font-style: italic;
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
    flex-wrap: wrap;
}

.break-day {
    width: 90px;
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
    flex-wrap: wrap;
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

/* Responsive */
@media (max-width: 640px) {
    .day-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .day-name {
        width: auto;
    }

    .day-times {
        width: 100%;
    }

    .header-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

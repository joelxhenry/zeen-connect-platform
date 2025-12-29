<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleEmptyState,
    ConsoleButton,
} from '@/components/console';
import InputSwitch from 'primevue/inputswitch';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { BreaksManager } from '@/components/availability';
import type { Break } from '@/components/availability';
import TeamMemberAvailability from '@/actions/App/Domains/Provider/Controllers/TeamMemberAvailabilityController';
import TeamMemberController from '@/actions/App/Domains/Provider/Controllers/TeamMemberController';

interface ScheduleDay {
    day_of_week: number;
    start_time: string;
    end_time: string;
    is_available: boolean;
}

interface TeamMemberScheduleDay extends ScheduleDay {
    use_provider_defaults: boolean;
}

interface BlockedDate {
    id?: number;
    date: string;
    reason: string | null;
}

interface Props {
    teamMember: {
        id: number;
        uuid: string;
        name: string;
        email: string;
    };
    providerSchedule: Record<number, ScheduleDay>;
    weeklySchedule: TeamMemberScheduleDay[];
    blockedDates: BlockedDate[];
    teamMemberBlockedDates: BlockedDate[];
    breaks: Break[];
}

const props = defineProps<Props>();
const toast = useToast();
const confirm = useConfirm();

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

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

const formatDate = (dateStr: string): string => {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

// Get provider schedule for a specific day
const getProviderSchedule = (dayOfWeek: number): ScheduleDay | null => {
    return props.providerSchedule[dayOfWeek] ?? null;
};

// Schedule form
const scheduleForm = useForm({
    schedule: props.weeklySchedule.map(day => ({
        day_of_week: day.day_of_week,
        use_provider_defaults: day.use_provider_defaults,
        is_available: day.is_available,
        start_time: day.start_time,
        end_time: day.end_time,
    })),
});

// Breaks form
const breaksForm = useForm({
    breaks: props.breaks.map(b => ({
        day_of_week: b.day_of_week,
        start_time: b.start_time,
        end_time: b.end_time,
        label: b.label,
    })),
});

// Blocked dates form
const blockedDatesForm = useForm({
    blocked_dates: props.teamMemberBlockedDates.map(bd => ({
        date: bd.date,
        reason: bd.reason,
    })),
});

const newBlockedDate = ref<Date | null>(null);
const newBlockedReason = ref('');

// Save functions
const saveSchedule = () => {
    scheduleForm.put(TeamMemberAvailability.updateSchedule(props.teamMember).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Schedule updated successfully',
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

const updateBreaks = (breaks: Break[]) => {
    breaksForm.breaks = breaks;
};

const saveBreaks = () => {
    breaksForm.put(TeamMemberAvailability.updateBreaks(props.teamMember).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Breaks updated successfully',
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

const saveBlockedDates = () => {
    blockedDatesForm.put(TeamMemberAvailability.updateBlockedDates(props.teamMember).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Blocked dates updated successfully',
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
            summary: 'Duplicate',
            detail: 'This date is already blocked',
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

const resetToDefaults = () => {
    confirm.require({
        message: 'Are you sure you want to reset this team member\'s schedule to use provider defaults?',
        header: 'Reset to Defaults',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(TeamMemberAvailability.reset(props.teamMember).url, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Schedule reset to provider defaults',
                        life: 3000,
                    });
                },
                onError: () => {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: 'Failed to reset schedule',
                        life: 3000,
                    });
                },
            });
        },
    });
};
</script>

<template>
    <ConsoleLayout title="Team Member Availability">
        <div class="w-full max-w-7xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                :title="`${teamMember.name}'s Availability`"
                subtitle="Manage this team member's schedule, breaks, and blocked dates"
                :back-link="TeamMemberController.index().url"
                back-label="Back to Team"
            >
                <template #actions>
                    <ConsoleButton
                        label="Reset to Defaults"
                        icon="pi pi-refresh"
                        severity="secondary"
                        @click="resetToDefaults"
                    />
                </template>
            </ConsolePageHeader>

            <!-- Member Info Banner -->
            <div class="member-info-banner">
                <div class="member-details">
                    <span class="member-name">{{ teamMember.name }}</span>
                    <span class="member-email">{{ teamMember.email }}</span>
                </div>
            </div>

            <!-- Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6 mt-4">
                <!-- Weekly Schedule Card -->
                <ConsoleFormCard title="Weekly Schedule" icon="pi pi-calendar">
                    <template #header-actions>
                        <ConsoleButton
                            label="Save"
                            icon="pi pi-check"
                            size="small"
                            :loading="scheduleForm.processing"
                            @click="saveSchedule"
                        />
                    </template>
                    <div class="space-y-3">
                        <div v-for="day in scheduleForm.schedule" :key="day.day_of_week"
                            class="schedule-day">
                            <div class="schedule-day-header">
                                <span class="day-name">{{ dayNames[day.day_of_week] }}</span>
                                <div class="use-defaults-toggle">
                                    <label class="text-xs text-gray-500">Use Provider</label>
                                    <InputSwitch v-model="day.use_provider_defaults" />
                                </div>
                            </div>

                            <!-- Provider defaults display -->
                            <div v-if="day.use_provider_defaults" class="provider-schedule">
                                <template v-if="getProviderSchedule(day.day_of_week)?.is_available">
                                    <span class="time-display">
                                        {{ formatTime(getProviderSchedule(day.day_of_week)!.start_time) }}
                                        -
                                        {{ formatTime(getProviderSchedule(day.day_of_week)!.end_time) }}
                                    </span>
                                    <Tag value="Provider Schedule" severity="secondary" />
                                </template>
                                <template v-else>
                                    <span class="text-gray-400 text-sm">Closed (Provider)</span>
                                </template>
                            </div>

                            <!-- Custom schedule -->
                            <div v-else class="custom-schedule">
                                <div class="flex items-center gap-3 mb-2">
                                    <InputSwitch v-model="day.is_available" />
                                    <span class="text-sm"
                                        :class="day.is_available ? 'text-[#0D1F1B]' : 'text-gray-400'">
                                        {{ day.is_available ? 'Available' : 'Unavailable' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2"
                                    :class="{ 'opacity-50 pointer-events-none': !day.is_available }">
                                    <Select v-model="day.start_time" :options="timeOptions" optionLabel="label"
                                        optionValue="value" placeholder="Start" class="flex-1" />
                                    <span class="text-gray-400 text-sm">to</span>
                                    <Select v-model="day.end_time" :options="timeOptions" optionLabel="label"
                                        optionValue="value" placeholder="End" class="flex-1" />
                                </div>
                            </div>
                        </div>

                        <small v-if="scheduleForm.errors.schedule" class="text-red-500 text-xs">
                            {{ scheduleForm.errors.schedule }}
                        </small>
                    </div>
                </ConsoleFormCard>

                <!-- Breaks Card -->
                <ConsoleFormCard title="Breaks" icon="pi pi-clock">
                    <template #header-actions>
                        <ConsoleButton
                            label="Save"
                            icon="pi pi-check"
                            size="small"
                            :loading="breaksForm.processing"
                            @click="saveBreaks"
                        />
                    </template>
                    <BreaksManager
                        :breaks="breaksForm.breaks"
                        :processing="breaksForm.processing"
                        @update:breaks="updateBreaks"
                    >
                        <template #actions><!-- Hide internal save button --></template>
                    </BreaksManager>

                    <small v-if="breaksForm.errors.breaks" class="text-red-500 text-xs mt-2 block">
                        {{ breaksForm.errors.breaks }}
                    </small>
                </ConsoleFormCard>

                <!-- Blocked Dates Card -->
                <ConsoleFormCard title="Blocked Dates" icon="pi pi-ban" icon-color="danger">
                    <template #header-actions>
                        <ConsoleButton
                            label="Save"
                            icon="pi pi-check"
                            size="small"
                            :loading="blockedDatesForm.processing"
                            @click="saveBlockedDates"
                        />
                    </template>
                    <!-- Add New Blocked Date -->
                    <div class="flex flex-col sm:flex-row gap-3 mb-4 pb-4 border-b border-gray-100">
                        <DatePicker v-model="newBlockedDate" :minDate="new Date()" placeholder="Select date"
                            dateFormat="M dd, yy" showIcon class="flex-1" />
                        <InputText v-model="newBlockedReason" placeholder="Reason (optional)" class="flex-1" />
                        <Button icon="pi pi-plus" @click="addBlockedDate" :disabled="!newBlockedDate"
                            v-tooltip="'Add Blocked Date'" severity="secondary" />
                    </div>

                    <!-- Provider Blocked Dates (Read-only) -->
                    <div v-if="blockedDates.length > 0" class="mb-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Provider Blocked</div>
                        <div class="space-y-1">
                            <div v-for="blocked in blockedDates" :key="blocked.date"
                                class="text-sm text-gray-500 flex items-center gap-2">
                                <i class="pi pi-lock text-xs"></i>
                                {{ formatDate(blocked.date) }}
                                <span v-if="blocked.reason" class="text-xs">({{ blocked.reason }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Team Member Blocked Dates -->
                    <ConsoleEmptyState
                        v-if="blockedDatesForm.blocked_dates.length === 0"
                        icon="pi pi-calendar-times"
                        title="No blocked dates"
                        description="Add dates when this team member is unavailable"
                        size="compact"
                    />
                    <div v-else class="space-y-2 max-h-[200px] overflow-y-auto">
                        <div v-for="(blocked, index) in blockedDatesForm.blocked_dates" :key="index"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg group">
                            <div class="flex-1 min-w-0">
                                <span class="block font-medium text-sm text-[#0D1F1B]">
                                    {{ formatDate(blocked.date) }}
                                </span>
                                <span v-if="blocked.reason" class="block text-xs text-gray-500 truncate">
                                    {{ blocked.reason }}
                                </span>
                            </div>
                            <Button icon="pi pi-times" severity="danger" text rounded size="small"
                                @click="removeBlockedDate(index)"
                                class="opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                    </div>

                    <small v-if="blockedDatesForm.errors.blocked_dates" class="text-red-500 text-xs mt-2 block">
                        {{ blockedDatesForm.errors.blocked_dates }}
                    </small>
                </ConsoleFormCard>
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.member-info-banner {
    background: linear-gradient(135deg, #0D1F1B 0%, #1a3a33 100%);
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.member-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.member-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: white;
}

.member-email {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
}

.schedule-day {
    padding: 0.75rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
}

.schedule-day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.day-name {
    font-weight: 500;
    font-size: 0.875rem;
    color: #0D1F1B;
}

.use-defaults-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.provider-schedule {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.time-display {
    font-size: 0.875rem;
    color: #6b7280;
}

.custom-schedule {
    padding-top: 0.25rem;
}

@media (max-width: 640px) {
    .schedule-day-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

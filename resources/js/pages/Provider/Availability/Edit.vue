<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import InputSwitch from 'primevue/inputswitch';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import AvailabilityController from '@/actions/App/Domains/Provider/Controllers/AvailabilityController';

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
}

const props = defineProps<Props>();
const toast = useToast();

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

const newBlockedDate = ref<Date | null>(null);
const newBlockedReason = ref('');

const saveSchedule = () => {
    scheduleForm.put(AvailabilityController.updateSchedule().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Weekly schedule updated successfully',
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
</script>

<template>
    <ConsoleLayout title="Availability">
        <div class="w-full max-w-7xl mx-auto">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-xl lg:text-2xl font-semibold text-[#0D1F1B] m-0 mb-1">
                    Manage Availability
                </h1>
                <p class="text-gray-500 m-0 text-sm lg:text-base">
                    Set your working hours and block specific dates when you're unavailable
                </p>
            </div>

            <!-- Two Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                <!-- Weekly Schedule Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="flex justify-between items-center px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                        <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                            <i class="pi pi-calendar text-[#106B4F]"></i>
                            Weekly Schedule
                        </h2>
                        <Button label="Save" icon="pi pi-check" size="small" :loading="scheduleForm.processing"
                            @click="saveSchedule" class="!bg-[#106B4F] !border-[#106B4F]" />
                    </div>
                    <div class="p-4 lg:p-5 space-y-3">
                        <div v-for="day in scheduleForm.schedule" :key="day.day_of_week"
                            class="flex flex-col sm:flex-row sm:items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <InputSwitch v-model="day.is_available" />
                                <span class="font-medium text-sm"
                                    :class="day.is_available ? 'text-[#0D1F1B]' : 'text-gray-400'">
                                    {{ dayNames[day.day_of_week] }}
                                </span>
                            </div>

                            <div class="flex flex-1 items-center gap-2"
                                :class="{ 'opacity-50 pointer-events-none': !day.is_available }">
                                <Select v-model="day.start_time" :options="timeOptions" optionLabel="label"
                                    optionValue="value" placeholder="Start" class="flex-1" />
                                <span class="text-gray-400 text-sm">to</span>
                                <Select v-model="day.end_time" :options="timeOptions" optionLabel="label"
                                    optionValue="value" placeholder="End" class="flex-1" />
                            </div>
                        </div>

                        <small v-if="scheduleForm.errors.schedule" class="text-red-500 text-xs">
                            {{ scheduleForm.errors.schedule }}
                        </small>
                    </div>
                </div>

                <!-- Blocked Dates Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="flex justify-between items-center px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                        <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                            <i class="pi pi-ban text-red-500"></i>
                            Blocked Dates
                        </h2>
                        <Button label="Save" icon="pi pi-check" size="small" :loading="blockedDatesForm.processing"
                            @click="saveBlockedDates" class="!bg-[#106B4F] !border-[#106B4F]" />
                    </div>
                    <div class="p-4 lg:p-5">
                        <!-- Add New Blocked Date -->
                        <div class="flex flex-col sm:flex-row gap-3 mb-4 pb-4 border-b border-gray-100">
                            <DatePicker v-model="newBlockedDate" :minDate="new Date()" placeholder="Select date"
                                dateFormat="M dd, yy" showIcon class="flex-1" />
                            <InputText v-model="newBlockedReason" placeholder="Reason (optional)" class="flex-1" />
                            <Button icon="pi pi-plus" @click="addBlockedDate" :disabled="!newBlockedDate"
                                v-tooltip="'Add Blocked Date'" severity="secondary" />
                        </div>

                        <!-- Blocked Dates List -->
                        <div v-if="blockedDatesForm.blocked_dates.length === 0" class="text-center py-8 text-gray-400">
                            <i class="pi pi-calendar-times text-3xl mb-2 block"></i>
                            <p class="m-0 text-sm">No blocked dates</p>
                        </div>
                        <div v-else class="space-y-2 max-h-[300px] overflow-y-auto">
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
                    </div>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

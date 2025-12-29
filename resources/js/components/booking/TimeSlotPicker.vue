<script setup lang="ts">
interface Slot {
    start_time: string;
    end_time: string;
    display: string;
}

interface Props {
    slots: Slot[];
    modelValue: Slot | null;
    loading?: boolean;
    hasDate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    hasDate: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: Slot | null];
}>();

const selectSlot = (slot: Slot) => {
    emit('update:modelValue', slot);
};

const isSelected = (slot: Slot) => {
    return props.modelValue?.start_time === slot.start_time;
};

const formatSlotTime = (slot: Slot) => {
    const [hours, minutes] = slot.start_time.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};
</script>

<template>
    <div class="time-slot-picker">
        <label class="time-slot-picker__label">Available Times</label>

        <!-- No date selected -->
        <div v-if="!hasDate" class="time-slot-picker__empty">
            <i class="pi pi-calendar"></i>
            <p>Select a date to see available times</p>
        </div>

        <!-- Loading state -->
        <div v-else-if="loading" class="time-slot-picker__loading">
            <i class="pi pi-spinner pi-spin"></i>
        </div>

        <!-- No slots available -->
        <div v-else-if="slots.length === 0" class="time-slot-picker__empty">
            <i class="pi pi-times-circle"></i>
            <p>No available times for this date</p>
        </div>

        <!-- Slots grid -->
        <div v-else class="time-slot-picker__grid">
            <button v-for="slot in slots" :key="slot.start_time" type="button" class="time-slot-picker__slot"
                :class="{ 'time-slot-picker__slot--selected': isSelected(slot) }" @click="selectSlot(slot)">
                {{ formatSlotTime(slot) }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.time-slot-picker__label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.time-slot-picker__empty {
    text-align: center;
    padding: 2rem;
    color: #9ca3af;
}

.time-slot-picker__empty i {
    font-size: 1.875rem;
    margin-bottom: 0.5rem;
    display: block;
}

.time-slot-picker__empty p {
    margin: 0;
    font-size: 0.875rem;
}

.time-slot-picker__loading {
    text-align: center;
    padding: 2rem;
}

.time-slot-picker__loading i {
    font-size: 1.5rem;
    color: var(--provider-primary);
}

.time-slot-picker__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
}

.time-slot-picker__slot {
    padding: 0.5rem 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: white;
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
    transition: all 0.15s;
}

.time-slot-picker__slot:hover {
    border-color: var(--provider-primary);
}

.time-slot-picker__slot--selected {
    background-color: var(--provider-primary);
    border-color: var(--provider-primary);
    color: white;
}
</style>

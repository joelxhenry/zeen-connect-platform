<script setup lang="ts">
import { computed } from 'vue';
import Select from 'primevue/select';
import Button from 'primevue/button';

interface Props {
    modelValue: number;
    processing?: boolean;
    showSaveButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    processing: false,
    showSaveButton: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: number];
    save: [];
}>();

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

const selectedValue = computed({
    get: () => props.modelValue,
    set: (value: number) => emit('update:modelValue', value),
});

const handleSave = () => {
    emit('save');
};
</script>

<template>
    <div class="buffer-selector">
        <div class="buffer-controls">
            <Select
                v-model="selectedValue"
                :options="bufferOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select buffer time"
                class="buffer-select"
            />
            <Button
                v-if="showSaveButton"
                label="Save"
                icon="pi pi-check"
                :loading="processing"
                @click="handleSave"
                size="small"
            />
        </div>
        <p class="buffer-help">
            Buffer time is added between appointments to allow for preparation, cleanup, or transition time.
        </p>
    </div>
</template>

<style scoped>
.buffer-selector {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.buffer-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.buffer-select {
    flex: 1;
    max-width: 200px;
}

.buffer-help {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
}

@media (max-width: 640px) {
    .buffer-controls {
        flex-direction: column;
        align-items: stretch;
    }

    .buffer-select {
        max-width: none;
    }
}
</style>

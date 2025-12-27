<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    title?: string;
    description?: string;
    columns?: 1 | 2;
    highlighted?: boolean;
    error?: string;
}

const props = withDefaults(defineProps<Props>(), {
    columns: 1,
    highlighted: false,
});

const containerClasses = computed(() => {
    const classes = [];

    if (props.highlighted) {
        classes.push('p-3 lg:p-4 bg-gray-50 rounded-lg');
    }

    return classes.join(' ');
});

const gridClasses = computed(() => {
    if (props.columns === 2) {
        return 'grid grid-cols-1 sm:grid-cols-2 gap-4';
    }
    return 'space-y-4';
});
</script>

<template>
    <div :class="containerClasses">
        <!-- Section header -->
        <div v-if="title || description" class="mb-4">
            <h3 v-if="title" class="text-sm font-medium text-gray-700 m-0">
                {{ title }}
            </h3>
            <p v-if="description" class="text-xs text-gray-500 m-0 mt-1">
                {{ description }}
            </p>
        </div>

        <!-- Section content -->
        <div :class="gridClasses">
            <slot />
        </div>

        <!-- Section error -->
        <p v-if="error" class="text-sm text-red-500 m-0 mt-3">
            {{ error }}
        </p>
    </div>
</template>

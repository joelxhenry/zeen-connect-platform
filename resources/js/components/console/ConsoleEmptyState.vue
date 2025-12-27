<script setup lang="ts">
import ConsoleButton from './ConsoleButton.vue';

interface Props {
    icon: string;
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
    actionIcon?: string;
    size?: 'default' | 'compact';
}

withDefaults(defineProps<Props>(), {
    size: 'default',
});
</script>

<template>
    <div
        class="text-center"
        :class="size === 'compact' ? 'py-6' : 'py-8 lg:py-12'"
    >
        <!-- Icon -->
        <div
            class="mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4"
            :class="size === 'compact' ? 'w-12 h-12' : 'w-16 h-16 lg:w-20 lg:h-20'"
        >
            <i
                :class="[icon, 'text-gray-400']"
                :style="{ fontSize: size === 'compact' ? '1.25rem' : '1.75rem' }"
            />
        </div>

        <!-- Title -->
        <h3
            class="font-semibold text-[#0D1F1B] m-0"
            :class="size === 'compact' ? 'text-base' : 'text-lg lg:text-xl'"
        >
            {{ title }}
        </h3>

        <!-- Description -->
        <p
            class="text-gray-500 max-w-md mx-auto m-0 mt-2"
            :class="size === 'compact' ? 'text-sm' : 'text-sm lg:text-base'"
        >
            {{ description }}
        </p>

        <!-- Action button -->
        <div v-if="actionLabel" class="mt-5">
            <ConsoleButton
                :label="actionLabel"
                :href="actionHref"
                :icon="actionIcon"
                variant="primary"
                :size="size === 'compact' ? 'small' : 'default'"
            />
        </div>
    </div>
</template>

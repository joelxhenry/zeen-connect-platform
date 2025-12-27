<script setup lang="ts">
import { computed } from 'vue';
import AppLink from '@/components/common/AppLink.vue';

interface Props {
    hoverable?: boolean;
    clickable?: boolean;
    href?: string;
    selected?: boolean;
    noPadding?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    hoverable: true,
    clickable: false,
    selected: false,
    noPadding: false,
});

const containerClasses = computed(() => {
    const classes = [
        'bg-white rounded-xl overflow-hidden',
        'transition-all duration-[var(--transition-normal)]',
    ];

    // Shadow and hover
    if (props.hoverable || props.clickable || props.href) {
        classes.push('shadow-[var(--shadow-sm)] hover:shadow-[var(--shadow-md)]');
    } else {
        classes.push('shadow-[var(--shadow-sm)]');
    }

    // Clickable/link styling
    if (props.clickable || props.href) {
        classes.push('cursor-pointer');
    }

    // Selected state
    if (props.selected) {
        classes.push('ring-2 ring-[#106B4F] ring-offset-2');
    }

    // Padding
    if (!props.noPadding) {
        classes.push('p-4 lg:p-5');
    }

    return classes.join(' ');
});
</script>

<template>
    <component
        :is="href ? AppLink : 'div'"
        :href="href"
        :class="containerClasses"
    >
        <slot />

        <!-- Footer slot -->
        <div
            v-if="$slots.footer"
            class="border-t border-gray-100 pt-3 mt-3"
            :class="{ 'px-4 lg:px-5 pb-4 lg:pb-5 -mx-4 lg:-mx-5 -mb-4 lg:-mb-5': !noPadding }"
        >
            <slot name="footer" />
        </div>
    </component>
</template>

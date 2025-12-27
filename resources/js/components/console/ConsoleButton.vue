<script setup lang="ts">
import { computed } from 'vue';
import AppLink from '@/components/common/AppLink.vue';

interface Props {
    label?: string;
    icon?: string;
    iconPos?: 'left' | 'right';
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'success';
    size?: 'small' | 'default' | 'large';
    loading?: boolean;
    disabled?: boolean;
    outlined?: boolean;
    href?: string;
    type?: 'button' | 'submit';
}

const props = withDefaults(defineProps<Props>(), {
    iconPos: 'left',
    variant: 'primary',
    size: 'default',
    loading: false,
    disabled: false,
    outlined: false,
    type: 'button',
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const baseClasses = computed(() => {
    const classes = [
        'inline-flex items-center justify-center gap-2',
        'font-medium rounded-lg',
        'transition-all duration-[var(--transition-normal)]',
        'focus:outline-none focus:ring-2 focus:ring-offset-2',
        'disabled:opacity-50 disabled:cursor-not-allowed',
    ];

    // Size classes
    switch (props.size) {
        case 'small':
            classes.push('px-3 py-1.5 text-sm');
            break;
        case 'large':
            classes.push('px-6 py-3 text-base');
            break;
        default:
            classes.push('px-4 py-2 text-sm');
    }

    // Variant classes
    if (props.outlined) {
        switch (props.variant) {
            case 'primary':
                classes.push(
                    'border-2 border-[#106B4F] text-[#106B4F] bg-transparent',
                    'hover:bg-[#106B4F] hover:text-white',
                    'focus:ring-[#106B4F]/50'
                );
                break;
            case 'secondary':
                classes.push(
                    'border border-gray-300 text-gray-700 bg-transparent',
                    'hover:bg-gray-50 hover:border-gray-400',
                    'focus:ring-gray-500/30'
                );
                break;
            case 'danger':
                classes.push(
                    'border-2 border-red-500 text-red-500 bg-transparent',
                    'hover:bg-red-500 hover:text-white',
                    'focus:ring-red-500/50'
                );
                break;
            case 'success':
                classes.push(
                    'border-2 border-[#1ABC9C] text-[#1ABC9C] bg-transparent',
                    'hover:bg-[#1ABC9C] hover:text-white',
                    'focus:ring-[#1ABC9C]/50'
                );
                break;
            case 'ghost':
                classes.push(
                    'border border-transparent text-gray-600',
                    'hover:bg-gray-100',
                    'focus:ring-gray-500/30'
                );
                break;
        }
    } else {
        switch (props.variant) {
            case 'primary':
                classes.push(
                    'bg-[#106B4F] text-white border border-[#106B4F]',
                    'hover:bg-[#0D5A42] hover:border-[#0D5A42]',
                    'focus:ring-[#106B4F]/50',
                    'active:scale-[0.98]'
                );
                break;
            case 'secondary':
                classes.push(
                    'bg-gray-100 text-gray-700 border border-gray-100',
                    'hover:bg-gray-200 hover:border-gray-200',
                    'focus:ring-gray-500/30'
                );
                break;
            case 'danger':
                classes.push(
                    'bg-red-500 text-white border border-red-500',
                    'hover:bg-red-600 hover:border-red-600',
                    'focus:ring-red-500/50'
                );
                break;
            case 'success':
                classes.push(
                    'bg-[#1ABC9C] text-white border border-[#1ABC9C]',
                    'hover:bg-[#16A085] hover:border-[#16A085]',
                    'focus:ring-[#1ABC9C]/50'
                );
                break;
            case 'ghost':
                classes.push(
                    'bg-transparent text-gray-600 border border-transparent',
                    'hover:bg-gray-100',
                    'focus:ring-gray-500/30'
                );
                break;
        }
    }

    return classes.join(' ');
});

const handleClick = (event: MouseEvent) => {
    if (!props.disabled && !props.loading) {
        emit('click', event);
    }
};
</script>

<template>
    <component
        :is="href ? AppLink : 'button'"
        :href="href"
        :type="!href ? type : undefined"
        :disabled="disabled || loading"
        :class="baseClasses"
        @click="handleClick"
    >
        <!-- Loading spinner -->
        <i v-if="loading" class="pi pi-spinner pi-spin" />

        <!-- Left icon -->
        <i v-else-if="icon && iconPos === 'left'" :class="icon" />

        <!-- Label -->
        <span v-if="label">{{ label }}</span>
        <slot v-else />

        <!-- Right icon -->
        <i v-if="icon && iconPos === 'right' && !loading" :class="icon" />
    </component>
</template>

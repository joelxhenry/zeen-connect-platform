<script setup lang="ts">
import { computed } from 'vue';
import AppLink from '@/components/common/AppLink.vue';

interface Props {
    label?: string;
    icon?: string;
    href?: string;
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'text' | 'outlined';
    size?: 'small' | 'default' | 'large';
    disabled?: boolean;
    loading?: boolean;
    type?: 'button' | 'submit';
    rounded?: boolean;
    iconOnly?: boolean;
    severity?: 'danger' | 'secondary';
    iconPos?: 'left' | 'right';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'secondary',
    size: 'default',
    disabled: false,
    loading: false,
    type: 'button',
    rounded: false,
    iconOnly: false,
    iconPos: 'left',
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

// Auto-detect icon-only mode when no label provided
const isIconOnly = computed(() => props.iconOnly || (!props.label && props.icon));

const buttonClasses = computed(() => [
    'console-btn',
    `variant-${props.variant}`,
    `size-${props.size}`,
    {
        'is-disabled': props.disabled,
        'is-loading': props.loading,
        'is-rounded': props.rounded,
        'is-icon-only': isIconOnly.value,
        'icon-right': props.iconPos === 'right',
        [`severity-${props.severity}`]: props.severity,
    },
]);

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
        :class="buttonClasses"
        :disabled="disabled || loading"
        @click="handleClick"
    >
        <!-- Icon left (default) -->
        <template v-if="iconPos === 'left'">
            <i v-if="loading" class="pi pi-spin pi-spinner btn-icon"></i>
            <i v-else-if="icon" :class="icon" class="btn-icon"></i>
        </template>

        <!-- Label (hidden for icon-only) -->
        <span v-if="label && !isIconOnly" class="btn-label">{{ label }}</span>

        <!-- Icon right -->
        <template v-if="iconPos === 'right'">
            <i v-if="loading" class="pi pi-spin pi-spinner btn-icon"></i>
            <i v-else-if="icon" :class="icon" class="btn-icon"></i>
        </template>
    </component>
</template>

<style scoped>
.console-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-weight: 500;
    border-radius: 0.5rem;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
    white-space: nowrap;
}

/* Sizes */
.size-small {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}

.size-default {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.size-large {
    padding: 0.625rem 1.25rem;
    font-size: 0.9375rem;
}

/* Variants */
.variant-primary {
    background-color: #106B4F;
    color: white;
    border-color: #106B4F;
}

.variant-primary:hover:not(.is-disabled) {
    background-color: #0d5a42;
    border-color: #0d5a42;
}

.variant-secondary {
    background-color: white;
    color: var(--color-slate-700, #334155);
    border-color: var(--color-slate-200, #e2e8f0);
}

.variant-secondary:hover:not(.is-disabled) {
    background-color: var(--color-slate-50, #f8fafc);
    border-color: var(--color-slate-300, #cbd5e1);
}

.variant-danger {
    background-color: #DC2626;
    color: white;
    border-color: #DC2626;
}

.variant-danger:hover:not(.is-disabled) {
    background-color: #B91C1C;
    border-color: #B91C1C;
}

.variant-ghost {
    background-color: transparent;
    color: var(--color-slate-600, #475569);
    border-color: transparent;
}

.variant-ghost:hover:not(.is-disabled) {
    background-color: var(--color-slate-100, #f1f5f9);
}

.variant-text {
    background-color: transparent;
    color: #106B4F;
    border-color: transparent;
}

.variant-text:hover:not(.is-disabled) {
    background-color: rgba(16, 107, 79, 0.08);
}

.variant-text.severity-danger {
    color: #DC2626;
}

.variant-text.severity-danger:hover:not(.is-disabled) {
    background-color: rgba(220, 38, 38, 0.08);
}

.variant-text.severity-secondary {
    color: var(--color-slate-600, #475569);
}

.variant-text.severity-secondary:hover:not(.is-disabled) {
    background-color: var(--color-slate-100, #f1f5f9);
}

.variant-outlined {
    background-color: transparent;
    color: #106B4F;
    border-color: #106B4F;
}

.variant-outlined:hover:not(.is-disabled) {
    background-color: rgba(16, 107, 79, 0.08);
}

.variant-outlined.severity-danger {
    color: #DC2626;
    border-color: #DC2626;
}

.variant-outlined.severity-danger:hover:not(.is-disabled) {
    background-color: rgba(220, 38, 38, 0.08);
}

.variant-outlined.severity-secondary {
    color: var(--color-slate-600, #475569);
    border-color: var(--color-slate-200, #e2e8f0);
}

.variant-outlined.severity-secondary:hover:not(.is-disabled) {
    background-color: var(--color-slate-50, #f8fafc);
    border-color: var(--color-slate-300, #cbd5e1);
}

/* States */
.is-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.is-loading {
    cursor: wait;
}

/* Rounded modifier */
.is-rounded {
    border-radius: 9999px;
}

/* Icon-only modifier */
.is-icon-only {
    aspect-ratio: 1;
    padding: 0.5rem;
}

.is-icon-only.size-small {
    padding: 0.375rem;
}

.is-icon-only.size-large {
    padding: 0.625rem;
}

/* Icon right positioning */
.icon-right {
    flex-direction: row-reverse;
}

/* Icon */
.btn-icon {
    font-size: 0.875em;
}
</style>

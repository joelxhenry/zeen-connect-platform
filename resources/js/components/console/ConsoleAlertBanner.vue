<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLink from '@/components/common/AppLink.vue';

interface Props {
    variant: 'info' | 'warning' | 'success' | 'danger';
    icon?: string;
    dismissible?: boolean;
    actionLabel?: string;
    actionHref?: string;
}

const props = withDefaults(defineProps<Props>(), {
    dismissible: false,
});

const isDismissed = ref(false);

const variantClasses = computed(() => {
    const variants = {
        info: 'bg-blue-50 border-blue-200 text-blue-800',
        warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
        success: 'bg-green-50 border-green-200 text-green-800',
        danger: 'bg-red-50 border-red-200 text-red-800',
    };
    return variants[props.variant];
});

const iconClasses = computed(() => {
    const icons = {
        info: 'pi pi-info-circle',
        warning: 'pi pi-exclamation-triangle',
        success: 'pi pi-check-circle',
        danger: 'pi pi-times-circle',
    };
    return props.icon || icons[props.variant];
});

const actionClasses = computed(() => {
    const variants = {
        info: 'text-blue-700 hover:text-blue-900',
        warning: 'text-yellow-700 hover:text-yellow-900',
        success: 'text-green-700 hover:text-green-900',
        danger: 'text-red-700 hover:text-red-900',
    };
    return variants[props.variant];
});

const dismiss = () => {
    isDismissed.value = true;
};
</script>

<template>
    <div
        v-if="!isDismissed"
        class="flex flex-wrap items-center gap-3 px-4 py-3 rounded-xl border mb-6"
        :class="variantClasses"
    >
        <!-- Icon -->
        <i :class="[iconClasses, 'text-lg shrink-0']" />

        <!-- Content -->
        <div class="flex-1 min-w-0 text-sm">
            <slot />
        </div>

        <!-- Action link -->
        <AppLink
            v-if="actionLabel && actionHref"
            :href="actionHref"
            class="text-sm font-medium no-underline hover:underline shrink-0"
            :class="actionClasses"
        >
            {{ actionLabel }}
            <i class="pi pi-arrow-right text-xs ml-1" />
        </AppLink>

        <!-- Dismiss button -->
        <button
            v-if="dismissible"
            type="button"
            class="p-1 rounded hover:bg-black/5 transition-colors shrink-0"
            @click="dismiss"
        >
            <i class="pi pi-times text-sm" />
        </button>
    </div>
</template>

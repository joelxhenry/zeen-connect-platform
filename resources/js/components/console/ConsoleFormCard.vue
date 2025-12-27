<script setup lang="ts">
import { ref, computed } from 'vue';

interface Props {
    title?: string;
    subtitle?: string;
    icon?: string;
    iconColor?: string;
    collapsible?: boolean;
    defaultCollapsed?: boolean;
    variant?: 'default' | 'danger';
    noPadding?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    iconColor: '#106B4F',
    collapsible: false,
    defaultCollapsed: false,
    variant: 'default',
    noPadding: false,
});

const isCollapsed = ref(props.defaultCollapsed);

const toggle = () => {
    if (props.collapsible) {
        isCollapsed.value = !isCollapsed.value;
    }
};

const containerClasses = computed(() => {
    const classes = ['rounded-xl overflow-hidden transition-shadow duration-[var(--transition-normal)]'];

    if (props.variant === 'danger') {
        classes.push('bg-white border border-red-200 shadow-[var(--shadow-sm)]');
    } else {
        classes.push('bg-white shadow-[var(--shadow-sm)] hover:shadow-[var(--shadow-md)]');
    }

    return classes.join(' ');
});

const headerClasses = computed(() => {
    const classes = ['px-4 lg:px-5 py-3 lg:py-4 border-b'];

    if (props.variant === 'danger') {
        classes.push('bg-red-50 border-red-200');
    } else {
        classes.push('border-gray-100');
    }

    if (props.collapsible) {
        classes.push('cursor-pointer select-none');
    }

    return classes.join(' ');
});

const titleClasses = computed(() => {
    const classes = ['text-sm lg:text-base font-semibold m-0'];

    if (props.variant === 'danger') {
        classes.push('text-red-700');
    } else {
        classes.push('text-[#0D1F1B]');
    }

    return classes.join(' ');
});

const bodyClasses = computed(() => {
    if (props.noPadding) return '';
    return 'p-4 lg:p-5';
});
</script>

<template>
    <div :class="containerClasses">
        <!-- Header -->
        <div
            v-if="title || $slots['header-actions']"
            :class="headerClasses"
            @click="toggle"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <!-- Icon -->
                    <div
                        v-if="icon"
                        class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                        :class="variant === 'danger' ? 'bg-red-100' : 'bg-[#106B4F]/10'"
                    >
                        <i
                            :class="icon"
                            :style="{ color: variant === 'danger' ? '#DC2626' : iconColor }"
                        />
                    </div>

                    <div>
                        <h2 :class="titleClasses">{{ title }}</h2>
                        <p v-if="subtitle" class="text-xs text-gray-500 m-0 mt-0.5">
                            {{ subtitle }}
                        </p>
                    </div>
                </div>

                <!-- Header actions slot -->
                <div v-if="$slots['header-actions']" class="flex items-center gap-2" @click.stop>
                    <slot name="header-actions" />
                </div>

                <!-- Collapse indicator -->
                <i
                    v-if="collapsible"
                    class="pi pi-chevron-down text-gray-400 transition-transform duration-200"
                    :class="{ 'rotate-180': !isCollapsed }"
                />
            </div>
        </div>

        <!-- Body -->
        <div
            v-show="!collapsible || !isCollapsed"
            :class="bodyClasses"
        >
            <slot />
        </div>

        <!-- Footer slot -->
        <div
            v-if="$slots.footer"
            class="px-4 lg:px-5 py-3 lg:py-4 border-t border-gray-100 bg-gray-50/50"
        >
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import AppLink from '@/components/common/AppLink.vue';

interface Props {
    title: string;
    value: string | number;
    icon: string;
    iconColor?: 'primary' | 'accent' | 'warning' | 'purple' | 'success' | 'danger';
    href?: string;
    trend?: {
        value: number;
        direction: 'up' | 'down' | 'neutral';
    };
    size?: 'default' | 'compact';
}

const props = withDefaults(defineProps<Props>(), {
    iconColor: 'primary',
    size: 'default',
});

const iconColorClasses = computed(() => {
    const colorMap = {
        primary: 'bg-[#106B4F]/10 text-[#106B4F]',
        accent: 'bg-[#1ABC9C]/10 text-[#1ABC9C]',
        warning: 'bg-yellow-500/10 text-yellow-600',
        purple: 'bg-[#5B5BD6]/10 text-[#5B5BD6]',
        success: 'bg-green-500/10 text-green-600',
        danger: 'bg-red-500/10 text-red-600',
    };
    return colorMap[props.iconColor];
});

const trendClasses = computed(() => {
    if (!props.trend) return '';

    const directionMap = {
        up: 'text-green-600',
        down: 'text-red-600',
        neutral: 'text-gray-500',
    };
    return directionMap[props.trend.direction];
});

const trendIcon = computed(() => {
    if (!props.trend) return '';

    const iconMap = {
        up: 'pi pi-arrow-up',
        down: 'pi pi-arrow-down',
        neutral: 'pi pi-minus',
    };
    return iconMap[props.trend.direction];
});

const containerClasses = computed(() => {
    const classes = [
        'bg-white rounded-xl shadow-[var(--shadow-sm)]',
        'transition-all duration-[var(--transition-normal)]',
    ];

    if (props.href) {
        classes.push('hover:shadow-[var(--shadow-md)] cursor-pointer');
    }

    if (props.size === 'compact') {
        classes.push('p-3 lg:p-4');
    } else {
        classes.push('p-4 lg:p-5');
    }

    return classes.join(' ');
});

const iconContainerClasses = computed(() => {
    const classes = ['rounded-xl flex items-center justify-center shrink-0'];

    if (props.size === 'compact') {
        classes.push('w-9 h-9');
    } else {
        classes.push('w-10 h-10 lg:w-12 lg:h-12');
    }

    classes.push(iconColorClasses.value);

    return classes.join(' ');
});
</script>

<template>
    <component
        :is="href ? AppLink : 'div'"
        :href="href"
        :class="containerClasses"
    >
        <div class="flex items-start gap-3 lg:gap-4">
            <!-- Icon -->
            <div :class="iconContainerClasses">
                <i :class="[icon, size === 'compact' ? 'text-base' : 'text-lg lg:text-xl']" />
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <p
                    class="text-gray-500 m-0 truncate"
                    :class="size === 'compact' ? 'text-xs' : 'text-xs lg:text-[13px]'"
                >
                    {{ title }}
                </p>
                <p
                    class="font-semibold text-[#0D1F1B] m-0 mt-0.5"
                    :class="size === 'compact' ? 'text-base' : 'text-lg lg:text-xl'"
                >
                    {{ value }}
                </p>

                <!-- Trend indicator -->
                <div v-if="trend" class="flex items-center gap-1 mt-1" :class="trendClasses">
                    <i :class="[trendIcon, 'text-xs']" />
                    <span class="text-xs font-medium">{{ trend.value }}%</span>
                </div>
            </div>

            <!-- Arrow for clickable cards -->
            <i
                v-if="href"
                class="pi pi-chevron-right text-gray-300 text-sm mt-1"
            />
        </div>
    </component>
</template>

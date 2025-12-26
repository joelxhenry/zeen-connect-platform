<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    value: string | number;
    icon: string;
    iconBg?: string;
    trend?: {
        value: number;
        direction: 'up' | 'down' | 'neutral';
        label?: string;
    };
    href?: string;
}>();

const formattedValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value.toLocaleString();
    }
    return props.value;
});

const trendIcon = computed(() => {
    if (!props.trend) return '';
    switch (props.trend.direction) {
        case 'up': return 'pi pi-arrow-up';
        case 'down': return 'pi pi-arrow-down';
        default: return 'pi pi-minus';
    }
});

const trendClass = computed(() => {
    if (!props.trend) return '';
    switch (props.trend.direction) {
        case 'up': return 'trend-up';
        case 'down': return 'trend-down';
        default: return 'trend-neutral';
    }
});
</script>

<template>
    <component
        :is="href ? 'AppLink' : 'div'"
        :href="href"
        class="stat-card"
        :class="{ clickable: href }"
    >
        <div class="stat-header">
            <div
                class="stat-icon"
                :style="{ backgroundColor: iconBg || '#106B4F' }"
            >
                <i :class="icon"></i>
            </div>
            <div v-if="trend" class="stat-trend" :class="trendClass">
                <i :class="trendIcon"></i>
                <span>{{ Math.abs(trend.value) }}%</span>
            </div>
        </div>

        <div class="stat-content">
            <p class="stat-value">{{ formattedValue }}</p>
            <p class="stat-title">{{ title }}</p>
            <p v-if="trend?.label" class="stat-trend-label">{{ trend.label }}</p>
        </div>

        <div v-if="href" class="stat-action">
            <span>View details</span>
            <i class="pi pi-arrow-right"></i>
        </div>
    </component>
</template>

<style scoped>
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #E2E8F0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    transition: all 0.2s ease;
    text-decoration: none;
}

.stat-card.clickable:hover {
    border-color: #106B4F;
    box-shadow: 0 4px 12px rgba(16, 107, 79, 0.1);
    transform: translateY(-2px);
}

.stat-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.stat-trend i {
    font-size: 0.625rem;
}

.trend-up {
    background-color: #DCFCE7;
    color: #16A34A;
}

.trend-down {
    background-color: #FEE2E2;
    color: #DC2626;
}

.trend-neutral {
    background-color: #F3F4F6;
    color: #6B7280;
}

.stat-content {
    flex: 1;
}

.stat-value {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.2;
}

.stat-title {
    margin: 0.25rem 0 0;
    font-size: 0.875rem;
    color: #64748B;
}

.stat-trend-label {
    margin: 0.5rem 0 0;
    font-size: 0.75rem;
    color: #94A3B8;
}

.stat-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 0.75rem;
    border-top: 1px solid #F1F5F9;
    font-size: 0.8125rem;
    color: #106B4F;
    font-weight: 500;
}

.stat-action i {
    font-size: 0.75rem;
    transition: transform 0.2s;
}

.stat-card.clickable:hover .stat-action i {
    transform: translateX(4px);
}
</style>

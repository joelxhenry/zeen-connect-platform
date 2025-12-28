<script setup lang="ts">
import { computed } from 'vue';
import type { FoundingTier } from '@/types/models/provider';

const props = defineProps<{
    tier?: FoundingTier | null;
    size?: 'small' | 'normal' | 'large';
    showLabel?: boolean;
}>();

const badgeConfig = computed(() => {
    const isEnterprise = props.tier === 'enterprise';

    return {
        label: isEnterprise ? 'Enterprise Founder' : 'Growth Founder',
        shortLabel: 'Founder',
        icon: 'pi-star-fill',
    };
});

const sizeClass = computed(() => {
    switch (props.size) {
        case 'small':
            return 'badge-small';
        case 'large':
            return 'badge-large';
        default:
            return '';
    }
});

const showFullLabel = computed(() => props.showLabel !== false);
</script>

<template>
    <span :class="['founding-badge', sizeClass, tier]">
        <i :class="['pi', badgeConfig.icon]"></i>
        <span v-if="showFullLabel">{{ badgeConfig.label }}</span>
        <span v-else>{{ badgeConfig.shortLabel }}</span>
    </span>
</template>

<style scoped>
.founding-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 9999px;
    background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
    color: white;
    white-space: nowrap;
}

.founding-badge.enterprise {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
}

.founding-badge i {
    font-size: 0.625rem;
}

/* Size variants */
.badge-small {
    padding: 0.125rem 0.5rem;
    font-size: 0.625rem;
    gap: 0.25rem;
}

.badge-small i {
    font-size: 0.5rem;
}

.badge-large {
    padding: 0.375rem 0.875rem;
    font-size: 0.875rem;
    gap: 0.5rem;
}

.badge-large i {
    font-size: 0.75rem;
}
</style>

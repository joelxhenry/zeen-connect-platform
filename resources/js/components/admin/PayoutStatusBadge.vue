<script setup lang="ts">
import { computed } from 'vue';
import Tag from 'primevue/tag';

type PayoutStatus = 'pending' | 'processing' | 'paid' | 'failed' | 'cancelled';

const props = defineProps<{
    status: PayoutStatus;
    size?: 'small' | 'normal' | 'large';
}>();

const statusConfig = computed(() => {
    switch (props.status) {
        case 'pending':
            return {
                label: 'Pending',
                severity: 'warn' as const,
                icon: 'pi pi-clock',
            };
        case 'processing':
            return {
                label: 'Processing',
                severity: 'info' as const,
                icon: 'pi pi-spin pi-spinner',
            };
        case 'paid':
            return {
                label: 'Paid',
                severity: 'success' as const,
                icon: 'pi pi-check-circle',
            };
        case 'failed':
            return {
                label: 'Failed',
                severity: 'danger' as const,
                icon: 'pi pi-times-circle',
            };
        case 'cancelled':
            return {
                label: 'Cancelled',
                severity: 'secondary' as const,
                icon: 'pi pi-ban',
            };
        default:
            return {
                label: props.status,
                severity: 'secondary' as const,
                icon: 'pi pi-question-circle',
            };
    }
});

const sizeClass = computed(() => {
    switch (props.size) {
        case 'small': return 'badge-small';
        case 'large': return 'badge-large';
        default: return '';
    }
});
</script>

<template>
    <Tag
        :severity="statusConfig.severity"
        :class="['payout-badge', sizeClass]"
    >
        <i :class="statusConfig.icon" class="badge-icon"></i>
        <span>{{ statusConfig.label }}</span>
    </Tag>
</template>

<style scoped>
.payout-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-weight: 500;
}

.badge-icon {
    font-size: 0.75rem;
}

.badge-small {
    padding: 0.125rem 0.5rem;
    font-size: 0.6875rem;
}

.badge-small .badge-icon {
    font-size: 0.625rem;
}

.badge-large {
    padding: 0.5rem 0.875rem;
    font-size: 0.875rem;
}

.badge-large .badge-icon {
    font-size: 0.875rem;
}
</style>

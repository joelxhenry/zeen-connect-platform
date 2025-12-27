<script setup lang="ts">
import { computed } from 'vue';
import { ConsoleAlertBanner } from '@/components/console';
import Tag from 'primevue/tag';
import type { TierRestrictions } from '@/types/service';

interface Props {
    restrictions: TierRestrictions;
    context?: 'index' | 'form';
}

const props = withDefaults(defineProps<Props>(), {
    context: 'form',
});

const variant = computed((): 'info' | 'warning' | 'success' => {
    if (props.restrictions.tier === 'enterprise') return 'success';
    if (props.restrictions.tier === 'premium') return 'info';
    return 'warning';
});

const tagSeverity = computed((): 'success' | 'info' | 'secondary' => {
    if (props.restrictions.tier === 'enterprise') return 'success';
    if (props.restrictions.tier === 'premium') return 'info';
    return 'secondary';
});

const restrictionsList = computed(() => {
    const items: string[] = [];

    if (props.restrictions.minimum_service_price > 0) {
        items.push(`Min. price: ${props.restrictions.minimum_service_price_display}`);
    }

    if (props.restrictions.minimum_deposit_percentage > 0) {
        items.push(`Min. deposit: ${props.restrictions.minimum_deposit_percentage}%`);
    }

    if (!props.restrictions.can_disable_deposit) {
        items.push('Deposit required');
    }

    return items;
});

const message = computed(() => {
    if (props.restrictions.tier === 'enterprise') {
        return 'No restrictions on pricing or deposits.';
    }

    if (restrictionsList.value.length === 0) {
        return '';
    }

    return restrictionsList.value.join(' | ');
});

const showUpgradeLink = computed(() => {
    return props.restrictions.tier !== 'enterprise' && props.context === 'form';
});
</script>

<template>
    <ConsoleAlertBanner
        :variant="variant"
        :action-label="showUpgradeLink ? 'Upgrade Plan' : undefined"
        :action-href="showUpgradeLink ? '/subscription' : undefined"
        :dismissible="context === 'index'"
    >
        <div class="flex items-center gap-2 flex-wrap">
            <Tag
                :value="restrictions.tier_label"
                :severity="tagSeverity"
                class="!text-xs"
            />
            <span v-if="message">{{ message }}</span>
            <span v-else class="text-gray-600">
                {{ restrictions.tier === 'enterprise' ? 'Full control over pricing and deposits.' : '' }}
            </span>
        </div>
    </ConsoleAlertBanner>
</template>

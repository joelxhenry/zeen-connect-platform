<script setup lang="ts">
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleButton,
} from '@/components/console';
import Tag from 'primevue/tag';

interface Feature {
    value: string;
    label: string;
    description: string;
    icon: string;
    available: boolean;
    minimum_tier: string;
}

interface TierInfo {
    value: string;
    label: string;
    color: string;
    monthly_price: number;
    monthly_price_display: string;
    deposit_percentage: number;
    zeen_fee_rate: number;
    gateway_fee_rate: number;
    total_fee_rate: number;
    platform_fee_rate: number; // Legacy
    team_slots: number | 'unlimited';
    team_description: string;
    features: Array<{
        value: string;
        label: string;
        available: boolean;
    }>;
}

interface CurrentTier {
    value: string;
    label: string;
    color: string;
    deposit_percentage: number;
    zeen_fee_rate: number;
    gateway_fee_rate: number;
    total_fee_rate: number;
    platform_fee_rate: number; // Legacy
    team_description: string;
}

interface Subscription {
    started_at: string | null;
    expires_at: string | null;
    status: string;
    status_label: string;
}

interface Props {
    currentTier: CurrentTier;
    features: Feature[];
    allTiers: TierInfo[];
    subscription: Subscription | null;
}

const props = defineProps<Props>();

const getTagSeverity = (color: string): 'success' | 'info' | 'secondary' | 'warn' | 'danger' => {
    switch (color) {
        case 'success':
            return 'success';
        case 'info':
            return 'info';
        default:
            return 'secondary';
    }
};

const availableFeatures = props.features.filter(f => f.available);
const unavailableFeatures = props.features.filter(f => !f.available);
</script>

<template>
    <ConsoleLayout title="Subscription">
        <div class="w-full max-w-7xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Subscription"
                subtitle="Manage your subscription plan and features"
            />

            <!-- Current Plan Card -->
            <ConsoleFormCard class="mb-6">
                <template #header-actions>
                    <Tag :value="currentTier.label" :severity="getTagSeverity(currentTier.color)" />
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <span class="text-gray-500 text-sm block mb-1">Transaction Fee</span>
                        <p class="font-semibold text-[#0D1F1B] m-0">
                            {{ currentTier.total_fee_rate }}%
                        </p>
                        <p class="text-xs text-gray-500 m-0 mt-1">
                            {{ currentTier.zeen_fee_rate }}% Zeen + {{ currentTier.gateway_fee_rate }}% Gateway
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500 text-sm block mb-1">Deposit Requirement</span>
                        <p class="font-semibold text-[#0D1F1B] m-0">
                            {{ currentTier.deposit_percentage > 0 ? `${currentTier.deposit_percentage}%` : 'No minimum' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500 text-sm block mb-1">Team Members</span>
                        <p class="font-semibold text-[#0D1F1B] m-0">{{ currentTier.team_description }}</p>
                    </div>
                </div>

                <div v-if="subscription" class="mt-6 pt-6 border-t border-gray-100">
                    <div class="flex flex-wrap gap-6">
                        <div v-if="subscription.started_at">
                            <span class="text-gray-500 text-sm block mb-1">Started</span>
                            <p class="font-medium text-[#0D1F1B] m-0">{{ subscription.started_at }}</p>
                        </div>
                        <div v-if="subscription.expires_at">
                            <span class="text-gray-500 text-sm block mb-1">Expires</span>
                            <p class="font-medium text-[#0D1F1B] m-0">{{ subscription.expires_at }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 text-sm block mb-1">Status</span>
                            <p class="font-medium text-[#0D1F1B] m-0">{{ subscription.status_label }}</p>
                        </div>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Features Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Available Features -->
                <ConsoleFormCard title="Included Features" icon="pi pi-check-circle">
                    <ul class="space-y-4 m-0 p-0 list-none">
                        <li v-for="feature in availableFeatures" :key="feature.value" class="flex items-start gap-3">
                            <i :class="feature.icon" class="text-[#106B4F] mt-0.5"></i>
                            <div>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ feature.label }}</p>
                                <p class="text-sm text-gray-500 m-0">{{ feature.description }}</p>
                            </div>
                        </li>
                    </ul>
                </ConsoleFormCard>

                <!-- Unavailable Features -->
                <ConsoleFormCard v-if="unavailableFeatures.length > 0" title="Upgrade to Unlock" icon="pi pi-lock" icon-color="secondary">
                    <ul class="space-y-4 m-0 p-0 list-none">
                        <li v-for="feature in unavailableFeatures" :key="feature.value" class="flex items-start gap-3 opacity-60">
                            <i :class="feature.icon" class="text-gray-400 mt-0.5"></i>
                            <div>
                                <p class="font-medium text-[#0D1F1B] m-0">
                                    {{ feature.label }}
                                    <Tag :value="feature.minimum_tier" size="small" severity="secondary" class="ml-2" />
                                </p>
                                <p class="text-sm text-gray-500 m-0">{{ feature.description }}</p>
                            </div>
                        </li>
                    </ul>
                </ConsoleFormCard>
            </div>

            <!-- Plan Comparison -->
            <ConsoleFormCard title="Compare Plans" class="mb-6">
                <div class="overflow-x-auto -mx-4 lg:-mx-5">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left p-4 font-medium text-gray-500">Feature</th>
                                <th v-for="tier in allTiers" :key="tier.value" class="text-center p-4 font-semibold">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-[#0D1F1B]">{{ tier.label }}</span>
                                        <span class="text-sm text-gray-500 font-normal">{{ tier.monthly_price_display }}/mo</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-50">
                                <td class="p-4 text-gray-600">
                                    <div>Zeen Fee</div>
                                    <div class="text-xs text-gray-400">Platform fee</div>
                                </td>
                                <td v-for="tier in allTiers" :key="tier.value" class="p-4 text-center">
                                    {{ tier.zeen_fee_rate }}%
                                </td>
                            </tr>
                            <tr class="border-b border-gray-50">
                                <td class="p-4 text-gray-600">
                                    <div>Gateway Fee</div>
                                    <div class="text-xs text-gray-400">Payment processing</div>
                                </td>
                                <td v-for="tier in allTiers" :key="tier.value" class="p-4 text-center">
                                    {{ tier.gateway_fee_rate }}%
                                </td>
                            </tr>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <td class="p-4 text-gray-700 font-medium">Total Fee</td>
                                <td v-for="tier in allTiers" :key="tier.value" class="p-4 text-center font-semibold">
                                    {{ tier.total_fee_rate }}%
                                </td>
                            </tr>
                            <tr class="border-b border-gray-50">
                                <td class="p-4 text-gray-600">Deposit Requirement</td>
                                <td v-for="tier in allTiers" :key="tier.value" class="p-4 text-center">
                                    {{ tier.deposit_percentage > 0 ? `${tier.deposit_percentage}%` : 'Customizable' }}
                                </td>
                            </tr>
                            <tr class="border-b border-gray-50">
                                <td class="p-4 text-gray-600">Team Members</td>
                                <td v-for="tier in allTiers" :key="tier.value" class="p-4 text-center text-sm">
                                    {{ tier.team_slots === 'unlimited' ? 'Unlimited' : tier.team_slots }}
                                </td>
                            </tr>
                            <tr v-for="feature in features" :key="feature.value" class="border-b border-gray-50">
                                <td class="p-4 text-gray-600">{{ feature.label }}</td>
                                <td v-for="tier in allTiers" :key="tier.value" class="p-4 text-center">
                                    <i v-if="tier.features.find(f => f.value === feature.value)?.available"
                                       class="pi pi-check text-[#106B4F]"></i>
                                    <i v-else class="pi pi-times text-gray-300"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </ConsoleFormCard>

            <!-- Upgrade CTA -->
            <div v-if="currentTier.value !== 'enterprise'" class="bg-gradient-to-r from-[#0D1F1B] to-[#106B4F] rounded-xl p-6 lg:p-8 text-white">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold m-0 mb-2">Ready to upgrade?</h3>
                        <p class="m-0 text-white/80">
                            Unlock more features and grow your business with a higher tier plan.
                        </p>
                    </div>
                    <a href="mailto:support@zeen.com?subject=Subscription%20Upgrade%20Request"
                       class="no-underline">
                        <ConsoleButton
                            label="Contact Us to Upgrade"
                            icon="pi pi-arrow-right"
                            icon-pos="right"
                            variant="secondary"
                            class="!bg-white !text-[#0D1F1B] !border-white whitespace-nowrap"
                        />
                    </a>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

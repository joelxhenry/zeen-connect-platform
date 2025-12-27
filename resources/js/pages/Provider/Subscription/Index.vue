<script setup lang="ts">
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

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
    platform_fee_rate: number;
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
    platform_fee_rate: number;
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
            <div class="mb-6">
                <h1 class="text-xl lg:text-2xl font-semibold text-[#0D1F1B] m-0 mb-1">Subscription</h1>
                <p class="text-gray-500 m-0 text-sm lg:text-base">
                    Manage your subscription plan and features
                </p>
            </div>

            <!-- Current Plan Card -->
            <div class="bg-white rounded-xl shadow-sm mb-6 overflow-hidden">
                <div class="px-4 lg:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-[#0D1F1B] m-0">Current Plan</h2>
                    <Tag :value="currentTier.label" :severity="getTagSeverity(currentTier.color)" />
                </div>
                <div class="p-4 lg:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <span class="text-gray-500 text-sm block mb-1">Deposit Requirement</span>
                            <p class="font-semibold text-[#0D1F1B] m-0">
                                {{ currentTier.deposit_percentage > 0 ? `${currentTier.deposit_percentage}%` : 'No minimum' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-500 text-sm block mb-1">Platform Fee</span>
                            <p class="font-semibold text-[#0D1F1B] m-0">
                                {{ currentTier.platform_fee_rate > 0 ? `${currentTier.platform_fee_rate}%` : 'No fee' }}
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
                </div>
            </div>

            <!-- Features Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Available Features -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                            <i class="pi pi-check-circle text-[#106B4F]"></i>
                            Included Features
                        </h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <ul class="space-y-4 m-0 p-0 list-none">
                            <li v-for="feature in availableFeatures" :key="feature.value" class="flex items-start gap-3">
                                <i :class="feature.icon" class="text-[#106B4F] mt-0.5"></i>
                                <div>
                                    <p class="font-medium text-[#0D1F1B] m-0">{{ feature.label }}</p>
                                    <p class="text-sm text-gray-500 m-0">{{ feature.description }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Unavailable Features -->
                <div v-if="unavailableFeatures.length > 0" class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                            <i class="pi pi-lock text-gray-400"></i>
                            Upgrade to Unlock
                        </h2>
                    </div>
                    <div class="p-4 lg:p-6">
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
                    </div>
                </div>
            </div>

            <!-- Plan Comparison -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="px-4 lg:px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-[#0D1F1B] m-0">Compare Plans</h2>
                </div>
                <div class="overflow-x-auto">
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
                                <td class="p-4 text-gray-600">Platform Fee</td>
                                <td v-for="tier in allTiers" :key="tier.value" class="p-4 text-center">
                                    {{ tier.platform_fee_rate > 0 ? `${tier.platform_fee_rate}%` : 'None' }}
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
                                    {{ tier.team_description }}
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
            </div>

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
                        <Button label="Contact Us to Upgrade" icon="pi pi-arrow-right" iconPos="right"
                            class="!bg-white !text-[#0D1F1B] !border-white whitespace-nowrap" />
                    </a>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

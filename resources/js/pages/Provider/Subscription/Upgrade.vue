<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleButton,
} from '@/components/console';
import Tag from 'primevue/tag';
import SelectButton from 'primevue/selectbutton';

interface TierInfo {
    value: string;
    label: string;
    color: string;
    monthly_price: number;
    monthly_price_display: string;
    annual_price: number;
    annual_price_display: string;
    annual_savings: number;
    annual_savings_display: string;
    annual_savings_percentage: number;
    effective_monthly_price: number;
    effective_monthly_price_display: string;
    deposit_percentage: number;
    zeen_fee_rate: number;
    gateway_fee_rate: number;
    total_fee_rate: number;
    team_slots: number | 'unlimited';
    team_description: string;
    is_free: boolean;
    is_current: boolean;
    features: Array<{
        value: string;
        label: string;
        available: boolean;
    }>;
}

interface Props {
    currentTier: string;
    availableTiers: TierInfo[];
    canStartTrial: boolean;
    isOnTrial: boolean;
    trialDaysRemaining: number;
}

const props = defineProps<Props>();

const billingCycle = ref<'monthly' | 'annual'>('monthly');
const selectedTier = ref<string | null>(null);
const processing = ref(false);

const billingOptions = [
    { label: 'Monthly', value: 'monthly' },
    { label: 'Annual (Save 17%)', value: 'annual' },
];

const selectedTierInfo = computed(() => {
    return props.availableTiers.find(t => t.value === selectedTier.value);
});

const displayPrice = computed(() => {
    if (!selectedTierInfo.value) return null;
    return billingCycle.value === 'annual'
        ? selectedTierInfo.value.annual_price_display
        : selectedTierInfo.value.monthly_price_display;
});

const periodLabel = computed(() => {
    return billingCycle.value === 'annual' ? 'per year' : 'per month';
});

const effectiveMonthlyPrice = computed(() => {
    if (!selectedTierInfo.value || billingCycle.value !== 'annual') return null;
    return selectedTierInfo.value.effective_monthly_price_display;
});

const annualSavings = computed(() => {
    if (!selectedTierInfo.value || billingCycle.value !== 'annual') return null;
    return selectedTierInfo.value.annual_savings_display;
});

const selectTier = (tierValue: string) => {
    if (tierValue === props.currentTier) return;
    selectedTier.value = tierValue;
};

const processUpgrade = () => {
    if (!selectedTier.value || processing.value) return;

    processing.value = true;
    router.post(route('provider.subscription.upgrade.process'), {
        tier: selectedTier.value,
        billing_cycle: billingCycle.value,
    });
};

const startTrial = () => {
    if (processing.value) return;

    processing.value = true;
    router.post(route('provider.subscription.trial.start'), {
        tier: selectedTier.value || 'premium',
    });
};

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
</script>

<template>
    <ConsoleLayout title="Upgrade Subscription">
        <div class="w-full max-w-5xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Upgrade Your Plan"
                subtitle="Choose a plan that fits your business needs"
            >
                <template #actions>
                    <AppLink :href="route('provider.subscription.index')">
                        <ConsoleButton
                            label="Back"
                            icon="pi pi-arrow-left"
                            variant="secondary"
                        />
                    </AppLink>
                </template>
            </ConsolePageHeader>

            <!-- Trial Banner -->
            <div v-if="canStartTrial" class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-6 mb-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="pi pi-gift text-amber-600 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-amber-900 m-0 mb-1">Try Premium Free for 14 Days</h3>
                        <p class="text-amber-700 m-0 mb-3">
                            Experience all premium features with no commitment. No credit card required.
                        </p>
                        <ConsoleButton
                            label="Start Free Trial"
                            icon="pi pi-play"
                            @click="startTrial"
                            :loading="processing"
                            class="!bg-amber-600 !border-amber-600 hover:!bg-amber-700"
                        />
                    </div>
                </div>
            </div>

            <!-- On Trial Banner -->
            <div v-else-if="isOnTrial" class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-3">
                    <i class="pi pi-clock text-blue-600"></i>
                    <p class="m-0 text-blue-800">
                        <strong>Trial Active:</strong> {{ trialDaysRemaining }} days remaining.
                        Subscribe now to continue using premium features.
                    </p>
                </div>
            </div>

            <!-- Billing Cycle Toggle -->
            <div class="flex justify-center mb-8">
                <SelectButton
                    v-model="billingCycle"
                    :options="billingOptions"
                    option-label="label"
                    option-value="value"
                    :allow-empty="false"
                />
            </div>

            <!-- Tier Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div
                    v-for="tier in availableTiers"
                    :key="tier.value"
                    class="relative rounded-xl border-2 p-6 transition-all cursor-pointer"
                    :class="[
                        tier.is_current
                            ? 'border-gray-200 bg-gray-50 cursor-default opacity-75'
                            : selectedTier === tier.value
                                ? 'border-[#106B4F] bg-[#106B4F]/5 shadow-lg'
                                : 'border-gray-200 hover:border-gray-300 hover:shadow-md'
                    ]"
                    @click="selectTier(tier.value)"
                >
                    <!-- Current Plan Badge -->
                    <div v-if="tier.is_current" class="absolute -top-3 left-4">
                        <Tag value="Current Plan" severity="secondary" />
                    </div>

                    <!-- Selected Check -->
                    <div v-if="selectedTier === tier.value && !tier.is_current"
                         class="absolute top-4 right-4 w-6 h-6 bg-[#106B4F] rounded-full flex items-center justify-center">
                        <i class="pi pi-check text-white text-sm"></i>
                    </div>

                    <!-- Tier Header -->
                    <div class="mb-4">
                        <Tag :value="tier.label" :severity="getTagSeverity(tier.color)" class="mb-2" />
                        <div class="mt-3">
                            <template v-if="tier.is_free">
                                <span class="text-3xl font-bold text-[#0D1F1B]">Free</span>
                            </template>
                            <template v-else>
                                <span class="text-3xl font-bold text-[#0D1F1B]">
                                    {{ billingCycle === 'annual' ? tier.annual_price_display : tier.monthly_price_display }}
                                </span>
                                <span class="text-gray-500 text-sm">
                                    /{{ billingCycle === 'annual' ? 'year' : 'month' }}
                                </span>
                            </template>
                        </div>
                        <div v-if="!tier.is_free && billingCycle === 'annual'" class="mt-1">
                            <span class="text-sm text-[#106B4F] font-medium">
                                {{ tier.effective_monthly_price_display }}/mo
                            </span>
                            <span class="text-sm text-gray-500 ml-2">
                                (Save {{ tier.annual_savings_display }})
                            </span>
                        </div>
                    </div>

                    <!-- Tier Benefits -->
                    <ul class="space-y-3 m-0 p-0 list-none mb-4">
                        <li class="flex items-center gap-2 text-sm">
                            <i class="pi pi-percentage text-[#106B4F]"></i>
                            <span>{{ tier.total_fee_rate }}% transaction fee</span>
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <i class="pi pi-users text-[#106B4F]"></i>
                            <span>{{ tier.team_description }}</span>
                        </li>
                        <li v-if="tier.deposit_percentage === 0" class="flex items-center gap-2 text-sm">
                            <i class="pi pi-sliders-h text-[#106B4F]"></i>
                            <span>Customizable deposit</span>
                        </li>
                    </ul>

                    <!-- Feature List -->
                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Features</p>
                        <ul class="space-y-2 m-0 p-0 list-none">
                            <li v-for="feature in tier.features.filter(f => f.available).slice(0, 5)"
                                :key="feature.value"
                                class="flex items-center gap-2 text-sm text-gray-600">
                                <i class="pi pi-check text-[#106B4F] text-xs"></i>
                                <span>{{ feature.label }}</span>
                            </li>
                            <li v-if="tier.features.filter(f => f.available).length > 5"
                                class="text-sm text-gray-500 italic">
                                +{{ tier.features.filter(f => f.available).length - 5 }} more features
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Payment Summary -->
            <ConsoleFormCard v-if="selectedTier && selectedTierInfo && !selectedTierInfo.is_free" class="mb-6">
                <template #header>
                    <h3 class="text-lg font-semibold text-[#0D1F1B] m-0">Payment Summary</h3>
                </template>

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">{{ selectedTierInfo.label }} Plan</span>
                        <span class="font-medium">{{ displayPrice }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm text-gray-500">
                        <span>Billing Cycle</span>
                        <span>{{ billingCycle === 'annual' ? 'Annual' : 'Monthly' }}</span>
                    </div>
                    <div v-if="annualSavings" class="flex justify-between items-center text-sm text-[#106B4F]">
                        <span>Annual Savings</span>
                        <span>-{{ annualSavings }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-4 flex justify-between items-center">
                        <span class="font-semibold text-[#0D1F1B]">Total Today</span>
                        <span class="text-xl font-bold text-[#0D1F1B]">{{ displayPrice }}</span>
                    </div>
                    <p class="text-xs text-gray-500 m-0">
                        Your subscription will renew automatically {{ periodLabel }}.
                        You can cancel anytime.
                    </p>
                </div>

                <template #footer>
                    <div class="flex justify-end">
                        <ConsoleButton
                            label="Continue to Payment"
                            icon="pi pi-credit-card"
                            icon-pos="right"
                            @click="processUpgrade"
                            :loading="processing"
                            :disabled="!selectedTier || processing"
                        />
                    </div>
                </template>
            </ConsoleFormCard>

            <!-- Downgrade Notice for free tier -->
            <ConsoleFormCard v-else-if="selectedTier && selectedTierInfo?.is_free" class="mb-6">
                <div class="text-center py-4">
                    <i class="pi pi-info-circle text-4xl text-gray-400 mb-3"></i>
                    <h3 class="text-lg font-semibold text-[#0D1F1B] m-0 mb-2">Downgrade to Starter</h3>
                    <p class="text-gray-600 m-0 mb-4">
                        Your current plan will remain active until the end of your billing period.
                        After that, you'll be moved to the free Starter plan.
                    </p>
                    <ConsoleButton
                        label="Confirm Downgrade"
                        variant="secondary"
                        @click="processUpgrade"
                        :loading="processing"
                    />
                </div>
            </ConsoleFormCard>

            <!-- No Selection Prompt -->
            <div v-else class="text-center py-8 text-gray-500">
                <i class="pi pi-arrow-up text-3xl mb-3"></i>
                <p class="m-0">Select a plan above to continue</p>
            </div>
        </div>
    </ConsoleLayout>
</template>

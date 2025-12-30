<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleButton,
} from '@/components/console';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';

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
    platform_fee_rate: number;
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
    platform_fee_rate: number;
    team_description: string;
}

interface PaymentMethod {
    uuid: string;
    card_display: string;
    card_last_four: string;
    card_expiry: string;
    is_expired: boolean;
}

interface Subscription {
    started_at: string | null;
    expires_at: string | null;
    status: string;
    status_label: string;
    billing_cycle: string;
    billing_cycle_display: string;
    is_on_trial: boolean;
    trial_days_remaining: number;
    is_cancelled: boolean;
    is_in_grace_period: boolean;
    grace_period_ends_at: string | null;
    next_billing_date: string | null;
}

interface Props {
    currentTier: CurrentTier;
    features: Feature[];
    allTiers: TierInfo[];
    subscription: Subscription | null;
    paymentMethod: PaymentMethod | null;
    canStartTrial: boolean;
}

const props = defineProps<Props>();

const showCancelModal = ref(false);
const processing = ref(false);

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

const cancelSubscription = () => {
    processing.value = true;
    router.post(route('provider.subscription.cancel'), {}, {
        onFinish: () => {
            processing.value = false;
            showCancelModal.value = false;
        },
    });
};

const reactivateSubscription = () => {
    processing.value = true;
    router.post(route('provider.subscription.reactivate'), {}, {
        onFinish: () => {
            processing.value = false;
        },
    });
};

const startTrial = () => {
    processing.value = true;
    router.post(route('provider.subscription.trial.start'), {
        tier: 'premium',
    });
};
</script>

<template>
    <ConsoleLayout title="Subscription">
        <div class="w-full max-w-7xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Subscription"
                subtitle="Manage your subscription plan and features"
            >
                <template #actions>
                    <div class="flex gap-2">
                        <AppLink :href="route('provider.subscription.billing')">
                            <ConsoleButton
                                label="Billing History"
                                icon="pi pi-file"
                                variant="secondary"
                            />
                        </AppLink>
                        <AppLink v-if="currentTier.value !== 'enterprise'" :href="route('provider.subscription.upgrade')">
                            <ConsoleButton
                                label="Upgrade"
                                icon="pi pi-arrow-up"
                            />
                        </AppLink>
                    </div>
                </template>
            </ConsolePageHeader>

            <!-- Trial Banner -->
            <div v-if="subscription?.is_on_trial" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                            <i class="pi pi-clock text-amber-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-amber-900 m-0">Trial Period Active</p>
                            <p class="text-sm text-amber-700 m-0">
                                {{ subscription.trial_days_remaining }} days remaining. Subscribe to continue using premium features.
                            </p>
                        </div>
                    </div>
                    <AppLink :href="route('provider.subscription.upgrade')">
                        <ConsoleButton
                            label="Subscribe Now"
                            icon="pi pi-credit-card"
                            class="!bg-amber-600 !border-amber-600"
                        />
                    </AppLink>
                </div>
            </div>

            <!-- Free Trial CTA (for Starter tier without trial) -->
            <div v-else-if="canStartTrial" class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="pi pi-gift text-purple-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-purple-900 m-0">Try Premium Free for 14 Days</p>
                            <p class="text-sm text-purple-700 m-0">
                                Experience all premium features with no commitment. No credit card required.
                            </p>
                        </div>
                    </div>
                    <ConsoleButton
                        label="Start Free Trial"
                        icon="pi pi-play"
                        @click="startTrial"
                        :loading="processing"
                        class="!bg-purple-600 !border-purple-600"
                    />
                </div>
            </div>

            <!-- Cancellation Notice -->
            <div v-if="subscription?.is_in_grace_period" class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <i class="pi pi-exclamation-circle text-orange-600 text-xl"></i>
                        <div>
                            <p class="font-medium text-orange-900 m-0">Subscription Cancelled</p>
                            <p class="text-sm text-orange-700 m-0">
                                Your subscription remains active until {{ subscription.grace_period_ends_at }}.
                            </p>
                        </div>
                    </div>
                    <ConsoleButton
                        label="Reactivate"
                        icon="pi pi-refresh"
                        @click="reactivateSubscription"
                        :loading="processing"
                        variant="secondary"
                    />
                </div>
            </div>

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
                            {{ currentTier.deposit_percentage > 0 ? `${currentTier.deposit_percentage}%` : 'Customizable' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500 text-sm block mb-1">Team Members</span>
                        <p class="font-semibold text-[#0D1F1B] m-0">{{ currentTier.team_description }}</p>
                    </div>
                    <div v-if="subscription?.billing_cycle && currentTier.value !== 'starter'">
                        <span class="text-gray-500 text-sm block mb-1">Billing Cycle</span>
                        <p class="font-semibold text-[#0D1F1B] m-0">{{ subscription.billing_cycle_display }}</p>
                    </div>
                </div>

                <div v-if="subscription" class="mt-6 pt-6 border-t border-gray-100">
                    <div class="flex flex-wrap items-start justify-between gap-6">
                        <div class="flex flex-wrap gap-6">
                            <div v-if="subscription.started_at">
                                <span class="text-gray-500 text-sm block mb-1">Started</span>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ subscription.started_at }}</p>
                            </div>
                            <div v-if="subscription.next_billing_date && !subscription.is_cancelled">
                                <span class="text-gray-500 text-sm block mb-1">Next Billing</span>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ subscription.next_billing_date }}</p>
                            </div>
                            <div v-else-if="subscription.expires_at && !subscription.is_in_grace_period">
                                <span class="text-gray-500 text-sm block mb-1">Expires</span>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ subscription.expires_at }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm block mb-1">Status</span>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ subscription.status_label }}</p>
                            </div>
                        </div>

                        <!-- Cancel Button (for paid active subscriptions) -->
                        <div v-if="currentTier.value !== 'starter' && !subscription.is_cancelled && !subscription.is_on_trial">
                            <ConsoleButton
                                label="Cancel Subscription"
                                icon="pi pi-times"
                                variant="secondary"
                                severity="danger"
                                size="small"
                                @click="showCancelModal = true"
                            />
                        </div>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Payment Method Card (for paid tiers) -->
            <ConsoleFormCard v-if="currentTier.value !== 'starter'" class="mb-6">
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-[#0D1F1B] m-0">Payment Method</h3>
                        <AppLink v-if="paymentMethod" :href="route('provider.subscription.payment-method.edit')">
                            <ConsoleButton
                                label="Update"
                                icon="pi pi-pencil"
                                variant="secondary"
                                size="small"
                            />
                        </AppLink>
                    </div>
                </template>

                <div v-if="paymentMethod" class="flex items-center gap-4">
                    <div class="w-12 h-8 bg-gray-100 rounded flex items-center justify-center">
                        <i class="pi pi-credit-card text-gray-500"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-[#0D1F1B] m-0">{{ paymentMethod.card_display }}</p>
                        <p class="text-sm text-gray-500 m-0">Expires {{ paymentMethod.card_expiry }}</p>
                    </div>
                    <Tag v-if="paymentMethod.is_expired" value="Expired" severity="danger" />
                </div>

                <div v-else class="flex items-center gap-4 py-2">
                    <i class="pi pi-info-circle text-gray-400"></i>
                    <span class="text-gray-500">No payment method on file</span>
                    <AppLink :href="route('provider.subscription.payment-method.edit')">
                        <ConsoleButton
                            label="Add Payment Method"
                            icon="pi pi-plus"
                            size="small"
                        />
                    </AppLink>
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
            <div v-if="currentTier.value !== 'enterprise' && !subscription?.is_on_trial" class="bg-gradient-to-r from-[#0D1F1B] to-[#106B4F] rounded-xl p-6 lg:p-8 text-white">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold m-0 mb-2">Ready to upgrade?</h3>
                        <p class="m-0 text-white/80">
                            Unlock more features and grow your business with a higher tier plan.
                        </p>
                    </div>
                    <AppLink :href="route('provider.subscription.upgrade')">
                        <ConsoleButton
                            label="View Upgrade Options"
                            icon="pi pi-arrow-right"
                            icon-pos="right"
                            variant="secondary"
                            class="!bg-white !text-[#0D1F1B] !border-white whitespace-nowrap"
                        />
                    </AppLink>
                </div>
            </div>
        </div>

        <!-- Cancel Subscription Modal -->
        <Dialog
            v-model:visible="showCancelModal"
            modal
            header="Cancel Subscription"
            :style="{ width: '450px' }"
        >
            <div class="space-y-4">
                <p class="text-gray-600 m-0">
                    Are you sure you want to cancel your subscription? You'll lose access to these features:
                </p>

                <ul class="space-y-2 m-0 p-0 list-none">
                    <li v-for="feature in unavailableFeatures.slice(0, 4)" :key="feature.value" class="flex items-center gap-2 text-gray-600">
                        <i class="pi pi-times text-red-500 text-sm"></i>
                        <span>{{ feature.label }}</span>
                    </li>
                </ul>

                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 m-0">
                        <i class="pi pi-info-circle mr-1"></i>
                        Your subscription will remain active until <strong>{{ subscription?.expires_at }}</strong>.
                        After that, you'll be moved to the free Starter plan.
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <ConsoleButton
                        label="Keep Subscription"
                        variant="secondary"
                        @click="showCancelModal = false"
                    />
                    <ConsoleButton
                        label="Cancel Subscription"
                        severity="danger"
                        @click="cancelSubscription"
                        :loading="processing"
                    />
                </div>
            </template>
        </Dialog>
    </ConsoleLayout>
</template>

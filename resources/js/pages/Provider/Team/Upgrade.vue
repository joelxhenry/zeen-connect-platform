<script setup lang="ts">
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import { ConsolePageHeader, ConsoleButton } from '@/components/console';

interface Props {
    currentTier: string;
    currentTierLabel: string;
}

defineProps<Props>();
</script>

<template>
    <ConsoleLayout title="Team Members">
        <div class="w-full max-w-2xl mx-auto">
            <ConsolePageHeader
                title="Team Members"
                subtitle="Manage your team and delegate tasks"
            />

            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="pi pi-users text-purple-600 text-2xl"></i>
                </div>

                <h2 class="text-xl font-semibold text-[#0D1F1B] m-0 mb-2">
                    Upgrade to Add Team Members
                </h2>

                <p class="text-gray-600 m-0 mb-6 max-w-md mx-auto">
                    Team management is available on Premium and Enterprise plans.
                    Upgrade your subscription to invite team members and delegate bookings.
                </p>

                <div class="bg-gray-50 rounded-lg p-4 mb-6 max-w-sm mx-auto">
                    <p class="text-sm text-gray-500 m-0">Your current plan</p>
                    <p class="font-semibold text-[#0D1F1B] m-0">{{ currentTierLabel }}</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <AppLink :href="route('provider.subscription.upgrade')">
                        <ConsoleButton
                            label="View Upgrade Options"
                            icon="pi pi-arrow-up"
                        />
                    </AppLink>
                    <AppLink :href="route('provider.subscription.index')">
                        <ConsoleButton
                            label="View Subscription"
                            icon="pi pi-info-circle"
                            variant="secondary"
                        />
                    </AppLink>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

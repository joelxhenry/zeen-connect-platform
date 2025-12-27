<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleEmptyState,
    ConsoleAlertBanner,
    ConsoleButton,
    ConsoleDataCard,
} from '@/components/console';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import ConfirmDialog from 'primevue/confirmdialog';
import type { GatewaySetupIndexProps, GatewayConfig, GatewayOption } from '@/types/payments';

const props = defineProps<GatewaySetupIndexProps>();
const confirm = useConfirm();
const toast = useToast();

const verifyGateway = (gateway: GatewayConfig) => {
    router.post(`/payments/setup/${gateway.slug}/verify`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Verification Started',
                detail: 'Verifying your credentials...',
                life: 3000,
            });
        },
    });
};

const makePrimary = (gateway: GatewayConfig) => {
    router.post(`/payments/setup/${gateway.slug}/primary`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Updated',
                detail: `${gateway.name} is now your primary gateway`,
                life: 3000,
            });
        },
    });
};

const removeGateway = (gateway: GatewayConfig) => {
    confirm.require({
        message: `Are you sure you want to remove ${gateway.name}? You will need to set it up again to use it.`,
        header: 'Remove Gateway',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-500 !border-red-500',
        accept: () => {
            router.delete(`/payments/setup/${gateway.slug}`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Removed',
                        detail: `${gateway.name} has been removed`,
                        life: 3000,
                    });
                },
            });
        },
    });
};

const getVerificationSeverity = (status: string): 'success' | 'warn' | 'danger' | 'secondary' => {
    switch (status) {
        case 'verified':
            return 'success';
        case 'pending':
            return 'warn';
        case 'failed':
            return 'danger';
        default:
            return 'secondary';
    }
};
</script>

<template>
    <ConsoleLayout title="Payment Setup">
        <ConfirmDialog />

        <div class="w-full max-w-5xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Payment Setup"
                subtitle="Configure your payment gateways to receive payments from customers"
                back-href="/payments"
            />

            <!-- No Gateway Alert -->
            <ConsoleAlertBanner
                v-if="!hasGatewayConfigured"
                variant="warning"
                class="mb-6"
            >
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <span>You haven't set up a payment gateway yet. Choose one below to start receiving payments.</span>
                </div>
            </ConsoleAlertBanner>

            <!-- Configured Gateways -->
            <div v-if="configuredGateways.length > 0" class="mb-8">
                <h2 class="text-lg font-semibold text-[#0D1F1B] mb-4">Your Payment Gateways</h2>
                <div class="space-y-4">
                    <ConsoleDataCard
                        v-for="gateway in configuredGateways"
                        :key="gateway.id"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div class="w-12 h-12 rounded-xl bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                                <i :class="[gateway.icon, 'text-xl text-[#106B4F]']" />
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <h3 class="font-semibold text-[#0D1F1B] m-0">{{ gateway.name }}</h3>
                                    <Tag
                                        v-if="gateway.is_primary"
                                        value="Primary"
                                        severity="info"
                                        class="!text-xs"
                                    />
                                    <Tag
                                        :value="gateway.verification_status_label"
                                        :severity="getVerificationSeverity(gateway.verification_status)"
                                        class="!text-xs"
                                    />
                                </div>
                                <p class="text-sm text-gray-500 m-0">
                                    <span v-if="gateway.supports_split && gateway.supports_escrow">
                                        Supports split payments and scheduled payouts
                                    </span>
                                    <span v-else-if="gateway.supports_split">
                                        Supports split payments (direct deposits)
                                    </span>
                                    <span v-else>
                                        Supports scheduled payouts (escrow mode)
                                    </span>
                                </p>
                                <p v-if="gateway.merchant_account_id" class="text-xs text-gray-400 m-0 mt-1">
                                    Account: {{ gateway.merchant_account_id }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <Button
                                    v-if="gateway.is_pending"
                                    icon="pi pi-check-circle"
                                    label="Verify"
                                    size="small"
                                    severity="success"
                                    outlined
                                    @click="verifyGateway(gateway)"
                                />
                                <Button
                                    v-if="gateway.is_verified && !gateway.is_primary"
                                    icon="pi pi-star"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    v-tooltip="'Set as Primary'"
                                    @click="makePrimary(gateway)"
                                />
                                <ConsoleButton
                                    icon="pi pi-pencil"
                                    size="small"
                                    variant="secondary"
                                    outlined
                                    :href="`/payments/setup/${gateway.slug}/edit`"
                                    v-tooltip="'Edit'"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    size="small"
                                    severity="danger"
                                    outlined
                                    v-tooltip="'Remove'"
                                    @click="removeGateway(gateway)"
                                />
                            </div>
                        </div>

                        <template #footer>
                            <div class="flex items-center gap-4 text-xs text-gray-400">
                                <span v-if="gateway.created_at">
                                    <i class="pi pi-calendar mr-1" />
                                    Added {{ gateway.created_at }}
                                </span>
                                <span v-if="gateway.verified_at">
                                    <i class="pi pi-check mr-1" />
                                    Verified {{ gateway.verified_at }}
                                </span>
                            </div>
                        </template>
                    </ConsoleDataCard>
                </div>
            </div>

            <!-- Available Gateways -->
            <div v-if="availableGateways.length > 0">
                <h2 class="text-lg font-semibold text-[#0D1F1B] mb-4">
                    {{ hasGatewayConfigured ? 'Add Another Gateway' : 'Available Payment Gateways' }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <ConsoleFormCard
                        v-for="gateway in availableGateways"
                        :key="gateway.slug"
                        class="hover:shadow-md transition-shadow cursor-pointer"
                        @click="router.visit(`/payments/setup/${gateway.slug}`)"
                    >
                        <div class="text-center">
                            <!-- Icon -->
                            <div class="w-16 h-16 rounded-2xl bg-[#106B4F]/10 flex items-center justify-center mx-auto mb-4">
                                <i :class="[gateway.icon, 'text-3xl text-[#106B4F]']" />
                            </div>

                            <!-- Name & Description -->
                            <h3 class="font-semibold text-[#0D1F1B] m-0 mb-2">{{ gateway.name }}</h3>
                            <p class="text-sm text-gray-500 m-0 mb-4">{{ gateway.description }}</p>

                            <!-- Features -->
                            <div class="flex flex-wrap justify-center gap-2 mb-4">
                                <Tag
                                    v-if="gateway.supports_split"
                                    value="Split Payments"
                                    severity="success"
                                    class="!text-xs"
                                />
                                <Tag
                                    v-if="gateway.supports_escrow"
                                    value="Scheduled Payouts"
                                    severity="info"
                                    class="!text-xs"
                                />
                            </div>

                            <!-- Setup Button -->
                            <ConsoleButton
                                label="Set Up"
                                icon="pi pi-arrow-right"
                                icon-pos="right"
                                class="w-full"
                            />
                        </div>
                    </ConsoleFormCard>
                </div>
            </div>

            <!-- All Configured -->
            <ConsoleEmptyState
                v-if="availableGateways.length === 0 && hasGatewayConfigured"
                icon="pi pi-check-circle"
                title="All gateways configured"
                description="You have set up all available payment gateways."
            />
        </div>
    </ConsoleLayout>
</template>

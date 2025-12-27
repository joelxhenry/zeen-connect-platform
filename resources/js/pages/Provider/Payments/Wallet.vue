<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleStatCard,
    ConsoleEmptyState,
    ConsoleAlertBanner,
    ConsoleButton,
} from '@/components/console';
import AppLink from '@/components/common/AppLink.vue';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import type { WalletProps } from '@/types/payments';

const props = defineProps<WalletProps>();
const confirm = useConfirm();
const toast = useToast();

const selectedType = ref(props.filters.type);

// Payout request dialog
const showPayoutDialog = ref(false);
const payoutAmount = ref(props.balance.available);

// Payout settings dialog
const showSettingsDialog = ref(false);
const settingsForm = useForm({
    schedule: props.payoutSettings.schedule,
    minimum_amount: props.payoutSettings.minimum_amount,
});

watch(selectedType, (newType) => {
    router.get('/payments/wallet', { type: newType }, {
        preserveState: true,
        preserveScroll: true,
    });
});

const requestPayout = () => {
    router.post('/payments/payout/request', {
        amount: payoutAmount.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showPayoutDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Payout Requested',
                detail: 'Your payout request has been submitted.',
                life: 3000,
            });
        },
        onError: (errors: Record<string, string>) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.payout || 'Failed to request payout.',
                life: 5000,
            });
        },
    });
};

const cancelPayout = (payout: { uuid: string; amount_display: string }) => {
    confirm.require({
        message: `Are you sure you want to cancel the payout of ${payout.amount_display}?`,
        header: 'Cancel Payout',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-500 !border-red-500',
        accept: () => {
            router.post(`/payments/payout/${payout.uuid}/cancel`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Cancelled',
                        detail: 'Payout has been cancelled.',
                        life: 3000,
                    });
                },
            });
        },
    });
};

const saveSettings = () => {
    settingsForm.put('/payments/payout/schedule', {
        preserveScroll: true,
        onSuccess: () => {
            showSettingsDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Settings Updated',
                detail: 'Your payout settings have been saved.',
                life: 3000,
            });
        },
    });
};

const getLedgerTypeColor = (type: string): string => {
    switch (type) {
        case 'credit':
            return 'text-green-600';
        case 'debit':
            return 'text-red-600';
        case 'hold':
            return 'text-yellow-600';
        case 'release':
            return 'text-blue-600';
        default:
            return 'text-gray-600';
    }
};

const scheduleOptions = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
];
</script>

<template>
    <ConsoleLayout title="Wallet">
        <ConfirmDialog />

        <div class="w-full max-w-5xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Wallet & Payouts"
                subtitle="Manage your balance and payout settings"
                back-href="/payments"
            />

            <!-- No Gateway Warning -->
            <ConsoleAlertBanner
                v-if="!hasGateway"
                variant="warning"
                class="mb-6"
            >
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <span>Set up a payment method to request payouts.</span>
                    <ConsoleButton
                        label="Set Up"
                        icon="pi pi-arrow-right"
                        icon-pos="right"
                        size="small"
                        href="/payments/setup"
                    />
                </div>
            </ConsoleAlertBanner>

            <!-- Balance Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <ConsoleStatCard
                    title="Total Balance"
                    :value="balance.total_display"
                    icon="pi pi-wallet"
                    icon-color="primary"
                />
                <ConsoleStatCard
                    title="Available"
                    :value="balance.available_display"
                    icon="pi pi-check-circle"
                    icon-color="success"
                />
                <ConsoleStatCard
                    title="Held"
                    :value="balance.held_display"
                    icon="pi pi-lock"
                    icon-color="warning"
                />
            </div>

            <!-- Two Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Request Payout -->
                <ConsoleFormCard title="Request Payout" icon="pi pi-send">
                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500 m-0 mb-1">Available for payout</p>
                            <p class="text-2xl font-bold text-[#106B4F] m-0">
                                {{ balance.available_display }}
                            </p>
                        </div>

                        <ConsoleButton
                            label="Request Payout"
                            icon="pi pi-send"
                            class="w-full"
                            :disabled="balance.available <= 0 || !hasGateway"
                            @click="showPayoutDialog = true"
                        />

                        <p v-if="balance.pending_payout && balance.pending_payout > 0" class="text-sm text-gray-500 m-0 text-center">
                            {{ balance.pending_payout_display }} pending in scheduled payouts
                        </p>
                    </div>
                </ConsoleFormCard>

                <!-- Payout Settings -->
                <ConsoleFormCard title="Payout Settings" icon="pi pi-cog">
                    <template #header-actions>
                        <Button
                            icon="pi pi-pencil"
                            size="small"
                            severity="secondary"
                            outlined
                            v-tooltip="'Edit Settings'"
                            @click="showSettingsDialog = true"
                        />
                    </template>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Schedule</span>
                            <span class="font-medium text-[#0D1F1B]">{{ payoutSettings.schedule_label }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Minimum Amount</span>
                            <span class="font-medium text-[#0D1F1B]">{{ payoutSettings.minimum_amount_display }}</span>
                        </div>
                        <div v-if="payoutSettings.next_payout_date" class="flex justify-between items-center">
                            <span class="text-gray-500">Next Payout</span>
                            <span class="font-medium text-[#0D1F1B]">{{ payoutSettings.next_payout_date }}</span>
                        </div>
                    </div>
                </ConsoleFormCard>
            </div>

            <!-- Scheduled Payouts -->
            <ConsoleFormCard
                v-if="scheduledPayouts.length > 0"
                title="Scheduled Payouts"
                icon="pi pi-calendar"
                class="mb-6"
            >
                <div class="space-y-3">
                    <div
                        v-for="payout in scheduledPayouts"
                        :key="payout.uuid"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-yellow-500/10 flex items-center justify-center">
                                <i class="pi pi-clock text-yellow-600" />
                            </div>
                            <div>
                                <p class="font-semibold text-[#0D1F1B] m-0">{{ payout.amount_display }}</p>
                                <p class="text-sm text-gray-500 m-0">Scheduled for {{ payout.scheduled_for }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Tag :value="payout.status_label" :severity="payout.status === 'pending' ? 'warn' : 'info'" />
                            <Button
                                v-if="payout.can_cancel"
                                icon="pi pi-times"
                                size="small"
                                severity="danger"
                                outlined
                                v-tooltip="'Cancel'"
                                @click="cancelPayout(payout)"
                            />
                        </div>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Ledger History -->
            <ConsoleFormCard title="Transaction History" icon="pi pi-list">
                <template #header-actions>
                    <Select
                        v-model="selectedType"
                        :options="typeOptions"
                        optionLabel="label"
                        optionValue="value"
                        class="w-40"
                    />
                </template>

                <ConsoleEmptyState
                    v-if="!ledgerEntries.data?.length"
                    icon="pi pi-inbox"
                    title="No transactions"
                    description="Your transaction history will appear here."
                    size="compact"
                />

                <div v-else class="space-y-2">
                    <div
                        v-for="entry in ledgerEntries.data"
                        :key="entry.uuid"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'w-8 h-8 rounded-lg flex items-center justify-center',
                                    entry.type === 'credit' ? 'bg-green-500/10' :
                                    entry.type === 'debit' ? 'bg-red-500/10' :
                                    entry.type === 'hold' ? 'bg-yellow-500/10' :
                                    'bg-blue-500/10'
                                ]"
                            >
                                <i :class="[entry.type_icon, getLedgerTypeColor(entry.type), 'text-sm']" />
                            </div>
                            <div>
                                <p class="font-medium text-[#0D1F1B] m-0 text-sm">{{ entry.description }}</p>
                                <p class="text-xs text-gray-500 m-0">{{ entry.created_at }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p :class="['font-semibold m-0', getLedgerTypeColor(entry.type)]">
                                {{ entry.amount_display }}
                            </p>
                            <p class="text-xs text-gray-400 m-0">
                                Balance: {{ entry.balance_after_display }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="ledgerEntries.meta?.last_page && ledgerEntries.meta.last_page > 1" class="flex justify-center gap-2 mt-4">
                    <AppLink
                        v-for="link in ledgerEntries.meta.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 rounded text-sm no-underline',
                            link.active ? 'bg-[#106B4F] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                            !link.url && 'opacity-50 pointer-events-none',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </ConsoleFormCard>
        </div>

        <!-- Request Payout Dialog -->
        <Dialog
            v-model:visible="showPayoutDialog"
            header="Request Payout"
            :modal="true"
            class="w-full max-w-md"
        >
            <div class="space-y-4">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-500 m-0 mb-1">Available Balance</p>
                    <p class="text-2xl font-bold text-[#106B4F] m-0">{{ balance.available_display }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payout Amount</label>
                    <InputNumber
                        v-model="payoutAmount"
                        mode="currency"
                        currency="USD"
                        :max="balance.available"
                        :min="10"
                        class="w-full"
                    />
                    <small class="text-gray-500">Minimum payout: $10.00</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showPayoutDialog = false" />
                <Button
                    label="Request Payout"
                    icon="pi pi-send"
                    :disabled="payoutAmount < 10 || payoutAmount > balance.available"
                    @click="requestPayout"
                />
            </template>
        </Dialog>

        <!-- Payout Settings Dialog -->
        <Dialog
            v-model:visible="showSettingsDialog"
            header="Payout Settings"
            :modal="true"
            class="w-full max-w-md"
        >
            <form @submit.prevent="saveSettings" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payout Schedule</label>
                    <Select
                        v-model="settingsForm.schedule"
                        :options="scheduleOptions"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    />
                    <small class="text-gray-500">How often you'd like to receive payouts</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Payout Amount</label>
                    <InputNumber
                        v-model="settingsForm.minimum_amount"
                        mode="currency"
                        currency="USD"
                        :min="10"
                        :max="10000"
                        class="w-full"
                    />
                    <small class="text-gray-500">Only process payouts when balance exceeds this amount</small>
                </div>
            </form>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showSettingsDialog = false" />
                <Button
                    label="Save Settings"
                    icon="pi pi-check"
                    :loading="settingsForm.processing"
                    @click="saveSettings"
                />
            </template>
        </Dialog>
    </ConsoleLayout>
</template>

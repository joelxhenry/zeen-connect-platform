<script setup lang="ts">
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleFormSection,
    ConsoleAlertBanner,
    ConsoleButton,
} from '@/components/console';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import RadioButton from 'primevue/radiobutton';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import ConfirmDialog from 'primevue/confirmdialog';
import type { BankingInfoProps } from '@/types/payments';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

const props = defineProps<BankingInfoProps>();
const confirm = useConfirm();
const toast = useToast();

const form = useForm({
    bank_name: props.bankingInfo.bank_name ?? '',
    bank_account_number: props.bankingInfo.bank_account_number ?? '',
    bank_account_holder_name: props.bankingInfo.bank_account_holder_name ?? '',
    bank_branch_code: props.bankingInfo.bank_branch_code ?? '',
    bank_account_type: props.bankingInfo.bank_account_type ?? 'savings',
});

// Convert banks object to array for Select component
const bankOptions = computed(() => {
    const options = Object.entries(props.banks).map(([code, name]) => ({
        value: code,
        label: name,
    }));
    options.push({ value: 'OTHER', label: 'Other' });
    return options;
});

const hasExistingInfo = computed(() => props.bankingInfo.has_banking_info);

const submit = () => {
    form.put(resolveUrl(provider.payments.bankingInfo.update().url), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Saved',
                detail: 'Banking information has been updated',
                life: 3000,
            });
        },
    });
};

const removeBankingInfo = () => {
    confirm.require({
        message: 'Are you sure you want to remove your banking information? You will need to add it again to receive escrow payouts.',
        header: 'Remove Banking Information',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-500 !border-red-500',
        accept: () => {
            router.delete(resolveUrl(provider.payments.bankingInfo.destroy().url), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Removed',
                        detail: 'Banking information has been removed',
                        life: 3000,
                    });
                },
            });
        },
    });
};
</script>

<template>
    <ConsoleLayout title="Banking Information">
        <ConfirmDialog />

        <div class="w-full max-w-2xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Banking Information"
                subtitle="Add your bank account details for receiving escrow payouts"
                :back-href="provider.payments.setup.index().url"
            />

            <!-- WiPay Account Notice -->
            <ConsoleAlertBanner
                v-if="hasWiPayAccount"
                variant="info"
                class="mb-6"
            >
                <div class="flex items-center gap-2">
                    <i class="pi pi-info-circle" />
                    <span>
                        You have a WiPay account linked. Banking information is optional but can be used as an alternative payout method.
                    </span>
                </div>
            </ConsoleAlertBanner>

            <!-- Verification Status -->
            <ConsoleAlertBanner
                v-if="hasExistingInfo && bankingInfo.is_verified"
                variant="success"
                class="mb-6"
            >
                <div class="flex items-center gap-2">
                    <i class="pi pi-check-circle" />
                    <span>
                        Your banking information has been verified.
                        <span v-if="bankingInfo.verified_at" class="text-sm opacity-75">
                            ({{ bankingInfo.verified_at }})
                        </span>
                    </span>
                </div>
            </ConsoleAlertBanner>

            <ConsoleAlertBanner
                v-if="hasExistingInfo && !bankingInfo.is_verified"
                variant="warning"
                class="mb-6"
            >
                <div class="flex items-center gap-2">
                    <i class="pi pi-clock" />
                    <span>
                        Your banking information is pending verification. This usually takes 1-2 business days.
                    </span>
                </div>
            </ConsoleAlertBanner>

            <!-- Banking Info Description -->
            <ConsoleFormCard class="mb-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                        <i class="pi pi-building text-xl text-[#106B4F]" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-[#0D1F1B] m-0">Bank Account for Payouts</h3>
                            <Tag v-if="hasExistingInfo" value="Configured" severity="success" class="!text-xs" />
                        </div>
                        <p class="text-sm text-gray-500 m-0">
                            Your bank account will be used to receive payouts from escrow payments.
                            Funds are collected by the platform and paid out on a scheduled basis.
                        </p>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Banking Form -->
            <ConsoleFormCard title="Bank Account Details" icon="pi pi-credit-card">
                <form @submit.prevent="submit" class="space-y-4">
                    <ConsoleFormSection>
                        <div>
                            <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Bank Name *
                            </label>
                            <Select
                                id="bank_name"
                                v-model="form.bank_name"
                                :options="bankOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.bank_name }"
                                placeholder="Select your bank"
                            />
                            <small v-if="form.errors.bank_name" class="text-red-500">
                                {{ form.errors.bank_name }}
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <ConsoleFormSection>
                        <div>
                            <label for="bank_account_holder_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Account Holder Name *
                            </label>
                            <InputText
                                id="bank_account_holder_name"
                                v-model="form.bank_account_holder_name"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.bank_account_holder_name }"
                                placeholder="Name as it appears on your account"
                            />
                            <small v-if="form.errors.bank_account_holder_name" class="text-red-500">
                                {{ form.errors.bank_account_holder_name }}
                            </small>
                            <small class="text-gray-500 block mt-1">
                                Must match the name on your bank account exactly
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <ConsoleFormSection>
                        <div>
                            <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">
                                Account Number *
                            </label>
                            <InputText
                                id="bank_account_number"
                                v-model="form.bank_account_number"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.bank_account_number }"
                                placeholder="Your bank account number"
                            />
                            <small v-if="form.errors.bank_account_number" class="text-red-500">
                                {{ form.errors.bank_account_number }}
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <ConsoleFormSection>
                        <div>
                            <label for="bank_branch_code" class="block text-sm font-medium text-gray-700 mb-1">
                                Branch Code
                                <span class="text-gray-400 font-normal">(Optional)</span>
                            </label>
                            <InputText
                                id="bank_branch_code"
                                v-model="form.bank_branch_code"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.bank_branch_code }"
                                placeholder="e.g., 001"
                            />
                            <small v-if="form.errors.bank_branch_code" class="text-red-500">
                                {{ form.errors.bank_branch_code }}
                            </small>
                            <small class="text-gray-500 block mt-1">
                                Some banks require a branch code for transfers
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <ConsoleFormSection>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Account Type *
                            </label>
                            <div class="flex gap-6">
                                <div class="flex items-center">
                                    <RadioButton
                                        v-model="form.bank_account_type"
                                        inputId="type_savings"
                                        value="savings"
                                        :class="{ 'p-invalid': form.errors.bank_account_type }"
                                    />
                                    <label for="type_savings" class="ml-2 text-sm text-gray-700 cursor-pointer">
                                        Savings
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <RadioButton
                                        v-model="form.bank_account_type"
                                        inputId="type_checking"
                                        value="checking"
                                        :class="{ 'p-invalid': form.errors.bank_account_type }"
                                    />
                                    <label for="type_checking" class="ml-2 text-sm text-gray-700 cursor-pointer">
                                        Checking / Current
                                    </label>
                                </div>
                            </div>
                            <small v-if="form.errors.bank_account_type" class="text-red-500 block mt-1">
                                {{ form.errors.bank_account_type }}
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <!-- Actions -->
                    <div class="flex justify-between items-center gap-3 pt-4 border-t border-gray-100">
                        <div>
                            <ConsoleButton
                                v-if="hasExistingInfo"
                                label="Remove"
                                icon="pi pi-trash"
                                variant="danger"
                                outlined
                                type="button"
                                @click="removeBankingInfo"
                            />
                        </div>
                        <div class="flex gap-3">
                            <ConsoleButton
                                label="Cancel"
                                variant="secondary"
                                :href="provider.payments.setup.index().url"
                            />
                            <ConsoleButton
                                :label="hasExistingInfo ? 'Update' : 'Save'"
                                icon="pi pi-check"
                                type="submit"
                                :loading="form.processing"
                            />
                        </div>
                    </div>
                </form>
            </ConsoleFormCard>

            <!-- Info Card -->
            <ConsoleFormCard title="About Escrow Payouts" icon="pi pi-info-circle" class="mt-6">
                <ul class="space-y-3 m-0 p-0 list-none">
                    <li class="flex items-start gap-3">
                        <i class="pi pi-calendar text-[#106B4F] mt-0.5" />
                        <div>
                            <p class="font-medium text-[#0D1F1B] m-0">Weekly Payouts</p>
                            <p class="text-sm text-gray-500 m-0">
                                Funds are paid out every Friday for completed bookings
                            </p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="pi pi-shield text-[#106B4F] mt-0.5" />
                        <div>
                            <p class="font-medium text-[#0D1F1B] m-0">Secure Processing</p>
                            <p class="text-sm text-gray-500 m-0">
                                Payouts are processed securely through WiPay or direct bank transfer
                            </p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="pi pi-wallet text-[#106B4F] mt-0.5" />
                        <div>
                            <p class="font-medium text-[#0D1F1B] m-0">Minimum Payout</p>
                            <p class="text-sm text-gray-500 m-0">
                                A minimum balance of $1,000 JMD is required for payouts
                            </p>
                        </div>
                    </li>
                </ul>
            </ConsoleFormCard>
        </div>
    </ConsoleLayout>
</template>

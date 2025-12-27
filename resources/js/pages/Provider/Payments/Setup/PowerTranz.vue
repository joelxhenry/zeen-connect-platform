<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleFormSection,
    ConsoleAlertBanner,
    ConsoleButton,
} from '@/components/console';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import type { GatewaySetupFormProps } from '@/types/payments';

const props = defineProps<GatewaySetupFormProps>();

const form = useForm({
    merchant_id: props.config?.merchant_account_id ?? '',
    password: '',
    terminal_id: '',
    environment: 'sandbox' as 'sandbox' | 'production',
});

const environmentOptions = [
    { value: 'sandbox', label: 'Sandbox (Testing)' },
    { value: 'production', label: 'Production (Live)' },
];

const submit = () => {
    if (props.isEdit) {
        form.put(`/payments/setup/powertranz`, {
            preserveScroll: true,
        });
    } else {
        form.post(`/payments/setup/powertranz`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <ConsoleLayout :title="isEdit ? 'Edit PowerTranz' : 'Set Up PowerTranz'">
        <div class="w-full max-w-2xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                :title="isEdit ? 'Edit PowerTranz Configuration' : 'Set Up PowerTranz'"
                :subtitle="isEdit ? 'Update your PowerTranz account credentials' : 'Connect your PowerTranz account to receive payments'"
                back-href="/payments/setup"
            />

            <!-- Gateway Info -->
            <ConsoleFormCard class="mb-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                        <i :class="[gateway.icon, 'text-xl text-[#106B4F]']" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-[#0D1F1B] m-0">{{ gateway.name }}</h3>
                            <Tag v-if="gateway.supports_escrow" value="Escrow Only" severity="info" class="!text-xs" />
                        </div>
                        <p class="text-sm text-gray-500 m-0">{{ gateway.description }}</p>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Escrow Mode Info -->
            <ConsoleAlertBanner
                variant="info"
                class="mb-6"
            >
                <div class="flex items-start gap-2">
                    <i class="pi pi-info-circle mt-0.5" />
                    <div>
                        <p class="font-medium m-0 mb-1">Escrow Mode Only</p>
                        <p class="text-sm m-0">
                            PowerTranz operates in escrow mode. The platform collects the full payment and you'll receive
                            scheduled payouts based on your payout settings.
                        </p>
                    </div>
                </div>
            </ConsoleAlertBanner>

            <!-- Status Alert (for edit mode) -->
            <ConsoleAlertBanner
                v-if="isEdit && config?.is_pending"
                variant="warning"
                class="mb-6"
            >
                Your credentials are pending verification. Click "Verify" after saving to confirm your account.
            </ConsoleAlertBanner>

            <ConsoleAlertBanner
                v-if="isEdit && config?.is_failed"
                variant="danger"
                class="mb-6"
            >
                Verification failed. Please check your credentials and try again.
            </ConsoleAlertBanner>

            <!-- Credentials Form -->
            <ConsoleFormCard title="Account Credentials" icon="pi pi-key">
                <form @submit.prevent="submit" class="space-y-4">
                    <ConsoleFormSection :columns="2">
                        <div>
                            <label for="merchant_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Merchant ID *
                            </label>
                            <InputText
                                id="merchant_id"
                                v-model="form.merchant_id"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.merchant_id }"
                                placeholder="Your PowerTranz merchant ID"
                            />
                            <small v-if="form.errors.merchant_id" class="text-red-500">
                                {{ form.errors.merchant_id }}
                            </small>
                        </div>
                        <div>
                            <label for="terminal_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Terminal ID *
                            </label>
                            <InputText
                                id="terminal_id"
                                v-model="form.terminal_id"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.terminal_id }"
                                placeholder="Your terminal ID"
                            />
                            <small v-if="form.errors.terminal_id" class="text-red-500">
                                {{ form.errors.terminal_id }}
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <ConsoleFormSection>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password *
                            </label>
                            <Password
                                id="password"
                                v-model="form.password"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.password }"
                                :feedback="false"
                                toggleMask
                                placeholder="Enter your API password"
                                inputClass="w-full"
                            />
                            <small v-if="form.errors.password" class="text-red-500">
                                {{ form.errors.password }}
                            </small>
                            <small v-if="isEdit" class="text-gray-500 block mt-1">
                                Leave blank to keep your current password
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <ConsoleFormSection>
                        <div>
                            <label for="environment" class="block text-sm font-medium text-gray-700 mb-1">
                                Environment *
                            </label>
                            <Select
                                id="environment"
                                v-model="form.environment"
                                :options="environmentOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.environment }"
                            />
                            <small v-if="form.errors.environment" class="text-red-500">
                                {{ form.errors.environment }}
                            </small>
                            <small class="text-gray-500 block mt-1">
                                Use Sandbox for testing, Production for live payments
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <ConsoleButton
                            label="Cancel"
                            variant="secondary"
                            href="/payments/setup"
                        />
                        <ConsoleButton
                            :label="isEdit ? 'Save Changes' : 'Save & Continue'"
                            icon="pi pi-check"
                            type="submit"
                            :loading="form.processing"
                        />
                    </div>
                </form>
            </ConsoleFormCard>

            <!-- Features Info -->
            <ConsoleFormCard title="What you get with PowerTranz" icon="pi pi-info-circle" class="mt-6">
                <ul class="space-y-3 m-0 p-0 list-none">
                    <li v-for="feature in gateway.features" :key="feature.label" class="flex items-start gap-3">
                        <i :class="[feature.icon, 'text-[#106B4F] mt-0.5']" />
                        <div>
                            <p class="font-medium text-[#0D1F1B] m-0">{{ feature.label }}</p>
                            <p class="text-sm text-gray-500 m-0">{{ feature.description }}</p>
                        </div>
                    </li>
                </ul>
            </ConsoleFormCard>
        </div>
    </ConsoleLayout>
</template>

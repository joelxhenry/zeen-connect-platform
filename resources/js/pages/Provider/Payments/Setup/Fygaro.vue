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
    api_key: '',
    secret_key: '',
    environment: 'sandbox' as 'sandbox' | 'production',
});

const environmentOptions = [
    { value: 'sandbox', label: 'Sandbox (Testing)' },
    { value: 'production', label: 'Production (Live)' },
];

const submit = () => {
    if (props.isEdit) {
        form.put(`/payments/setup/fygaro`, {
            preserveScroll: true,
        });
    } else {
        form.post(`/payments/setup/fygaro`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <ConsoleLayout :title="isEdit ? 'Edit Fygaro' : 'Set Up Fygaro'">
        <div class="w-full max-w-2xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                :title="isEdit ? 'Edit Fygaro Configuration' : 'Set Up Fygaro'"
                :subtitle="isEdit ? 'Update your Fygaro account credentials' : 'Connect your Fygaro account to receive payments'"
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
                            <Tag v-if="gateway.supports_split" value="Split Payments" severity="success" class="!text-xs" />
                            <Tag v-if="gateway.supports_escrow" value="Escrow" severity="info" class="!text-xs" />
                        </div>
                        <p class="text-sm text-gray-500 m-0">{{ gateway.description }}</p>
                    </div>
                </div>
            </ConsoleFormCard>

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
                    <ConsoleFormSection>
                        <div>
                            <label for="merchant_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Merchant ID *
                            </label>
                            <InputText
                                id="merchant_id"
                                v-model="form.merchant_id"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.merchant_id }"
                                placeholder="Your Fygaro merchant ID"
                            />
                            <small v-if="form.errors.merchant_id" class="text-red-500">
                                {{ form.errors.merchant_id }}
                            </small>
                            <small class="text-gray-500 block mt-1">
                                Found in your Fygaro dashboard under API Settings
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <ConsoleFormSection :columns="2">
                        <div>
                            <label for="api_key" class="block text-sm font-medium text-gray-700 mb-1">
                                API Key *
                            </label>
                            <Password
                                id="api_key"
                                v-model="form.api_key"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.api_key }"
                                :feedback="false"
                                toggleMask
                                placeholder="Enter your API key"
                                inputClass="w-full"
                            />
                            <small v-if="form.errors.api_key" class="text-red-500">
                                {{ form.errors.api_key }}
                            </small>
                        </div>
                        <div>
                            <label for="secret_key" class="block text-sm font-medium text-gray-700 mb-1">
                                Secret Key *
                            </label>
                            <Password
                                id="secret_key"
                                v-model="form.secret_key"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.secret_key }"
                                :feedback="false"
                                toggleMask
                                placeholder="Enter your secret key"
                                inputClass="w-full"
                            />
                            <small v-if="form.errors.secret_key" class="text-red-500">
                                {{ form.errors.secret_key }}
                            </small>
                        </div>
                    </ConsoleFormSection>

                    <small v-if="isEdit" class="text-gray-500 block">
                        Leave API Key and Secret Key blank to keep your current credentials
                    </small>

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
            <ConsoleFormCard title="What you get with Fygaro" icon="pi pi-info-circle" class="mt-6">
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

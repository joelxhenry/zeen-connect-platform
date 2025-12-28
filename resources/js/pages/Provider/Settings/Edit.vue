<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleFormSection,
    ConsoleButton,
} from '@/components/console';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import RadioButton from 'primevue/radiobutton';
import { useToast } from 'primevue/usetoast';
import SettingsController from '@/actions/App/Domains/Provider/Controllers/SettingsController';
import FeeBreakdownPreview from '@/components/provider/FeeBreakdownPreview.vue';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface BookingSettings {
    requires_approval: boolean;
    deposit_type: 'none' | 'percentage';
    deposit_amount: number | null;
    cancellation_policy: 'flexible' | 'moderate' | 'strict';
    advance_booking_days: number;
    min_booking_notice_hours: number;
}

interface TierRestrictions {
    tier: string;
    tier_label: string;
    zeen_fee_rate: number;
    gateway_fee_rate: number;
    total_fee_rate: number;
    team_slots: number | 'unlimited';
    monthly_price: number;
    minimum_deposit_percentage: number;
    can_disable_deposit: boolean;
    can_customize_deposit: boolean;
    deposit_help_text: string;
}

interface Props {
    bookingSettings: BookingSettings;
    feePayer: 'provider' | 'client';
    tierRestrictions: TierRestrictions;
}

const props = defineProps<Props>();
const toast = useToast();

// Initialize deposit amount with minimum if needed
const initialDepositAmount = props.bookingSettings.deposit_amount !== null
    ? Math.max(props.bookingSettings.deposit_amount, props.tierRestrictions.minimum_deposit_percentage)
    : props.tierRestrictions.minimum_deposit_percentage;

const form = useForm({
    requires_approval: props.bookingSettings.requires_approval,
    deposit_type: props.bookingSettings.deposit_type,
    deposit_amount: initialDepositAmount,
    cancellation_policy: props.bookingSettings.cancellation_policy,
    advance_booking_days: props.bookingSettings.advance_booking_days,
    min_booking_notice_hours: props.bookingSettings.min_booking_notice_hours,
    fee_payer: props.feePayer,
});

// Build deposit type options based on tier restrictions
const depositTypeOptions = computed(() => {
    const options: Array<{ label: string; value: string; disabled?: boolean }> = [];

    if (props.tierRestrictions.can_disable_deposit) {
        options.push({ label: 'No deposit required', value: 'none' });
    } else {
        options.push({ label: 'No deposit required', value: 'none', disabled: true });
    }

    options.push({ label: 'Percentage of total', value: 'percentage' });

    return options;
});

const cancellationPolicyOptions = [
    { label: 'Flexible - Full refund 24h before', value: 'flexible' },
    { label: 'Moderate - Full refund 48h before', value: 'moderate' },
    { label: 'Strict - 50% refund up to 1 week before', value: 'strict' },
];

const showDepositAmount = computed(() => form.deposit_type === 'percentage');

// Ensure deposit amount meets minimum when switching to percentage
watch(() => form.deposit_type, (newType) => {
    if (newType === 'percentage') {
        const min = props.tierRestrictions.minimum_deposit_percentage;
        if (form.deposit_amount === null || form.deposit_amount < min) {
            form.deposit_amount = min;
        }
    }
});

const submit = () => {
    form.put(resolveUrl(provider.settings.booking().url), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Booking settings updated successfully',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update booking settings',
                life: 3000,
            });
        },
    });
};
</script>

<template>
    <ConsoleLayout title="Settings">
        <div class="w-full max-w-3xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader title="Settings"
                subtitle="Configure your default booking settings. These will apply to all services unless overridden." />

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Booking Settings Card -->
                <ConsoleFormCard title="Default Booking Settings" icon="pi pi-cog">
                    <div class="space-y-5">
                        <p class="text-sm text-gray-500 m-0">
                            These settings will be used as defaults for all your services. You can override them on
                            individual services if needed.
                        </p>

                        <!-- Requires Approval -->
                        <ConsoleFormSection highlighted>
                            <div class="flex items-start gap-3">
                                <InputSwitch v-model="form.requires_approval" class="mt-0.5" />
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Require approval for bookings
                                    </label>
                                    <p class="text-xs text-gray-500 m-0 mt-1">
                                        When enabled, you'll need to manually confirm each booking request before it's
                                        confirmed. Clients will receive a pending status until you approve.
                                    </p>
                                </div>
                            </div>
                        </ConsoleFormSection>

                        <!-- Deposit Settings -->
                        <div class="space-y-4">
                            <div>
                                <label for="deposit_type" class="block text-sm font-medium text-gray-700 mb-1">
                                    Deposit Requirement
                                </label>
                                <Select id="deposit_type" v-model="form.deposit_type" :options="depositTypeOptions"
                                    optionLabel="label" optionValue="value" optionDisabled="disabled" class="w-full"
                                    :class="{ 'p-invalid': form.errors.deposit_type }" />
                                <small class="text-xs text-gray-500 mt-1 block">
                                    {{ tierRestrictions.deposit_help_text }}
                                </small>
                                <small v-if="form.errors.deposit_type" class="text-red-500 block mt-1">
                                    {{ form.errors.deposit_type }}
                                </small>
                            </div>

                            <!-- Deposit Percentage (conditional) -->
                            <div v-if="showDepositAmount">
                                <label for="deposit_amount" class="block text-sm font-medium text-gray-700 mb-1">
                                    Deposit Percentage
                                </label>
                                <InputNumber id="deposit_amount" v-model="form.deposit_amount"
                                    mode="decimal" suffix="%"
                                    :min="tierRestrictions.minimum_deposit_percentage"
                                    :max="100" class="w-full"
                                    :class="{ 'p-invalid': form.errors.deposit_amount }" />
                                <small class="text-xs text-gray-500 mt-1 block">
                                    Minimum {{ tierRestrictions.minimum_deposit_percentage }}% required for your tier
                                </small>
                                <small v-if="form.errors.deposit_amount" class="text-red-500 block mt-1">
                                    {{ form.errors.deposit_amount }}
                                </small>
                            </div>
                        </div>

                        <!-- Cancellation Policy -->
                        <div>
                            <label for="cancellation_policy" class="block text-sm font-medium text-gray-700 mb-1">
                                Cancellation Policy
                            </label>
                            <Select id="cancellation_policy" v-model="form.cancellation_policy"
                                :options="cancellationPolicyOptions" optionLabel="label" optionValue="value"
                                class="w-full" />
                            <small class="text-xs text-gray-500 mt-1 block">
                                Define when clients can cancel and receive a refund
                            </small>
                        </div>

                        <!-- Booking Windows -->
                        <ConsoleFormSection :columns="2">
                            <div>
                                <label for="advance_booking_days" class="block text-sm font-medium text-gray-700 mb-1">
                                    Advance Booking (days)
                                </label>
                                <InputNumber id="advance_booking_days" v-model="form.advance_booking_days" :min="1"
                                    :max="365" class="w-full"
                                    :class="{ 'p-invalid': form.errors.advance_booking_days }" />
                                <small class="text-xs text-gray-500 mt-1 block">
                                    How far in advance clients can book (1-365 days)
                                </small>
                                <small v-if="form.errors.advance_booking_days" class="text-red-500">
                                    {{ form.errors.advance_booking_days }}
                                </small>
                            </div>
                            <div>
                                <label for="min_booking_notice_hours"
                                    class="block text-sm font-medium text-gray-700 mb-1">
                                    Minimum Notice (hours)
                                </label>
                                <InputNumber id="min_booking_notice_hours" v-model="form.min_booking_notice_hours"
                                    :min="1" :max="168" class="w-full"
                                    :class="{ 'p-invalid': form.errors.min_booking_notice_hours }" />
                                <small class="text-xs text-gray-500 mt-1 block">
                                    Minimum hours notice required for bookings (1-168)
                                </small>
                                <small v-if="form.errors.min_booking_notice_hours" class="text-red-500">
                                    {{ form.errors.min_booking_notice_hours }}
                                </small>
                            </div>
                        </ConsoleFormSection>
                    </div>
                </ConsoleFormCard>

                <!-- Payment Fees Card -->
                <ConsoleFormCard title="Transaction Fees" icon="pi pi-percentage">
                    <div class="space-y-5">
                        <p class="text-sm text-gray-500 m-0">
                            Your {{ tierRestrictions.tier_label }} tier has a {{ tierRestrictions.total_fee_rate }}%
                            transaction fee
                            ({{ tierRestrictions.zeen_fee_rate }}% Zeen + {{ tierRestrictions.gateway_fee_rate }}%
                            gateway).
                            Choose who pays these fees.
                        </p>

                        <!-- Fee Payer Selection -->
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">
                                Who pays transaction fees?
                            </label>

                            <div class="flex flex-col gap-3">
                                <label
                                    class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                                    :class="form.fee_payer === 'provider' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                    <RadioButton v-model="form.fee_payer" value="provider" class="mt-0.5" />
                                    <div>
                                        <span class="block text-sm font-medium text-gray-900">I absorb the fees</span>
                                        <span class="block text-xs text-gray-500 mt-0.5">
                                            Fees are deducted from your payout. Clients see only the service price.
                                        </span>
                                    </div>
                                </label>

                                <label
                                    class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                                    :class="form.fee_payer === 'client' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                    <RadioButton v-model="form.fee_payer" value="client" class="mt-0.5" />
                                    <div>
                                        <span class="block text-sm font-medium text-gray-900">Client pays the
                                            fees</span>
                                        <span class="block text-xs text-gray-500 mt-0.5">
                                            Fees are added as a "transaction fee" on top of the service price at
                                            checkout.
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Fee Breakdown Preview -->
                        <FeeBreakdownPreview :fee-payer="form.fee_payer" :zeen-fee-rate="tierRestrictions.zeen_fee_rate"
                            :gateway-fee-rate="tierRestrictions.gateway_fee_rate" :example-amount="10000" />
                    </div>
                </ConsoleFormCard>

                <!-- Form Actions -->
                <div class="flex justify-end">
                    <ConsoleButton label="Save Settings" icon="pi pi-check" type="submit" :loading="form.processing" />
                </div>
            </form>
        </div>
    </ConsoleLayout>
</template>

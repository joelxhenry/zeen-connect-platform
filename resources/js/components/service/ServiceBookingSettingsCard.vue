<script setup lang="ts">
import { computed, watch } from 'vue';
import { ConsoleFormCard, ConsoleFormSection } from '@/components/console';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Message from 'primevue/message';
import type {
    BookingSettings,
    TierRestrictions,
    DepositTypeOption,
    CancellationPolicyOption,
    ValidationResult,
} from '@/types/service';

interface Props {
    providerDefaults: BookingSettings;
    tierRestrictions: TierRestrictions;
    depositTypeOptions: DepositTypeOption[];
    cancellationPolicyOptions: CancellationPolicyOption[];
    minDepositPercentage: number;
    canDisableDeposit: boolean;
    depositHelpText: string;
    depositTypeValidation: ValidationResult;
    depositAmountValidation: ValidationResult;
    errors?: {
        deposit_type?: string;
        deposit_amount?: string;
    };
}

const props = defineProps<Props>();

// v-model bindings
const useProviderDefaults = defineModel<boolean>('useProviderDefaults', { required: true });
const requiresApproval = defineModel<boolean>('requiresApproval', { required: true });
const depositType = defineModel<'none' | 'percentage'>('depositType', { required: true });
const depositAmount = defineModel<number | null>('depositAmount', { required: true });
const cancellationPolicy = defineModel<'flexible' | 'moderate' | 'strict'>('cancellationPolicy', { required: true });
const advanceBookingDays = defineModel<number>('advanceBookingDays', { required: true });
const minBookingNoticeHours = defineModel<number>('minBookingNoticeHours', { required: true });

const showDepositAmount = computed(() => depositType.value === 'percentage');

const getDepositDisplay = () => {
    if (props.providerDefaults.deposit_type === 'none') return 'None';
    return `${props.providerDefaults.deposit_amount}%`;
};

// Watch for use_provider_defaults toggle
watch(useProviderDefaults, (useDefaults) => {
    if (useDefaults) {
        requiresApproval.value = props.providerDefaults.requires_approval;
        depositType.value = props.providerDefaults.deposit_type;
        depositAmount.value = props.providerDefaults.deposit_amount;
        cancellationPolicy.value = props.providerDefaults.cancellation_policy;
        advanceBookingDays.value = props.providerDefaults.advance_booking_days;
        minBookingNoticeHours.value = props.providerDefaults.min_booking_notice_hours;
    }
});

// Watch deposit type changes to reset amount when needed
watch(depositType, (newType) => {
    if (newType === 'percentage') {
        if (depositAmount.value === null || depositAmount.value < props.minDepositPercentage) {
            depositAmount.value = props.minDepositPercentage;
        }
    } else if (newType === 'none') {
        depositAmount.value = null;
    }
});
</script>

<template>
    <ConsoleFormCard title="Booking Settings" icon="pi pi-calendar">
        <div class="space-y-4">
            <!-- Use Provider Defaults Toggle -->
            <ConsoleFormSection highlighted>
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Use default booking settings</label>
                        <p class="text-xs text-gray-500 m-0">Apply your provider-wide defaults to this service</p>
                    </div>
                    <InputSwitch v-model="useProviderDefaults" />
                </div>
            </ConsoleFormSection>

            <!-- Provider Defaults Summary (shown when using defaults) -->
            <div v-if="useProviderDefaults" class="bg-gray-50 rounded-lg p-4">
                <p class="m-0 font-medium text-gray-700 mb-2">Current defaults:</p>
                <div class="text-sm text-gray-500 space-y-1">
                    <p class="m-0">
                        <i class="pi pi-check-circle text-xs mr-2" />
                        Approval: {{ providerDefaults.requires_approval ? 'Required' : 'Not required' }}
                    </p>
                    <p class="m-0">
                        <i class="pi pi-wallet text-xs mr-2" />
                        Deposit: {{ getDepositDisplay() }}
                    </p>
                    <p class="m-0">
                        <i class="pi pi-ban text-xs mr-2" />
                        Cancellation: <span class="capitalize">{{ providerDefaults.cancellation_policy }}</span>
                    </p>
                    <p class="m-0">
                        <i class="pi pi-calendar-plus text-xs mr-2" />
                        Advance booking: {{ providerDefaults.advance_booking_days }} days
                    </p>
                    <p class="m-0">
                        <i class="pi pi-clock text-xs mr-2" />
                        Min notice: {{ providerDefaults.min_booking_notice_hours }} hours
                    </p>
                </div>
            </div>

            <!-- Custom Settings (shown when not using defaults) -->
            <div v-else class="space-y-4 pt-2">
                <!-- Requires Approval -->
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Require approval for bookings</label>
                        <p class="text-xs text-gray-500 m-0">You'll need to manually confirm each booking</p>
                    </div>
                    <InputSwitch v-model="requiresApproval" />
                </div>

                <!-- Deposit Section -->
                <div class="border-t border-gray-100 pt-4">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="pi pi-wallet text-gray-400" />
                        <span class="font-medium text-gray-700">Deposit Settings</span>
                    </div>

                    <!-- Tier restriction notice for deposit -->
                    <Message
                        v-if="!canDisableDeposit && tierRestrictions.tier !== 'enterprise'"
                        severity="info"
                        :closable="false"
                        class="mb-4"
                    >
                        <span class="text-sm">{{ depositHelpText }}</span>
                    </Message>

                    <!-- Deposit Type -->
                    <div class="mb-4">
                        <label for="deposit_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Deposit Type
                        </label>
                        <Select
                            id="deposit_type"
                            v-model="depositType"
                            :options="depositTypeOptions"
                            optionLabel="label"
                            optionValue="value"
                            :optionDisabled="(opt) => opt.disabled"
                            class="w-full"
                            :class="{ 'p-invalid': errors?.deposit_type || !depositTypeValidation.valid }"
                        />
                        <small v-if="errors?.deposit_type" class="text-red-500">{{ errors.deposit_type }}</small>
                        <small v-else-if="!depositTypeValidation.valid" class="text-red-500">{{ depositTypeValidation.message }}</small>
                    </div>

                    <!-- Deposit Percentage (shown when deposit type is percentage) -->
                    <div v-if="showDepositAmount">
                        <label for="deposit_amount" class="block text-sm font-medium text-gray-700 mb-1">
                            Deposit Percentage
                        </label>
                        <InputNumber
                            id="deposit_amount"
                            v-model="depositAmount"
                            mode="decimal"
                            suffix="%"
                            :min="minDepositPercentage"
                            :max="100"
                            class="w-full"
                            :class="{ 'p-invalid': errors?.deposit_amount || !depositAmountValidation.valid }"
                        />
                        <small v-if="errors?.deposit_amount" class="text-red-500">{{ errors.deposit_amount }}</small>
                        <small v-else-if="!depositAmountValidation.valid" class="text-red-500">{{ depositAmountValidation.message }}</small>
                        <small v-else class="text-gray-500">
                            Minimum {{ minDepositPercentage }}% required to cover platform fees
                        </small>
                    </div>
                </div>

                <!-- Cancellation Policy -->
                <div class="border-t border-gray-100 pt-4">
                    <label for="cancellation_policy" class="block text-sm font-medium text-gray-700 mb-1">
                        Cancellation Policy
                    </label>
                    <Select
                        id="cancellation_policy"
                        v-model="cancellationPolicy"
                        :options="cancellationPolicyOptions"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    >
                        <template #option="{ option }">
                            <div>
                                <p class="font-medium m-0">{{ option.label }}</p>
                                <p class="text-xs text-gray-500 m-0">{{ option.description }}</p>
                            </div>
                        </template>
                    </Select>
                </div>

                <!-- Advance Booking & Notice Row -->
                <ConsoleFormSection :columns="2" class="border-t border-gray-100 pt-4">
                    <div>
                        <label for="advance_booking_days" class="block text-sm font-medium text-gray-700 mb-1">
                            Advance Booking (days)
                        </label>
                        <InputNumber
                            id="advance_booking_days"
                            v-model="advanceBookingDays"
                            :min="1"
                            :max="365"
                            class="w-full"
                        />
                        <small class="text-xs text-gray-500">How far in advance clients can book</small>
                    </div>
                    <div>
                        <label for="min_booking_notice_hours" class="block text-sm font-medium text-gray-700 mb-1">
                            Min Notice (hours)
                        </label>
                        <InputNumber
                            id="min_booking_notice_hours"
                            v-model="minBookingNoticeHours"
                            :min="1"
                            :max="168"
                            class="w-full"
                        />
                        <small class="text-xs text-gray-500">Minimum notice required for booking</small>
                    </div>
                </ConsoleFormSection>
            </div>
        </div>
    </ConsoleFormCard>
</template>

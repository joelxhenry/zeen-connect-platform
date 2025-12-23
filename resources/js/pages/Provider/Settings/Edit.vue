<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import SettingsController from '@/actions/App/Domains/Provider/Controllers/SettingsController';

interface BookingSettings {
    requires_approval: boolean;
    deposit_type: 'none' | 'fixed' | 'percentage';
    deposit_amount: number | null;
    cancellation_policy: 'flexible' | 'moderate' | 'strict';
    advance_booking_days: number;
    min_booking_notice_hours: number;
}

interface Props {
    bookingSettings: BookingSettings;
}

const props = defineProps<Props>();
const toast = useToast();

const form = useForm({
    requires_approval: props.bookingSettings.requires_approval,
    deposit_type: props.bookingSettings.deposit_type,
    deposit_amount: props.bookingSettings.deposit_amount,
    cancellation_policy: props.bookingSettings.cancellation_policy,
    advance_booking_days: props.bookingSettings.advance_booking_days,
    min_booking_notice_hours: props.bookingSettings.min_booking_notice_hours,
});

const depositTypeOptions = [
    { label: 'No deposit required', value: 'none' },
    { label: 'Fixed amount', value: 'fixed' },
    { label: 'Percentage of total', value: 'percentage' },
];

const cancellationPolicyOptions = [
    { label: 'Flexible - Full refund 24h before', value: 'flexible' },
    { label: 'Moderate - Full refund 48h before', value: 'moderate' },
    { label: 'Strict - 50% refund up to 1 week before', value: 'strict' },
];

const showDepositAmount = computed(() =>
    form.deposit_type === 'fixed' || form.deposit_type === 'percentage'
);

const submit = () => {
    form.put(SettingsController.updateBookingSettings().url, {
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
            <div class="mb-6">
                <h1 class="text-xl lg:text-2xl font-semibold text-[#0D1F1B] m-0 mb-1">
                    Settings
                </h1>
                <p class="text-gray-500 m-0 text-sm lg:text-base">
                    Configure your default booking settings. These will apply to all services unless overridden.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Booking Settings Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                        <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                            <i class="pi pi-cog text-[#106B4F]"></i>
                            Default Booking Settings
                        </h2>
                    </div>
                    <div class="p-4 lg:p-5 space-y-5">
                        <p class="text-sm text-gray-500 m-0">
                            These settings will be used as defaults for all your services. You can override them on
                            individual services if needed.
                        </p>

                        <!-- Requires Approval -->
                        <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                            <InputSwitch v-model="form.requires_approval" class="mt-0.5" />
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Require approval for
                                    bookings</label>
                                <p class="text-xs text-gray-500 m-0 mt-1">
                                    When enabled, you'll need to manually confirm each booking request before it's
                                    confirmed.
                                    Clients will receive a pending status until you approve.
                                </p>
                            </div>
                        </div>

                        <!-- Deposit Settings -->
                        <div class="space-y-4">
                            <div>
                                <label for="deposit_type" class="block text-sm font-medium text-gray-700 mb-1">
                                    Deposit Requirement
                                </label>
                                <Select id="deposit_type" v-model="form.deposit_type" :options="depositTypeOptions"
                                    optionLabel="label" optionValue="value" class="w-full" />
                                <small class="text-xs text-gray-500 mt-1 block">
                                    Require clients to pay a deposit when booking
                                </small>
                            </div>

                            <!-- Deposit Amount (conditional) -->
                            <div v-if="showDepositAmount">
                                <label for="deposit_amount" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ form.deposit_type === 'percentage' ? 'Deposit Percentage' : `Deposit Amount
                                        (JMD)`
                                    }}
                                </label>
                                <InputNumber id="deposit_amount" v-model="form.deposit_amount"
                                    :mode="form.deposit_type === 'percentage' ? 'decimal' : 'currency'"
                                    :currency="form.deposit_type === 'fixed' ? 'JMD' : undefined"
                                    :suffix="form.deposit_type === 'percentage' ? '%' : undefined" :min="0"
                                    :max="form.deposit_type === 'percentage' ? 100 : undefined" class="w-full"
                                    :class="{ 'p-invalid': form.errors.deposit_amount }" />
                                <small v-if="form.errors.deposit_amount" class="text-red-500">
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end">
                    <Button label="Save Settings" icon="pi pi-check" type="submit" :loading="form.processing"
                        class="!bg-[#106B4F] !border-[#106B4F]" />
                </div>
            </form>
        </div>
    </ConsoleLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import ServiceController from '@/actions/App/Domains/Provider/Controllers/ServiceController';

interface Category {
    id: number;
    name: string;
}

interface BookingSettings {
    requires_approval: boolean;
    deposit_type: 'none' | 'fixed' | 'percentage';
    deposit_amount: number | null;
    cancellation_policy: 'flexible' | 'moderate' | 'strict';
    advance_booking_days: number;
    min_booking_notice_hours: number;
}

interface Props {
    categories: Category[];
    providerDefaults: BookingSettings;
}

const props = defineProps<Props>();
const toast = useToast();

const form = useForm({
    name: '',
    category_id: null as number | null,
    description: '',
    duration_minutes: 60,
    price: null as number | null,
    is_active: true,
    use_provider_defaults: true,
    requires_approval: props.providerDefaults.requires_approval,
    deposit_type: props.providerDefaults.deposit_type,
    deposit_amount: props.providerDefaults.deposit_amount,
    cancellation_policy: props.providerDefaults.cancellation_policy,
    advance_booking_days: props.providerDefaults.advance_booking_days,
    min_booking_notice_hours: props.providerDefaults.min_booking_notice_hours,
});

const durationOptions = [
    { label: '15 minutes', value: 15 },
    { label: '30 minutes', value: 30 },
    { label: '45 minutes', value: 45 },
    { label: '1 hour', value: 60 },
    { label: '1 hour 30 minutes', value: 90 },
    { label: '2 hours', value: 120 },
    { label: '2 hours 30 minutes', value: 150 },
    { label: '3 hours', value: 180 },
    { label: '4 hours', value: 240 },
    { label: '5 hours', value: 300 },
    { label: '6 hours', value: 360 },
    { label: '8 hours', value: 480 },
];

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

watch(() => form.use_provider_defaults, (useDefaults) => {
    if (useDefaults) {
        form.requires_approval = props.providerDefaults.requires_approval;
        form.deposit_type = props.providerDefaults.deposit_type;
        form.deposit_amount = props.providerDefaults.deposit_amount;
        form.cancellation_policy = props.providerDefaults.cancellation_policy;
        form.advance_booking_days = props.providerDefaults.advance_booking_days;
        form.min_booking_notice_hours = props.providerDefaults.min_booking_notice_hours;
    }
});

const getDepositDisplay = (settings: BookingSettings) => {
    if (settings.deposit_type === 'none') return 'None';
    if (settings.deposit_type === 'fixed') return `$${settings.deposit_amount?.toFixed(2)}`;
    return `${settings.deposit_amount}%`;
};

const submit = () => {
    form.post(ServiceController.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Service created successfully',
                life: 3000,
            });
        },
    });
};
</script>

<template>
    <ConsoleLayout title="Add Service">
        <div class="w-full max-w-3xl mx-auto">
            <!-- Page Header -->
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-2">
                    <Link href="/console/services" class="text-gray-500 hover:text-[#0D1F1B]">
                        <i class="pi pi-arrow-left"></i>
                    </Link>
                    <h1 class="text-xl lg:text-2xl font-semibold text-[#0D1F1B] m-0">Add New Service</h1>
                </div>
                <p class="text-gray-500 m-0 text-sm lg:text-base ml-6">
                    Create a new service that clients can book
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                        <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0">Basic Information</h2>
                    </div>
                    <div class="p-4 lg:p-5 space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Service Name
                                *</label>
                            <InputText id="name" v-model="form.name" class="w-full"
                                :class="{ 'p-invalid': form.errors.name }"
                                placeholder="e.g., Haircut, Makeup, Massage" />
                            <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category
                                *</label>
                            <Select id="category" v-model="form.category_id" :options="categories" optionLabel="name"
                                optionValue="id" placeholder="Select a category" class="w-full"
                                :class="{ 'p-invalid': form.errors.category_id }" />
                            <small v-if="form.errors.category_id" class="text-red-500">{{ form.errors.category_id
                                }}</small>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <Textarea id="description" v-model="form.description" rows="3" class="w-full"
                                placeholder="Describe what this service includes..." />
                            <small v-if="form.errors.description" class="text-red-500">{{ form.errors.description
                                }}</small>
                        </div>

                        <!-- Duration & Price Row -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">Duration
                                    *</label>
                                <Select id="duration" v-model="form.duration_minutes" :options="durationOptions"
                                    optionLabel="label" optionValue="value" placeholder="Select duration" class="w-full"
                                    :class="{ 'p-invalid': form.errors.duration_minutes }" />
                                <small v-if="form.errors.duration_minutes" class="text-red-500">{{
                                    form.errors.duration_minutes }}</small>
                            </div>
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (JMD)
                                    *</label>
                                <InputNumber id="price" v-model="form.price" mode="currency" currency="JMD"
                                    locale="en-JM" class="w-full" :class="{ 'p-invalid': form.errors.price }"
                                    placeholder="0.00" />
                                <small v-if="form.errors.price" class="text-red-500">{{ form.errors.price }}</small>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3">
                            <InputSwitch v-model="form.is_active" />
                            <label class="text-sm font-medium text-gray-700">Active (visible to clients)</label>
                        </div>
                    </div>
                </div>

                <!-- Booking Settings Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                        <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0">Booking Settings</h2>
                    </div>
                    <div class="p-4 lg:p-5 space-y-4">
                        <!-- Use Provider Defaults Toggle -->
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Use default booking settings</label>
                                <p class="text-xs text-gray-500 m-0">Apply your provider-wide defaults to this service
                                </p>
                            </div>
                            <InputSwitch v-model="form.use_provider_defaults" />
                        </div>

                        <!-- Custom Settings (shown when not using defaults) -->
                        <div v-if="!form.use_provider_defaults" class="space-y-4 pt-2">
                            <!-- Requires Approval -->
                            <div class="flex items-center gap-3">
                                <InputSwitch v-model="form.requires_approval" />
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Require approval for
                                        bookings</label>
                                    <p class="text-xs text-gray-500 m-0">You'll need to manually confirm each booking
                                    </p>
                                </div>
                            </div>

                            <!-- Deposit Type -->
                            <div>
                                <label for="deposit_type" class="block text-sm font-medium text-gray-700 mb-1">Deposit
                                    Requirement</label>
                                <Select id="deposit_type" v-model="form.deposit_type" :options="depositTypeOptions"
                                    optionLabel="label" optionValue="value" class="w-full" />
                            </div>

                            <!-- Deposit Amount (conditional) -->
                            <div v-if="showDepositAmount">
                                <label for="deposit_amount" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ form.deposit_type === 'percentage' ? 'Deposit Percentage' : 'Deposit Amount (JMD)'
                                    }}
                                </label>
                                <InputNumber id="deposit_amount" v-model="form.deposit_amount"
                                    :mode="form.deposit_type === 'percentage' ? 'decimal' : 'currency'"
                                    :currency="form.deposit_type === 'fixed' ? 'JMD' : undefined"
                                    :suffix="form.deposit_type === 'percentage' ? '%' : undefined" :min="0"
                                    :max="form.deposit_type === 'percentage' ? 100 : undefined" class="w-full"
                                    :class="{ 'p-invalid': form.errors.deposit_amount }" />
                                <small v-if="form.errors.deposit_amount" class="text-red-500">{{
                                    form.errors.deposit_amount }}</small>
                            </div>

                            <!-- Cancellation Policy -->
                            <div>
                                <label for="cancellation_policy"
                                    class="block text-sm font-medium text-gray-700 mb-1">Cancellation Policy</label>
                                <Select id="cancellation_policy" v-model="form.cancellation_policy"
                                    :options="cancellationPolicyOptions" optionLabel="label" optionValue="value"
                                    class="w-full" />
                            </div>

                            <!-- Advance Booking & Notice Row -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="advance_booking_days"
                                        class="block text-sm font-medium text-gray-700 mb-1">Advance Booking
                                        (days)</label>
                                    <InputNumber id="advance_booking_days" v-model="form.advance_booking_days" :min="1"
                                        :max="365" class="w-full" />
                                    <small class="text-xs text-gray-500">How far in advance clients can book</small>
                                </div>
                                <div>
                                    <label for="min_booking_notice_hours"
                                        class="block text-sm font-medium text-gray-700 mb-1">Min Notice (hours)</label>
                                    <InputNumber id="min_booking_notice_hours" v-model="form.min_booking_notice_hours"
                                        :min="1" :max="168" class="w-full" />
                                    <small class="text-xs text-gray-500">Minimum notice required for booking</small>
                                </div>
                            </div>
                        </div>

                        <!-- Provider Defaults Summary (shown when using defaults) -->
                        <div v-else class="text-sm text-gray-500 space-y-1 p-3 bg-gray-50 rounded-lg">
                            <p class="m-0 font-medium text-gray-700">Current defaults:</p>
                            <p class="m-0">Approval: {{ providerDefaults.requires_approval ? 'Required' : 'Not required'
                                }}</p>
                            <p class="m-0">Deposit: {{ getDepositDisplay(providerDefaults) }}</p>
                            <p class="m-0 capitalize">Cancellation: {{ providerDefaults.cancellation_policy }}</p>
                            <p class="m-0">Advance booking: {{ providerDefaults.advance_booking_days }} days</p>
                            <p class="m-0">Min notice: {{ providerDefaults.min_booking_notice_hours }} hours</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3">
                    <Link href="/console/services">
                        <Button label="Cancel" severity="secondary" type="button" />
                    </Link>
                    <Button label="Create Service" icon="pi pi-check" type="submit" :loading="form.processing"
                        class="!bg-[#106B4F] !border-[#106B4F]" />
                </div>
            </form>
        </div>
    </ConsoleLayout>
</template>

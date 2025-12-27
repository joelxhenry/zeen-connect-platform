<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleFormSection,
    ConsoleButton,
} from '@/components/console';
import TierRestrictionBanner from '@/components/service/TierRestrictionBanner.vue';
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import provider from '@/routes/provider';
import { useServiceForm } from '@/composables/useServiceForm';
import type { ServicesEditProps } from '@/types/service';
import type { MediaItem } from '@/types/models';

const props = defineProps<ServicesEditProps>();
const confirm = useConfirm();
const toast = useToast();

// Use the service form composable for tier-based options and validation
const {
    depositTypeOptions,
    durationOptions,
    cancellationPolicyOptions,
    minDepositPercentage,
    minServicePrice,
    canCustomizeDeposit,
    canDisableDeposit,
    validatePrice,
    validateDepositType,
    validateDepositAmount,
    getDefaultDepositType,
    getDefaultDepositAmount,
    depositHelpText,
    priceHelpText,
} = useServiceForm(props.tierRestrictions);

// Cover image state (managed separately from form)
const cover = ref<MediaItem | null>(props.service.cover || null);

// URL for cover image upload (use UUID for route model binding)
const uploadUrl = computed(() => `/media/services/${props.service.uuid}`);

const handleCoverUploaded = (media: MediaItem) => {
    toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Cover image uploaded',
        life: 3000,
    });
};

const handleCoverError = (error: string) => {
    toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error,
        life: 5000,
    });
};

const form = useForm({
    name: props.service.name,
    category_id: props.service.category_id,
    description: props.service.description || '',
    duration_minutes: props.service.duration_minutes,
    price: props.service.price,
    is_active: props.service.is_active,
    use_provider_defaults: props.service.use_provider_defaults,
    requires_approval: props.service.requires_approval ?? props.providerDefaults.requires_approval,
    deposit_type: props.service.deposit_type ?? props.providerDefaults.deposit_type,
    deposit_amount: props.service.deposit_amount ?? props.providerDefaults.deposit_amount,
    cancellation_policy: props.service.cancellation_policy ?? props.providerDefaults.cancellation_policy,
    advance_booking_days: props.service.advance_booking_days ?? props.providerDefaults.advance_booking_days,
    min_booking_notice_hours: props.service.min_booking_notice_hours ?? props.providerDefaults.min_booking_notice_hours,
});

// Real-time validation for price
const priceValidation = computed(() => {
    if (form.price === null || form.price === 0) {
        return { valid: true, message: null };
    }
    return validatePrice(form.price);
});

// Real-time validation for deposit type
const depositTypeValidation = computed(() => {
    if (form.use_provider_defaults) {
        return { valid: true, message: null };
    }
    return validateDepositType(form.deposit_type);
});

// Real-time validation for deposit amount
const depositAmountValidation = computed(() => {
    if (form.use_provider_defaults || form.deposit_type === 'none') {
        return { valid: true, message: null };
    }
    return validateDepositAmount(form.deposit_type, form.deposit_amount);
});

// Show deposit amount input
const showDepositAmount = computed(() =>
    form.deposit_type === 'fixed' || form.deposit_type === 'percentage'
);

// Watch for use_provider_defaults toggle
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

// Watch deposit type changes to reset amount when needed
watch(() => form.deposit_type, (newType) => {
    if (newType === 'percentage') {
        // Ensure deposit amount meets minimum
        if (form.deposit_amount === null || form.deposit_amount < minDepositPercentage.value) {
            form.deposit_amount = minDepositPercentage.value;
        }
    } else if (newType === 'none') {
        form.deposit_amount = null;
    }
});

const getDepositDisplay = () => {
    if (props.providerDefaults.deposit_type === 'none') return 'None';
    if (props.providerDefaults.deposit_type === 'fixed') {
        return `$${props.providerDefaults.deposit_amount?.toFixed(2)}`;
    }
    return `${props.providerDefaults.deposit_amount}%`;
};

const submit = () => {
    form.put(provider.services.update.url(props.service.uuid), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Service updated successfully',
                life: 3000,
            });
        },
    });
};

const deleteService = () => {
    confirm.require({
        message: `Are you sure you want to delete "${props.service.name}"? This action cannot be undone.`,
        header: 'Delete Service',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-500 !border-red-500',
        accept: () => {
            router.delete(provider.services.destroy.url(props.service.uuid), {
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Deleted',
                        detail: 'Service deleted successfully',
                        life: 3000,
                    });
                },
            });
        },
    });
};
</script>

<template>
    <ConsoleLayout title="Edit Service">
        <ConfirmDialog />

        <div class="w-full max-w-3xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Edit Service"
                subtitle="Update your service details and booking settings"
                :back-href="provider.services.index.url()"
            >
                <template #title-badge>
                    <Tag
                        :value="tierRestrictions.tier_label"
                        :severity="tierRestrictions.tier === 'enterprise' ? 'success' : tierRestrictions.tier === 'premium' ? 'info' : 'secondary'"
                        class="ml-2"
                    />
                </template>
            </ConsolePageHeader>

            <!-- Tier Restriction Banner -->
            <TierRestrictionBanner
                :restrictions="tierRestrictions"
                class="mb-6"
            />

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information Card -->
                <ConsoleFormCard title="Basic Information" icon="pi pi-info-circle">
                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Service Name *
                            </label>
                            <InputText
                                id="name"
                                v-model="form.name"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.name }"
                                placeholder="e.g., Haircut, Makeup, Massage"
                            />
                            <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                                Category *
                            </label>
                            <Select
                                id="category"
                                v-model="form.category_id"
                                :options="categories"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select a category"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.category_id }"
                            />
                            <small v-if="form.errors.category_id" class="text-red-500">{{ form.errors.category_id }}</small>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full"
                                placeholder="Describe what this service includes..."
                            />
                            <small v-if="form.errors.description" class="text-red-500">{{ form.errors.description }}</small>
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Cover Image Card -->
                <ConsoleFormCard title="Cover Image" icon="pi pi-image">
                    <SingleImageUpload
                        v-model="cover"
                        :upload-url="uploadUrl"
                        collection="cover"
                        shape="cover"
                        placeholder="Upload Cover Image"
                        @uploaded="handleCoverUploaded"
                        @error="handleCoverError"
                    />
                    <small class="text-gray-500 mt-2 block">
                        This image will be displayed in service listings and your booking page.
                    </small>
                </ConsoleFormCard>

                <!-- Pricing & Duration Card -->
                <ConsoleFormCard title="Pricing & Duration" icon="pi pi-dollar">
                    <div class="space-y-4">
                        <ConsoleFormSection :columns="2">
                            <!-- Duration -->
                            <div>
                                <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">
                                    Duration *
                                </label>
                                <Select
                                    id="duration"
                                    v-model="form.duration_minutes"
                                    :options="durationOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select duration"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.duration_minutes }"
                                />
                                <small v-if="form.errors.duration_minutes" class="text-red-500">{{ form.errors.duration_minutes }}</small>
                            </div>

                            <!-- Price -->
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                                    Price (JMD) *
                                </label>
                                <InputNumber
                                    id="price"
                                    v-model="form.price"
                                    mode="currency"
                                    currency="JMD"
                                    locale="en-JM"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.price || !priceValidation.valid }"
                                    placeholder="0.00"
                                />
                                <small v-if="form.errors.price" class="text-red-500">{{ form.errors.price }}</small>
                                <small v-else-if="!priceValidation.valid" class="text-red-500">{{ priceValidation.message }}</small>
                                <small v-else-if="priceHelpText" class="text-gray-500">{{ priceHelpText }}</small>
                            </div>
                        </ConsoleFormSection>
                    </div>
                </ConsoleFormCard>

                <!-- Visibility Card -->
                <ConsoleFormCard title="Visibility" icon="pi pi-eye">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Active</label>
                            <p class="text-xs text-gray-500 m-0">Make this service visible to clients</p>
                        </div>
                        <InputSwitch v-model="form.is_active" />
                    </div>
                </ConsoleFormCard>

                <!-- Booking Settings Card -->
                <ConsoleFormCard title="Booking Settings" icon="pi pi-calendar">
                    <div class="space-y-4">
                        <!-- Use Provider Defaults Toggle -->
                        <ConsoleFormSection highlighted>
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Use default booking settings</label>
                                    <p class="text-xs text-gray-500 m-0">Apply your provider-wide defaults to this service</p>
                                </div>
                                <InputSwitch v-model="form.use_provider_defaults" />
                            </div>
                        </ConsoleFormSection>

                        <!-- Provider Defaults Summary (shown when using defaults) -->
                        <div v-if="form.use_provider_defaults" class="bg-gray-50 rounded-lg p-4">
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
                                <InputSwitch v-model="form.requires_approval" />
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
                                        v-model="form.deposit_type"
                                        :options="depositTypeOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        :optionDisabled="(opt) => opt.disabled"
                                        class="w-full"
                                        :class="{ 'p-invalid': form.errors.deposit_type || !depositTypeValidation.valid }"
                                    />
                                    <small v-if="form.errors.deposit_type" class="text-red-500">{{ form.errors.deposit_type }}</small>
                                    <small v-else-if="!depositTypeValidation.valid" class="text-red-500">{{ depositTypeValidation.message }}</small>
                                </div>

                                <!-- Deposit Amount (conditional) -->
                                <div v-if="showDepositAmount">
                                    <label for="deposit_amount" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ form.deposit_type === 'percentage' ? 'Deposit Percentage' : 'Deposit Amount (JMD)' }}
                                    </label>
                                    <InputNumber
                                        id="deposit_amount"
                                        v-model="form.deposit_amount"
                                        :mode="form.deposit_type === 'percentage' ? 'decimal' : 'currency'"
                                        :currency="form.deposit_type === 'fixed' ? 'JMD' : undefined"
                                        :suffix="form.deposit_type === 'percentage' ? '%' : undefined"
                                        :min="form.deposit_type === 'percentage' ? minDepositPercentage : 0"
                                        :max="form.deposit_type === 'percentage' ? 100 : undefined"
                                        class="w-full"
                                        :class="{ 'p-invalid': form.errors.deposit_amount || !depositAmountValidation.valid }"
                                    />
                                    <small v-if="form.errors.deposit_amount" class="text-red-500">{{ form.errors.deposit_amount }}</small>
                                    <small v-else-if="!depositAmountValidation.valid" class="text-red-500">{{ depositAmountValidation.message }}</small>
                                    <small v-else-if="form.deposit_type === 'percentage'" class="text-gray-500">
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
                                    v-model="form.cancellation_policy"
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
                                        v-model="form.advance_booking_days"
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
                                        v-model="form.min_booking_notice_hours"
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

                <!-- Danger Zone Card -->
                <ConsoleFormCard title="Danger Zone" icon="pi pi-exclamation-triangle" variant="danger">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 m-0">Delete this service</p>
                            <p class="text-sm text-gray-500 m-0">Once deleted, this service cannot be recovered.</p>
                        </div>
                        <ConsoleButton
                            label="Delete"
                            icon="pi pi-trash"
                            variant="danger"
                            outlined
                            @click="deleteService"
                        />
                    </div>
                </ConsoleFormCard>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3">
                    <ConsoleButton
                        label="Cancel"
                        variant="secondary"
                        :href="provider.services.index.url()"
                    />
                    <ConsoleButton
                        label="Save Changes"
                        icon="pi pi-check"
                        type="submit"
                        :loading="form.processing"
                        :disabled="!priceValidation.valid || !depositTypeValidation.valid || !depositAmountValidation.valid"
                    />
                </div>
            </form>
        </div>
    </ConsoleLayout>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleButton,
} from '@/components/console';
import TierRestrictionBanner from '@/components/service/TierRestrictionBanner.vue';
import ServiceBasicInfoCard from '@/components/service/ServiceBasicInfoCard.vue';
import ServicePricingCard from '@/components/service/ServicePricingCard.vue';
import ServiceBookingSettingsCard from '@/components/service/ServiceBookingSettingsCard.vue';
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import provider from '@/routes/provider';
import { useServiceForm } from '@/composables/useServiceForm';
import type { ServicesCreateProps } from '@/types/service';

const props = defineProps<ServicesCreateProps>();
const toast = useToast();

// Use the service form composable for tier-based options and validation
const {
    depositTypeOptions,
    durationOptions,
    cancellationPolicyOptions,
    minDepositPercentage,
    canDisableDeposit,
    validatePrice,
    validateDepositType,
    validateDepositAmount,
    getDefaultDepositType,
    getDefaultDepositAmount,
    depositHelpText,
    priceHelpText,
} = useServiceForm(props.tierRestrictions);

const form = useForm({
    name: '',
    category_id: null as number | null,
    description: '',
    duration_minutes: 60,
    price: null as number | null,
    is_active: true,
    use_provider_defaults: true,
    requires_approval: props.providerDefaults.requires_approval,
    deposit_type: getDefaultDepositType(),
    deposit_amount: getDefaultDepositAmount(),
    cancellation_policy: props.providerDefaults.cancellation_policy,
    advance_booking_days: props.providerDefaults.advance_booking_days,
    min_booking_notice_hours: props.providerDefaults.min_booking_notice_hours,
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

// Watch for use_provider_defaults toggle
watch(() => form.use_provider_defaults, (useDefaults) => {
    if (useDefaults) {
        form.requires_approval = props.providerDefaults.requires_approval;
        form.deposit_type = props.providerDefaults.deposit_type;
        form.deposit_amount = props.providerDefaults.deposit_amount;
        form.cancellation_policy = props.providerDefaults.cancellation_policy;
        form.advance_booking_days = props.providerDefaults.advance_booking_days;
        form.min_booking_notice_hours = props.providerDefaults.min_booking_notice_hours;
    } else {
        // When switching to custom, set tier-appropriate defaults
        form.deposit_type = getDefaultDepositType();
        form.deposit_amount = getDefaultDepositAmount();
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

const submit = () => {
    form.post(provider.services.store.url(), {
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
            <ConsolePageHeader
                title="Add New Service"
                subtitle="Create a new service that clients can book"
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
                <ServiceBasicInfoCard
                    v-model:name="form.name"
                    v-model:category-id="form.category_id"
                    v-model:description="form.description"
                    :categories="categories"
                    :errors="form.errors"
                />

                <!-- Pricing & Duration Card -->
                <ServicePricingCard
                    v-model:duration-minutes="form.duration_minutes"
                    v-model:price="form.price"
                    :duration-options="durationOptions"
                    :price-validation="priceValidation"
                    :price-help-text="priceHelpText"
                    :errors="form.errors"
                />

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
                <ServiceBookingSettingsCard
                    v-model:use-provider-defaults="form.use_provider_defaults"
                    v-model:requires-approval="form.requires_approval"
                    v-model:deposit-type="form.deposit_type"
                    v-model:deposit-amount="form.deposit_amount"
                    v-model:cancellation-policy="form.cancellation_policy"
                    v-model:advance-booking-days="form.advance_booking_days"
                    v-model:min-booking-notice-hours="form.min_booking_notice_hours"
                    :provider-defaults="providerDefaults"
                    :tier-restrictions="tierRestrictions"
                    :deposit-type-options="depositTypeOptions"
                    :cancellation-policy-options="cancellationPolicyOptions"
                    :min-deposit-percentage="minDepositPercentage"
                    :can-disable-deposit="canDisableDeposit"
                    :deposit-help-text="depositHelpText"
                    :deposit-type-validation="depositTypeValidation"
                    :deposit-amount-validation="depositAmountValidation"
                    :errors="form.errors"
                />

                <!-- Form Actions -->
                <div class="flex justify-end gap-3">
                    <ConsoleButton
                        label="Cancel"
                        variant="secondary"
                        :href="provider.services.index.url()"
                    />
                    <ConsoleButton
                        label="Create Service"
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

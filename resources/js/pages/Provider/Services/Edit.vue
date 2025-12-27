<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
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
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import provider from '@/routes/provider';
import { useServiceForm } from '@/composables/useServiceForm';
import type { ServicesEditProps } from '@/types/service';
import type { MediaItem } from '@/types/models';
import media from '@/routes/provider/media';
import { resolveUrl } from '@/utils/url';

const props = defineProps<ServicesEditProps>();
const confirm = useConfirm();
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
    depositHelpText,
    priceHelpText,
} = useServiceForm(props.tierRestrictions);

// Cover image state (managed separately from form)
const cover = ref<MediaItem | null>(props.service.cover || null);

// URL for cover image upload (use UUID for route model binding)
const uploadUrl = computed(() => media.service.upload({ service: props.service.uuid }).url);

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

const submit = () => {
    form.put(resolveUrl(provider.services.update.url(props.service.uuid)), {
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
            router.delete(resolveUrl(provider.services.destroy.url(props.service.uuid)), {
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
            <TierRestrictionBanner :restrictions="tierRestrictions" class="mb-6" />

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information Card -->
                <ServiceBasicInfoCard
                    v-model:name="form.name"
                    v-model:category-id="form.category_id"
                    v-model:description="form.description"
                    :categories="categories"
                    :errors="form.errors"
                />

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

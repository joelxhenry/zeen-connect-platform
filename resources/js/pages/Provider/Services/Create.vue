<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsoleFormCard,
    ConsoleButton,
} from '@/components/console';
import TierRestrictionBanner from '@/components/service/TierRestrictionBanner.vue';
import ServiceBasicInfoCard from '@/components/service/ServiceBasicInfoCard.vue';
import ServicePricingCard from '@/components/service/ServicePricingCard.vue';
import ServiceBookingSettingsCard from '@/components/service/ServiceBookingSettingsCard.vue';
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
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

// Check if form is valid
const isFormValid = computed(() => {
    return priceValidation.value.valid &&
           depositTypeValidation.value.valid &&
           depositAmountValidation.value.valid &&
           form.name.trim() !== '';
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

const formatPrice = (price: number | null) => {
    if (price === null) return '$0';
    return new Intl.NumberFormat('en-JM', {
        style: 'currency',
        currency: 'JMD',
        minimumFractionDigits: 0,
    }).format(price);
};

const formatDuration = (minutes: number) => {
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
};
</script>

<template>
    <ConsoleLayout title="Add Service">
        <div class="service-create-page">
            <!-- Sticky Header -->
            <div class="sticky-header">
                <div class="header-content">
                    <div class="header-left">
                        <Button
                            icon="pi pi-arrow-left"
                            text
                            rounded
                            severity="secondary"
                            @click="router.visit(provider.services.index.url())"
                            class="back-btn"
                        />
                        <div class="header-info">
                            <h1 class="header-title">Add New Service</h1>
                            <p class="header-subtitle">Create a service clients can book</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <ConsoleButton
                            label="Cancel"
                            variant="ghost"
                            :href="provider.services.index.url()"
                            class="cancel-btn"
                        />
                        <ConsoleButton
                            label="Create Service"
                            icon="pi pi-check"
                            @click="submit"
                            :loading="form.processing"
                            :disabled="!isFormValid"
                        />
                    </div>
                </div>
            </div>

            <!-- Tier Restriction Banner -->
            <TierRestrictionBanner :restrictions="tierRestrictions" class="tier-banner" />

            <!-- Main Content -->
            <form @submit.prevent="submit" class="main-content">
                <div class="content-grid">
                    <!-- Left Column - Main Form -->
                    <div class="form-column">
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
                    </div>

                    <!-- Right Column - Sidebar -->
                    <div class="sidebar-column">
                        <!-- Quick Preview Card -->
                        <div class="preview-card">
                            <h3 class="preview-title">Quick Preview</h3>
                            <div class="preview-content">
                                <div class="preview-image preview-image--empty">
                                    <i class="pi pi-image"></i>
                                    <span>Add cover after creating</span>
                                </div>
                                <h4 class="preview-name">{{ form.name || 'Service Name' }}</h4>
                                <p class="preview-category">
                                    {{ categories.find(c => c.id === form.category_id)?.name || 'No category' }}
                                </p>
                                <div class="preview-stats">
                                    <div class="preview-stat">
                                        <span class="stat-value">{{ formatPrice(form.price) }}</span>
                                        <span class="stat-label">Price</span>
                                    </div>
                                    <div class="preview-stat">
                                        <span class="stat-value">{{ formatDuration(form.duration_minutes) }}</span>
                                        <span class="stat-label">Duration</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visibility Card -->
                        <div class="sidebar-card">
                            <div class="sidebar-card-header">
                                <i class="pi pi-eye"></i>
                                <span>Visibility</span>
                            </div>
                            <div class="sidebar-card-content">
                                <div class="visibility-toggle">
                                    <div class="visibility-info">
                                        <span class="visibility-label">{{ form.is_active ? 'Active' : 'Inactive' }}</span>
                                        <span class="visibility-desc">
                                            {{ form.is_active ? 'Visible to clients immediately' : 'Hidden from clients' }}
                                        </span>
                                    </div>
                                    <InputSwitch v-model="form.is_active" />
                                </div>
                            </div>
                        </div>

                        <!-- Tier Info Card -->
                        <div class="sidebar-card">
                            <div class="sidebar-card-header">
                                <i class="pi pi-star"></i>
                                <span>Your Tier</span>
                            </div>
                            <div class="sidebar-card-content">
                                <Tag
                                    :value="tierRestrictions.tier_label"
                                    :severity="tierRestrictions.tier === 'enterprise' ? 'success' : tierRestrictions.tier === 'premium' ? 'info' : 'secondary'"
                                    class="tier-tag"
                                />
                                <p class="tier-desc">
                                    {{ tierRestrictions.tier === 'enterprise'
                                        ? 'Full flexibility on pricing and deposits'
                                        : tierRestrictions.tier === 'premium'
                                            ? 'Custom deposits with minimum requirements'
                                            : 'Standard deposit requirements apply' }}
                                </p>
                            </div>
                        </div>

                        <!-- Tips Card -->
                        <div class="sidebar-card sidebar-card--tips">
                            <div class="sidebar-card-header">
                                <i class="pi pi-lightbulb"></i>
                                <span>Tips</span>
                            </div>
                            <div class="sidebar-card-content">
                                <ul class="tips-list">
                                    <li>Use a descriptive name that clients can easily understand</li>
                                    <li>Add a cover image after creating to attract more bookings</li>
                                    <li>Set competitive pricing based on your market</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Mobile Save Bar -->
            <div class="mobile-save-bar">
                <ConsoleButton
                    label="Create Service"
                    icon="pi pi-check"
                    @click="submit"
                    :loading="form.processing"
                    :disabled="!isFormValid"
                    class="mobile-save-btn"
                />
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.service-create-page {
    max-width: 1200px;
    margin: 0 auto;
    padding-bottom: 80px;
}

/* Sticky Header */
.sticky-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    margin: -1.5rem -1.5rem 1.5rem;
    padding: 1rem 1.5rem;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.back-btn {
    flex-shrink: 0;
}

.header-info {
    min-width: 0;
}

.header-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0;
    line-height: 1.3;
}

.header-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

.cancel-btn {
    display: none;
}

/* Tier Banner */
.tier-banner {
    margin-bottom: 1.5rem;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.form-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.sidebar-column {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Preview Card */
.preview-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 1px solid #d1fae5;
    border-radius: 1rem;
    padding: 1.25rem;
}

.preview-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #065f46;
    margin: 0 0 1rem;
}

.preview-content {
    text-align: center;
}

.preview-image {
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 0.75rem;
    overflow: hidden;
    margin-bottom: 1rem;
    background: white;
}

.preview-image--empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: #f3f4f6;
    color: #9ca3af;
}

.preview-image--empty i {
    font-size: 1.5rem;
}

.preview-image--empty span {
    font-size: 0.75rem;
}

.preview-name {
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0 0 0.25rem;
}

.preview-category {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0 0 1rem;
}

.preview-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
}

.preview-stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 1.125rem;
    font-weight: 700;
    color: #106B4F;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
}

/* Sidebar Cards */
.sidebar-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    overflow: hidden;
}

.sidebar-card--tips {
    background: #fefce8;
    border-color: #fef08a;
}

.sidebar-card-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.sidebar-card--tips .sidebar-card-header {
    background: #fef9c3;
    border-bottom-color: #fef08a;
    color: #854d0e;
}

.sidebar-card-content {
    padding: 1rem;
}

.visibility-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.visibility-info {
    display: flex;
    flex-direction: column;
}

.visibility-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0D1F1B;
}

.visibility-desc {
    font-size: 0.75rem;
    color: #6b7280;
}

.tier-tag {
    margin-bottom: 0.75rem;
}

.tier-desc {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

.tips-list {
    margin: 0;
    padding-left: 1.25rem;
    font-size: 0.8125rem;
    color: #854d0e;
    line-height: 1.6;
}

.tips-list li {
    margin-bottom: 0.5rem;
}

.tips-list li:last-child {
    margin-bottom: 0;
}

/* Mobile Save Bar */
.mobile-save-bar {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid #e5e7eb;
    padding: 1rem;
    z-index: 100;
}

.mobile-save-btn {
    width: 100%;
}

/* Desktop styles */
@media (min-width: 1024px) {
    .sticky-header {
        margin: -1.5rem -1.5rem 2rem;
        padding: 1rem 2rem;
    }

    .cancel-btn {
        display: inline-flex;
    }

    .content-grid {
        grid-template-columns: 1fr 320px;
    }

    .sidebar-column {
        position: sticky;
        top: 100px;
        align-self: start;
    }

    .service-create-page {
        padding-bottom: 2rem;
    }
}

/* Mobile styles */
@media (max-width: 1023px) {
    .mobile-save-bar {
        display: block;
    }

    .header-actions .console-button:last-child {
        display: none;
    }
}

@media (max-width: 640px) {
    .sticky-header {
        margin: -1rem -1rem 1rem;
        padding: 0.875rem 1rem;
    }

    .header-title {
        font-size: 1rem;
    }

    .header-subtitle {
        display: none;
    }

    .preview-stats {
        gap: 1.5rem;
    }
}
</style>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SettingsLayout from '@/components/layout/SettingsLayout.vue';
import ConsoleFormCard from '@/components/console/ConsoleFormCard.vue';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import Message from 'primevue/message';
import RadioButton from 'primevue/radiobutton';

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
    minimum_service_price: number;
    minimum_service_price_display: string;
    minimum_deposit_percentage: number;
    can_disable_deposit: boolean;
    can_customize_deposit: boolean;
    can_pass_fees_to_client: boolean;
    is_founding_member: boolean;
    has_founding_fee_waiver: boolean;
    founding_tier: string | null;
    founding_fee_waiver_ends_at: string | null;
    deposit_help_text: string;
    price_help_text: string;
    fee_help_text: string;
}

const props = defineProps<{
    bookingSettings: BookingSettings;
    feePayer: 'provider' | 'client';
    tierRestrictions: TierRestrictions;
}>();

const form = useForm({
    requires_approval: props.bookingSettings.requires_approval,
    deposit_type: props.bookingSettings.deposit_type,
    deposit_amount: props.bookingSettings.deposit_amount,
    cancellation_policy: props.bookingSettings.cancellation_policy,
    advance_booking_days: props.bookingSettings.advance_booking_days,
    min_booking_notice_hours: props.bookingSettings.min_booking_notice_hours,
    fee_payer: props.feePayer,
});

const isDirty = computed(() => form.isDirty);

const cancellationPolicies = [
    {
        value: 'flexible',
        label: 'Flexible',
        description: 'Full refund up to 24 hours before the appointment',
    },
    {
        value: 'moderate',
        label: 'Moderate',
        description: 'Full refund up to 48 hours before, 50% refund within 48 hours',
    },
    {
        value: 'strict',
        label: 'Strict',
        description: 'Full refund up to 7 days before, 50% up to 48 hours, no refund within 48 hours',
    },
];

const depositTypeOptions = [
    { value: 'none', label: 'No deposit required' },
    { value: 'percentage', label: 'Percentage of service price' },
];

const feePayerOptions = [
    { value: 'provider', label: 'You absorb the fees (clients pay listed price)' },
    { value: 'client', label: 'Pass fees to client (added to their total)' },
];

const selectedCancellationPolicy = computed(() => {
    return cancellationPolicies.find(p => p.value === form.cancellation_policy);
});

const save = () => {
    form.put(resolveUrl(provider.settings.booking.url()), {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults({
                requires_approval: form.requires_approval,
                deposit_type: form.deposit_type,
                deposit_amount: form.deposit_amount,
                cancellation_policy: form.cancellation_policy,
                advance_booking_days: form.advance_booking_days,
                min_booking_notice_hours: form.min_booking_notice_hours,
                fee_payer: form.fee_payer,
            });
            form.reset();
        },
    });
};

// Warn on navigation with unsaved changes
const beforeUnloadHandler = (e: BeforeUnloadEvent) => {
    if (isDirty.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

onMounted(() => {
    window.addEventListener('beforeunload', beforeUnloadHandler);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', beforeUnloadHandler);
});

// Watch for deposit type changes to enforce minimum
const onDepositTypeChange = (value: string) => {
    if (value === 'percentage' && !form.deposit_amount) {
        form.deposit_amount = props.tierRestrictions.minimum_deposit_percentage;
    }
};
</script>

<template>
    <SettingsLayout title="Booking Settings">
        <div class="settings-page">
            <!-- Tier Info Banner -->
            <Message :severity="tierRestrictions.is_founding_member ? 'success' : 'info'" :closable="false" class="tier-message">
                <div class="tier-info">
                    <div class="tier-content">
                        <i :class="tierRestrictions.is_founding_member ? 'pi pi-star-fill' : 'pi pi-info-circle'"></i>
                        <div>
                            <strong>{{ tierRestrictions.tier_label }} Tier</strong>
                            <p>{{ tierRestrictions.fee_help_text }}</p>
                        </div>
                    </div>
                </div>
            </Message>

            <!-- Booking Approval -->
            <ConsoleFormCard title="Booking Approval">
                <div class="form-field">
                    <div class="switch-field">
                        <div class="switch-info">
                            <label for="requires_approval" class="switch-label">Require Manual Approval</label>
                            <p class="switch-description">
                                When enabled, you'll need to approve each booking before it's confirmed.
                                When disabled, bookings are automatically confirmed.
                            </p>
                        </div>
                        <InputSwitch
                            v-model="form.requires_approval"
                            inputId="requires_approval"
                        />
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Deposit Settings -->
            <ConsoleFormCard title="Deposit Requirements">
                <p class="section-hint">{{ tierRestrictions.deposit_help_text }}</p>

                <div class="form-grid">
                    <div class="form-field full-width">
                        <label class="form-label">Deposit Type</label>
                        <div class="radio-options">
                            <div
                                v-for="option in depositTypeOptions"
                                :key="option.value"
                                class="radio-option"
                                :class="{
                                    selected: form.deposit_type === option.value,
                                    disabled: option.value === 'none' && !tierRestrictions.can_disable_deposit
                                }"
                            >
                                <RadioButton
                                    v-model="form.deposit_type"
                                    :inputId="'deposit_' + option.value"
                                    :name="'deposit_type'"
                                    :value="option.value"
                                    :disabled="option.value === 'none' && !tierRestrictions.can_disable_deposit"
                                    @change="onDepositTypeChange(option.value)"
                                />
                                <label :for="'deposit_' + option.value" class="radio-label">
                                    {{ option.label }}
                                    <span v-if="option.value === 'none' && !tierRestrictions.can_disable_deposit" class="tier-badge">
                                        Enterprise Only
                                    </span>
                                </label>
                            </div>
                        </div>
                        <small v-if="form.errors.deposit_type" class="p-error">{{ form.errors.deposit_type }}</small>
                    </div>

                    <div v-if="form.deposit_type === 'percentage'" class="form-field">
                        <label for="deposit_amount" class="form-label">Deposit Percentage</label>
                        <div class="input-with-suffix">
                            <InputNumber
                                v-model="form.deposit_amount"
                                inputId="deposit_amount"
                                :min="tierRestrictions.minimum_deposit_percentage"
                                :max="100"
                                suffix="%"
                                :class="{ 'p-invalid': form.errors.deposit_amount }"
                                class="w-full"
                            />
                        </div>
                        <small v-if="form.errors.deposit_amount" class="p-error">{{ form.errors.deposit_amount }}</small>
                        <small v-else class="form-hint">
                            Minimum {{ tierRestrictions.minimum_deposit_percentage }}% for your tier
                        </small>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Fee Handling -->
            <ConsoleFormCard title="Transaction Fees">
                <p class="section-hint">
                    Choose who pays the {{ tierRestrictions.total_fee_rate.toFixed(1) }}% transaction fee on each booking.
                </p>

                <div class="form-field">
                    <div class="radio-options vertical">
                        <div
                            v-for="option in feePayerOptions"
                            :key="option.value"
                            class="radio-option"
                            :class="{ selected: form.fee_payer === option.value }"
                        >
                            <RadioButton
                                v-model="form.fee_payer"
                                :inputId="'fee_' + option.value"
                                :name="'fee_payer'"
                                :value="option.value"
                            />
                            <label :for="'fee_' + option.value" class="radio-label">
                                {{ option.label }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="fee-example">
                    <div class="example-header">Example: $100 service</div>
                    <div class="example-body">
                        <template v-if="form.fee_payer === 'provider'">
                            <div class="example-row">
                                <span>Client pays:</span>
                                <strong>$100.00</strong>
                            </div>
                            <div class="example-row">
                                <span>Platform fee ({{ tierRestrictions.total_fee_rate.toFixed(1) }}%):</span>
                                <span>-${{ (100 * tierRestrictions.total_fee_rate / 100).toFixed(2) }}</span>
                            </div>
                            <div class="example-row total">
                                <span>You receive:</span>
                                <strong>${{ (100 - 100 * tierRestrictions.total_fee_rate / 100).toFixed(2) }}</strong>
                            </div>
                        </template>
                        <template v-else>
                            <div class="example-row">
                                <span>Service price:</span>
                                <span>$100.00</span>
                            </div>
                            <div class="example-row">
                                <span>Platform fee ({{ tierRestrictions.total_fee_rate.toFixed(1) }}%):</span>
                                <span>+${{ (100 * tierRestrictions.total_fee_rate / 100).toFixed(2) }}</span>
                            </div>
                            <div class="example-row total">
                                <span>Client pays:</span>
                                <strong>${{ (100 + 100 * tierRestrictions.total_fee_rate / 100).toFixed(2) }}</strong>
                            </div>
                            <div class="example-row">
                                <span>You receive:</span>
                                <strong>$100.00</strong>
                            </div>
                        </template>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Booking Window -->
            <ConsoleFormCard title="Booking Window">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="min_booking_notice_hours" class="form-label">Minimum Notice</label>
                        <div class="input-with-suffix">
                            <InputNumber
                                v-model="form.min_booking_notice_hours"
                                inputId="min_booking_notice_hours"
                                :min="1"
                                :max="168"
                                suffix=" hours"
                                :class="{ 'p-invalid': form.errors.min_booking_notice_hours }"
                                class="w-full"
                            />
                        </div>
                        <small v-if="form.errors.min_booking_notice_hours" class="p-error">
                            {{ form.errors.min_booking_notice_hours }}
                        </small>
                        <small v-else class="form-hint">
                            How much notice clients must give when booking
                        </small>
                    </div>

                    <div class="form-field">
                        <label for="advance_booking_days" class="form-label">Advance Booking</label>
                        <div class="input-with-suffix">
                            <InputNumber
                                v-model="form.advance_booking_days"
                                inputId="advance_booking_days"
                                :min="1"
                                :max="365"
                                suffix=" days"
                                :class="{ 'p-invalid': form.errors.advance_booking_days }"
                                class="w-full"
                            />
                        </div>
                        <small v-if="form.errors.advance_booking_days" class="p-error">
                            {{ form.errors.advance_booking_days }}
                        </small>
                        <small v-else class="form-hint">
                            How far in advance clients can book
                        </small>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Cancellation Policy -->
            <ConsoleFormCard title="Cancellation Policy">
                <div class="form-field">
                    <div class="policy-options">
                        <div
                            v-for="policy in cancellationPolicies"
                            :key="policy.value"
                            class="policy-option"
                            :class="{ selected: form.cancellation_policy === policy.value }"
                            @click="form.cancellation_policy = policy.value"
                        >
                            <div class="policy-header">
                                <RadioButton
                                    v-model="form.cancellation_policy"
                                    :inputId="'policy_' + policy.value"
                                    :name="'cancellation_policy'"
                                    :value="policy.value"
                                />
                                <label :for="'policy_' + policy.value" class="policy-label">
                                    {{ policy.label }}
                                </label>
                            </div>
                            <p class="policy-description">{{ policy.description }}</p>
                        </div>
                    </div>
                    <small v-if="form.errors.cancellation_policy" class="p-error">
                        {{ form.errors.cancellation_policy }}
                    </small>
                </div>
            </ConsoleFormCard>

            <!-- Floating Save Button -->
            <Transition name="slide-up">
                <div v-if="isDirty" class="floating-save">
                    <div class="save-bar">
                        <span class="save-text">You have unsaved changes</span>
                        <div class="save-actions">
                            <Button
                                label="Discard"
                                severity="secondary"
                                text
                                @click="form.reset()"
                            />
                            <Button
                                label="Save Changes"
                                :loading="form.processing"
                                @click="save"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </SettingsLayout>
</template>

<style scoped>
.settings-page {
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.tier-message {
    margin: 0;
}

.tier-info {
    width: 100%;
}

.tier-content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.tier-content i {
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.tier-content p {
    margin: 0.25rem 0 0;
    font-size: 0.875rem;
    opacity: 0.9;
}

.section-hint {
    margin: 0 0 1.25rem;
    padding: 0.75rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
    line-height: 1.5;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

@media (min-width: 640px) {
    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-field.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.form-hint {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.switch-field {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.switch-info {
    flex: 1;
}

.switch-label {
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--color-slate-900, #0f172a);
}

.switch-description {
    margin: 0.375rem 0 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.5;
}

/* Radio Options */
.radio-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.radio-options.vertical {
    flex-direction: column;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.radio-option:hover:not(.disabled) {
    border-color: var(--color-slate-300, #cbd5e1);
}

.radio-option.selected {
    border-color: #106B4F;
    background: rgba(16, 107, 79, 0.04);
}

.radio-option.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.radio-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-700, #334155);
    cursor: pointer;
}

.tier-badge {
    padding: 0.125rem 0.5rem;
    background: var(--color-slate-100, #f1f5f9);
    color: var(--color-slate-500, #64748b);
    font-size: 0.6875rem;
    font-weight: 600;
    border-radius: 9999px;
    text-transform: uppercase;
}

.input-with-suffix {
    display: flex;
    align-items: center;
}

/* Policy Options */
.policy-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.policy-option {
    padding: 1rem;
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.policy-option:hover {
    border-color: var(--color-slate-300, #cbd5e1);
}

.policy-option.selected {
    border-color: #106B4F;
    background: rgba(16, 107, 79, 0.04);
}

.policy-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.policy-label {
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--color-slate-900, #0f172a);
    cursor: pointer;
}

.policy-description {
    margin: 0.5rem 0 0 1.5rem;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.5;
}

/* Fee Example */
.fee-example {
    margin-top: 1rem;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    overflow: hidden;
}

.example-header {
    padding: 0.625rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    border-bottom: 1px solid var(--color-slate-200, #e2e8f0);
}

.example-body {
    padding: 0.75rem 1rem;
}

.example-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.375rem 0;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.example-row.total {
    padding-top: 0.75rem;
    margin-top: 0.5rem;
    border-top: 1px solid var(--color-slate-200, #e2e8f0);
    color: var(--color-slate-900, #0f172a);
}

.w-full {
    width: 100%;
}

/* Floating Save Bar */
.floating-save {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    padding: 1rem;
    pointer-events: none;
}

@media (min-width: 1024px) {
    .floating-save {
        left: 260px;
    }

    .sidebar-collapsed .floating-save {
        left: 72px;
    }
}

.save-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    max-width: 800px;
    margin: 0 auto;
    padding: 0.75rem 1rem;
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    pointer-events: auto;
}

.save-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.save-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Transition */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>

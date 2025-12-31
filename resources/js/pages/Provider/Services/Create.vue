<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import { ConsoleButton, ConsoleFormCard } from '@/components/console';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import Checkbox from 'primevue/checkbox';
import providerRoutes from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon?: string;
}

interface TeamMember {
    id: number;
    name: string;
    avatar?: string;
}

interface Props {
    categories: Category[];
    providerDefaults: {
        requires_approval: boolean;
        deposit_type: string;
        deposit_amount: number;
        cancellation_policy: string;
        advance_booking_days: number;
        min_booking_notice_hours: number;
    };
    tierRestrictions: Record<string, unknown>;
    teamMembers: TeamMember[];
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    description: '',
    category_ids: [] as number[],
    duration_minutes: 60,
    price: 0,
    is_active: true,
    team_member_ids: [] as number[],
    use_provider_defaults: true as boolean,
    requires_approval: undefined as boolean | undefined,
    deposit_type: null as string | null,
    deposit_amount: null as number | null,
    cancellation_policy: null as string | null,
    advance_booking_days: null as number | null,
    min_booking_notice_hours: null as number | null,
});

const showAdvancedSettings = ref(false);

const durationOptions = [
    { label: '15 minutes', value: 15 },
    { label: '30 minutes', value: 30 },
    { label: '45 minutes', value: 45 },
    { label: '1 hour', value: 60 },
    { label: '1.5 hours', value: 90 },
    { label: '2 hours', value: 120 },
    { label: '2.5 hours', value: 150 },
    { label: '3 hours', value: 180 },
    { label: '4 hours', value: 240 },
    { label: '5 hours', value: 300 },
    { label: '6 hours', value: 360 },
    { label: '8 hours', value: 480 },
];

const depositTypeOptions = [
    { label: 'No deposit', value: 'none' },
    { label: 'Fixed amount', value: 'fixed' },
    { label: 'Percentage', value: 'percentage' },
    { label: 'Full payment', value: 'full' },
];

const cancellationPolicyOptions = [
    { label: 'Flexible (24 hours)', value: 'flexible' },
    { label: 'Moderate (48 hours)', value: 'moderate' },
    { label: 'Strict (7 days)', value: 'strict' },
    { label: 'Non-refundable', value: 'non_refundable' },
];


const submit = () => {
    form.post(resolveUrl(providerRoutes.services.store.url()), {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get(resolveUrl(providerRoutes.services.index.url()));
};
</script>

<template>
    <ConsoleLayout title="Create Service">
        <div class="create-service-page">
            <!-- Header -->
            <div class="page-header">
                <button class="back-btn" @click="cancel">
                    <i class="pi pi-arrow-left"></i>
                </button>
                <div class="header-info">
                    <h1 class="page-title">Create Service</h1>
                    <p class="page-subtitle">Add a new service to your offerings</p>
                </div>
            </div>

            <div class="page-layout">
                <form @submit.prevent="submit" class="service-form">
                    <!-- Basic Info -->
                <ConsoleFormCard title="Basic Information">
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="name" class="form-label">Service Name *</label>
                            <InputText
                                id="name"
                                v-model="form.name"
                                placeholder="e.g., Haircut, Massage, Consultation"
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.name }"
                            />
                            <small v-if="form.errors.name" class="p-error">{{
                                form.errors.name
                            }}</small>
                        </div>

                        <div class="form-field">
                            <label for="categories" class="form-label">Categories</label>
                            <MultiSelect
                                id="categories"
                                v-model="form.category_ids"
                                :options="categories"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select categories"
                                display="chip"
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.category_ids }"
                            />
                            <small class="form-hint">Optional - organize your services</small>
                            <small v-if="form.errors.category_ids" class="p-error">{{
                                form.errors.category_ids
                            }}</small>
                        </div>

                        <div class="form-field full-width">
                            <label for="description" class="form-label">Description</label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Describe your service..."
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.description }"
                            />
                            <small v-if="form.errors.description" class="p-error">{{
                                form.errors.description
                            }}</small>
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Pricing & Duration -->
                <ConsoleFormCard title="Pricing & Duration">
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="price" class="form-label">Price *</label>
                            <InputNumber
                                id="price"
                                v-model="form.price"
                                mode="currency"
                                currency="USD"
                                locale="en-US"
                                :min="0"
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.price }"
                            />
                            <small v-if="form.errors.price" class="p-error">{{
                                form.errors.price
                            }}</small>
                        </div>

                        <div class="form-field">
                            <label for="duration" class="form-label">Duration *</label>
                            <Select
                                id="duration"
                                v-model="form.duration_minutes"
                                :options="durationOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.duration_minutes }"
                            />
                            <small v-if="form.errors.duration_minutes" class="p-error">{{
                                form.errors.duration_minutes
                            }}</small>
                        </div>

                        <div class="form-field">
                            <label class="form-label">Active</label>
                            <div class="switch-field">
                                <ToggleSwitch v-model="form.is_active" />
                                <span class="switch-label">{{
                                    form.is_active ? 'Service is visible' : 'Service is hidden'
                                }}</span>
                            </div>
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Media Upload Info -->
                <ConsoleFormCard title="Images & Videos">
                    <div class="info-message">
                        <i class="pi pi-info-circle"></i>
                        <span>You can add cover images, gallery photos, and videos after creating the service.</span>
                    </div>
                </ConsoleFormCard>

                <!-- Advanced Settings -->
                <ConsoleFormCard>
                    <template #header>
                        <div class="advanced-header">
                            <div class="advanced-title">
                                <h3>Booking Settings</h3>
                                <p class="advanced-subtitle">
                                    Override your default booking settings for this service
                                </p>
                            </div>
                            <ToggleSwitch
                                v-model="showAdvancedSettings"
                                @update:modelValue="
                                    (val: boolean) => {
                                        form.use_provider_defaults = !val;
                                    }
                                "
                            />
                        </div>
                    </template>

                    <div v-if="showAdvancedSettings" class="form-grid">
                        <div class="form-field">
                            <label class="form-label">Requires Approval</label>
                            <div class="switch-field">
                                <ToggleSwitch v-model="form.requires_approval" />
                                <span class="switch-label">{{
                                    form.requires_approval
                                        ? 'Manual approval required'
                                        : 'Auto-confirm bookings'
                                }}</span>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="deposit_type" class="form-label">Deposit Type</label>
                            <Select
                                id="deposit_type"
                                v-model="form.deposit_type"
                                :options="depositTypeOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="form-input"
                            />
                        </div>

                        <div
                            v-if="form.deposit_type === 'fixed' || form.deposit_type === 'percentage'"
                            class="form-field"
                        >
                            <label for="deposit_amount" class="form-label">
                                {{ form.deposit_type === 'percentage' ? 'Deposit %' : 'Deposit Amount' }}
                            </label>
                            <InputNumber
                                id="deposit_amount"
                                v-model="form.deposit_amount"
                                :mode="form.deposit_type === 'percentage' ? 'decimal' : 'currency'"
                                :currency="form.deposit_type === 'fixed' ? 'USD' : undefined"
                                :locale="form.deposit_type === 'fixed' ? 'en-US' : undefined"
                                :suffix="form.deposit_type === 'percentage' ? '%' : undefined"
                                :min="0"
                                :max="form.deposit_type === 'percentage' ? 100 : undefined"
                                class="form-input"
                            />
                        </div>

                        <div class="form-field">
                            <label for="cancellation_policy" class="form-label"
                                >Cancellation Policy</label
                            >
                            <Select
                                id="cancellation_policy"
                                v-model="form.cancellation_policy"
                                :options="cancellationPolicyOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="form-input"
                            />
                        </div>

                        <div class="form-field">
                            <label for="advance_booking_days" class="form-label"
                                >Advance Booking (days)</label
                            >
                            <InputNumber
                                id="advance_booking_days"
                                v-model="form.advance_booking_days"
                                :min="1"
                                :max="365"
                                class="form-input"
                            />
                            <small class="form-hint">How far in advance can clients book?</small>
                        </div>

                        <div class="form-field">
                            <label for="min_booking_notice_hours" class="form-label"
                                >Minimum Notice (hours)</label
                            >
                            <InputNumber
                                id="min_booking_notice_hours"
                                v-model="form.min_booking_notice_hours"
                                :min="0"
                                :max="168"
                                class="form-input"
                            />
                            <small class="form-hint">Minimum hours before appointment</small>
                        </div>
                    </div>

                    <div v-else class="defaults-info">
                        <i class="pi pi-info-circle"></i>
                        <span>Using your default booking settings</span>
                    </div>
                </ConsoleFormCard>

                    <!-- Spacer for floating footer -->
                    <div class="form-footer-spacer"></div>
                </form>

                <!-- Team Member Sidebar -->
                <aside v-if="teamMembers.length > 0" class="team-sidebar">
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Team Assignment</h3>
                        <p class="sidebar-description">
                            Select who can perform this service
                        </p>
                        <div class="team-member-list">
                            <label
                                v-for="member in teamMembers"
                                :key="member.id"
                                class="team-member-item"
                                :class="{ selected: form.team_member_ids.includes(member.id) }"
                            >
                                <Checkbox
                                    v-model="form.team_member_ids"
                                    :value="member.id"
                                    :inputId="`team-member-${member.id}`"
                                />
                                <div class="member-avatar">
                                    <img
                                        v-if="member.avatar"
                                        :src="member.avatar"
                                        :alt="member.name"
                                    />
                                    <span v-else class="avatar-placeholder">
                                        {{ member.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <span class="member-name">{{ member.name }}</span>
                            </label>
                        </div>
                        <p v-if="form.team_member_ids.length === 0" class="sidebar-hint">
                            <i class="pi pi-info-circle"></i>
                            If none selected, assigned to you
                        </p>
                    </div>
                </aside>
            </div>

            <!-- Floating Action Bar -->
            <div class="floating-actions">
                <div class="floating-actions-content">
                    <div class="floating-buttons">
                        <ConsoleButton
                            type="button"
                            label="Cancel"
                            variant="secondary"
                            size="small"
                            @click="cancel"
                        />
                        <ConsoleButton
                            type="button"
                            label="Create Service"
                            icon="pi pi-check"
                            variant="primary"
                            size="small"
                            :loading="form.processing"
                            @click="submit"
                        />
                    </div>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.create-service-page {
    max-width: 960px;
    margin: 0 auto;
    padding-bottom: 2rem;
}

/* Two-column layout */
.page-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .page-layout {
        grid-template-columns: 1fr 220px;
    }
}

/* Header */
.page-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.back-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.back-btn:hover {
    background-color: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-900, #0f172a);
}

.header-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.page-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.page-subtitle {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

/* Form */
.service-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 639px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-field.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.form-input {
    width: 100%;
}

.form-hint {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.switch-field {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.switch-label {
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

/* Info Message */
.info-message {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background-color: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.info-message i {
    color: #4f46e5;
}

/* Advanced Settings */
.advanced-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.advanced-title h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.advanced-subtitle {
    margin: 0.25rem 0 0;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
}

.defaults-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background-color: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.defaults-info i {
    color: #106b4f;
}

/* Footer Spacer */
.form-footer-spacer {
    height: 80px;
}

/* Floating Actions */
.floating-actions {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: white;
    border-top: 1px solid var(--color-slate-200, #e2e8f0);
    box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
}

.floating-actions-content {
    max-width: 960px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 1rem;
}

.floating-buttons {
    display: flex;
    gap: 0.75rem;
}

@media (max-width: 639px) {
    .floating-actions-content {
        padding: 1rem;
    }

    .floating-buttons {
        width: 100%;
    }

    .floating-buttons :deep(button) {
        flex: 1;
    }
}

/* Sidebar offset for floating bar */
@media (min-width: 1024px) {
    .floating-actions {
        left: 280px;
    }
}

/* Team Sidebar */
.team-sidebar {
    position: sticky;
    top: 5rem; /* Account for topbar height */
    height: fit-content;
}

.sidebar-card {
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
    padding: 1rem;
}

.sidebar-title {
    margin: 0 0 0.25rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.sidebar-description {
    margin: 0 0 1rem;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
}

.team-member-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.team-member-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}

.team-member-item:hover {
    background-color: var(--color-slate-50, #f8fafc);
}

.team-member-item.selected {
    background-color: #eef2ff;
    border-color: #c7d2fe;
}

.member-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    background-color: var(--color-slate-100, #f1f5f9);
}

.member-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-slate-600, #475569);
    background-color: var(--color-slate-200, #e2e8f0);
}

.member-name {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-hint {
    display: flex;
    align-items: flex-start;
    gap: 0.375rem;
    margin: 0.75rem 0 0;
    padding: 0.5rem;
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
    background-color: var(--color-slate-50, #f8fafc);
    border-radius: 0.375rem;
}

.sidebar-hint i {
    font-size: 0.75rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

@media (max-width: 767px) {
    .team-sidebar {
        order: -1;
        position: relative;
        top: 0;
    }

    .sidebar-card {
        padding: 0.875rem;
    }

    .sidebar-title {
        font-size: 0.875rem;
    }

    .sidebar-description {
        margin-bottom: 0.75rem;
        font-size: 0.75rem;
    }

    .team-member-list {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .team-member-item {
        flex: 0 0 auto;
        padding: 0.375rem 0.625rem;
        gap: 0.5rem;
        background-color: var(--color-slate-50, #f8fafc);
        border: 1px solid var(--color-slate-200, #e2e8f0);
    }

    .team-member-item.selected {
        background-color: #eef2ff;
        border-color: #818cf8;
    }

    .member-avatar {
        width: 24px;
        height: 24px;
    }

    .avatar-placeholder {
        font-size: 0.625rem;
    }

    .member-name {
        font-size: 0.75rem;
    }

    .sidebar-hint {
        margin-top: 0.5rem;
        padding: 0.375rem 0.5rem;
        font-size: 0.6875rem;
    }
}
</style>

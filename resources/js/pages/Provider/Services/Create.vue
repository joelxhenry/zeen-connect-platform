<script setup lang="ts">
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import type { Category } from '@/types/models';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import Message from 'primevue/message';

interface Props {
    categories: Category[];
}

const props = defineProps<Props>();
const page = usePage();

const form = useForm({
    category_id: null as number | null,
    name: '',
    description: '',
    duration_minutes: 60,
    price: null as number | null,
    is_active: true,
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
    { label: '3 hours 30 minutes', value: 210 },
    { label: '4 hours', value: 240 },
    { label: '5 hours', value: 300 },
    { label: '6 hours', value: 360 },
    { label: '7 hours', value: 420 },
    { label: '8 hours', value: 480 },
];

const submit = () => {
    form.post(route('provider.services.store'));
};

const descriptionLength = computed(() => form.description?.length || 0);
</script>

<template>
    <ConsoleLayout title="Add Service">
        <div class="service-page">
            <!-- Header -->
            <div class="page-header">
                <div class="header-content">
                    <Link :href="route('provider.services.index')" class="back-link">
                        <i class="pi pi-arrow-left"></i>
                    </Link>
                    <div class="header-icon">
                        <i class="pi pi-plus"></i>
                    </div>
                    <div>
                        <h1 class="header-title">Add New Service</h1>
                        <p class="header-subtitle">Create a service that clients can book</p>
                    </div>
                </div>
                <div class="header-actions">
                    <Link :href="route('provider.services.index')">
                        <Button type="button" label="Cancel" severity="secondary" text />
                    </Link>
                    <Button
                        type="submit"
                        label="Create Service"
                        :loading="form.processing"
                        @click="submit"
                        :disabled="!form.isDirty"
                    />
                </div>
            </div>

            <!-- Flash Message -->
            <Message
                v-if="page.props.flash?.success"
                severity="success"
                :closable="true"
                class="flash-message"
            >
                {{ page.props.flash.success }}
            </Message>

            <form @submit.prevent="submit" class="service-form">
                <!-- Service Details Section -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="pi pi-box"></i>
                        </div>
                        <div>
                            <h2 class="section-title">Service Details</h2>
                            <p class="section-desc">Basic information about your service</p>
                        </div>
                    </div>

                    <div class="section-content">
                        <div class="form-group">
                            <label for="category" class="form-label">
                                Category <span class="required">*</span>
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
                            >
                                <template #option="{ option }">
                                    <div class="flex items-center gap-2">
                                        <i :class="`pi ${option.icon}`" class="text-[var(--p-surface-500)]"></i>
                                        <span>{{ option.name }}</span>
                                    </div>
                                </template>
                                <template #value="{ value }">
                                    <div v-if="value" class="flex items-center gap-2">
                                        <i :class="`pi ${categories.find(c => c.id === value)?.icon}`" class="text-[var(--p-surface-500)]"></i>
                                        <span>{{ categories.find(c => c.id === value)?.name }}</span>
                                    </div>
                                    <span v-else class="text-[var(--p-surface-400)]">Select a category</span>
                                </template>
                            </Select>
                            <small v-if="form.errors.category_id" class="p-error">{{ form.errors.category_id }}</small>
                            <small v-else class="field-hint">Choose the category that best describes your service</small>
                        </div>

                        <div class="form-group">
                            <label for="name" class="form-label">
                                Service Name <span class="required">*</span>
                            </label>
                            <InputText
                                id="name"
                                v-model="form.name"
                                placeholder="e.g., Men's Haircut, Full Body Massage"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.name }"
                            />
                            <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                placeholder="Describe what's included in this service, any special techniques, or what clients can expect..."
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.description }"
                                autoResize
                            />
                            <div class="field-footer">
                                <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
                                <small v-else class="field-hint">Optional - helps clients understand what to expect</small>
                                <small class="char-count">{{ descriptionLength }}/500</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Divider -->
                <div class="section-divider"></div>

                <!-- Pricing Section -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="pi pi-dollar"></i>
                        </div>
                        <div>
                            <h2 class="section-title">Duration & Pricing</h2>
                            <p class="section-desc">How long the service takes and what you charge</p>
                        </div>
                    </div>

                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="duration" class="form-label">
                                    Duration <span class="required">*</span>
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
                                <small v-if="form.errors.duration_minutes" class="p-error">{{ form.errors.duration_minutes }}</small>
                                <small v-else class="field-hint">How long does this service typically take?</small>
                            </div>

                            <div class="form-group">
                                <label for="price" class="form-label">
                                    Price <span class="required">*</span>
                                </label>
                                <InputNumber
                                    id="price"
                                    v-model="form.price"
                                    mode="currency"
                                    currency="USD"
                                    locale="en-US"
                                    placeholder="0.00"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.price }"
                                    :minFractionDigits="2"
                                    :maxFractionDigits="2"
                                />
                                <small v-if="form.errors.price" class="p-error">{{ form.errors.price }}</small>
                                <small v-else class="field-hint">The price clients will pay for this service</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Divider -->
                <div class="section-divider"></div>

                <!-- Visibility Section -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="pi pi-eye"></i>
                        </div>
                        <div>
                            <h2 class="section-title">Visibility</h2>
                            <p class="section-desc">Control if clients can see this service</p>
                        </div>
                    </div>

                    <div class="section-content">
                        <div class="toggle-item">
                            <div class="toggle-content">
                                <span class="toggle-label">Service is active</span>
                                <span class="toggle-desc">When active, clients can see and book this service</span>
                            </div>
                            <InputSwitch v-model="form.is_active" inputId="is_active" />
                        </div>
                    </div>
                </section>

                <!-- Mobile Actions -->
                <div class="mobile-actions">
                    <Link :href="route('provider.services.index')" class="w-full">
                        <Button type="button" label="Cancel" severity="secondary" outlined class="w-full" />
                    </Link>
                    <Button
                        type="submit"
                        label="Create Service"
                        class="w-full"
                        :loading="form.processing"
                        :disabled="!form.isDirty"
                    />
                </div>
            </form>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.service-page {
    max-width: 900px;
    margin: 0 auto;
}

/* Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--p-surface-200);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.back-link {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: var(--p-surface-500);
    transition: all 0.2s;
}

.back-link:hover {
    background-color: var(--p-surface-100);
    color: var(--p-surface-700);
}

.header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--p-primary-color), var(--p-primary-400));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.header-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.header-subtitle {
    font-size: 0.875rem;
    color: var(--p-surface-500);
    margin: 0.25rem 0 0 0;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .header-actions {
        display: none;
    }
}

/* Flash Message */
.flash-message {
    margin-bottom: 1.5rem;
}

/* Form Sections */
.form-section {
    padding: 0.5rem 0;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    margin-bottom: 1.5rem;
}

.section-icon {
    width: 40px;
    height: 40px;
    background-color: var(--p-surface-100);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--p-surface-600);
    font-size: 1rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0;
}

.section-desc {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
    margin: 0.125rem 0 0 0;
}

.section-content {
    padding-left: 3.375rem;
}

.section-divider {
    height: 1px;
    background-color: var(--p-surface-200);
    margin: 1.5rem 0;
}

@media (max-width: 640px) {
    .section-content {
        padding-left: 0;
    }
}

/* Form Layout */
.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-700);
}

.required {
    color: #ef4444;
}

.field-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.field-hint {
    font-size: 0.75rem;
    color: var(--p-surface-400);
}

.char-count {
    font-size: 0.75rem;
    color: var(--p-surface-400);
    margin-left: auto;
}

/* Toggle Item */
.toggle-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background-color: var(--p-surface-50);
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
}

.toggle-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.toggle-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-900);
}

.toggle-desc {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

/* Mobile Actions */
.mobile-actions {
    display: none;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--p-surface-200);
}

@media (max-width: 768px) {
    .mobile-actions {
        display: flex;
    }
}
</style>

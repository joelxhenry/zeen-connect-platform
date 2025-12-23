<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import type { Provider, Country } from '@/types/models';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import Button from 'primevue/button';
import Message from 'primevue/message';

interface Props {
    provider: Provider & {
        primary_location?: {
            id: number;
            name: string;
            region: {
                id: number;
                name: string;
                country: {
                    id: number;
                    name: string;
                };
            };
        };
        locations?: Array<{ id: number; name: string }>;
    };
    countries: Country[];
}

const props = defineProps<Props>();
const page = usePage();

const form = useForm({
    business_name: props.provider?.business_name || '',
    tagline: props.provider?.tagline || '',
    bio: props.provider?.bio || '',
    address: props.provider?.address || '',
    website: props.provider?.website || '',
    phone: page.props.auth?.user?.phone || '',
    social_links: {
        instagram: props.provider?.social_links?.instagram || '',
        facebook: props.provider?.social_links?.facebook || '',
        twitter: props.provider?.social_links?.twitter || '',
        tiktok: props.provider?.social_links?.tiktok || '',
        youtube: props.provider?.social_links?.youtube || '',
    },
    primary_location_id: props.provider?.primary_location?.id || null,
    location_ids: props.provider?.locations?.map(l => l.id) || [],
});

// Flatten all locations from all countries/regions into a single list with grouping
const allLocations = computed(() => {
    const locations: Array<{ id: number; name: string; group: string }> = [];
    props.countries.forEach(country => {
        country.regions?.forEach(region => {
            region.locations?.forEach(location => {
                locations.push({
                    id: location.id,
                    name: location.name,
                    group: `${region.name}, ${country.name}`,
                });
            });
        });
    });
    return locations;
});

// Handle location selection for multi-location
const onLocationsChange = () => {
    if (form.primary_location_id && !form.location_ids.includes(form.primary_location_id)) {
        form.primary_location_id = form.location_ids.length > 0 ? form.location_ids[0] : null;
    }
    if (!form.primary_location_id && form.location_ids.length > 0) {
        form.primary_location_id = form.location_ids[0];
    }
};

const submit = () => {
    form.put(route('provider.profile.update'), {
        preserveScroll: true,
    });
};

const bioLength = computed(() => form.bio?.length || 0);

const primaryLocationOptions = computed(() => {
    return form.location_ids.map(id => {
        const loc = allLocations.value.find(l => l.id === id);
        return { label: loc ? `${loc.name}, ${loc.group}` : '', value: id };
    });
});

const socialPlatforms = [
    { key: 'instagram', label: 'Instagram', icon: 'pi-instagram', prefix: 'instagram.com/', placeholder: 'username' },
    { key: 'facebook', label: 'Facebook', icon: 'pi-facebook', prefix: 'facebook.com/', placeholder: 'pagename' },
    { key: 'tiktok', label: 'TikTok', icon: 'pi-tiktok', prefix: 'tiktok.com/@', placeholder: 'username' },
    { key: 'youtube', label: 'YouTube', icon: 'pi-youtube', prefix: 'youtube.com/', placeholder: 'channel' },
];
</script>

<template>
    <ConsoleLayout title="Edit Profile">
        <div class="profile-page">
            <!-- Header -->
            <div class="profile-header">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="pi pi-user"></i>
                    </div>
                    <div>
                        <h1 class="header-title">Business Profile</h1>
                        <p class="header-subtitle">Customize how clients see your business</p>
                    </div>
                </div>
                <div class="header-actions">
                    <Button
                        type="button"
                        label="Discard"
                        severity="secondary"
                        text
                        @click="form.reset()"
                        :disabled="!form.isDirty"
                    />
                    <Button
                        type="submit"
                        label="Save changes"
                        :loading="form.processing"
                        @click="submit"
                        :disabled="!form.isDirty"
                    />
                </div>
            </div>

            <!-- Success Message -->
            <Message
                v-if="page.props.flash?.success"
                severity="success"
                :closable="true"
                class="flash-message"
            >
                {{ page.props.flash.success }}
            </Message>

            <form @submit.prevent="submit" class="profile-form">
                <!-- Basic Info Section -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="pi pi-building"></i>
                        </div>
                        <div>
                            <h2 class="section-title">Basic Information</h2>
                            <p class="section-desc">Your business identity</p>
                        </div>
                    </div>

                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="business_name" class="form-label">
                                    Business Name <span class="required">*</span>
                                </label>
                                <InputText
                                    id="business_name"
                                    v-model="form.business_name"
                                    placeholder="Enter your business name"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.business_name }"
                                />
                                <small v-if="form.errors.business_name" class="p-error">
                                    {{ form.errors.business_name }}
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="tagline" class="form-label">Tagline</label>
                                <InputText
                                    id="tagline"
                                    v-model="form.tagline"
                                    placeholder="A catchy phrase for your business"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.tagline }"
                                />
                                <small v-if="form.errors.tagline" class="p-error">
                                    {{ form.errors.tagline }}
                                </small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bio" class="form-label">About your business</label>
                            <Textarea
                                id="bio"
                                v-model="form.bio"
                                rows="4"
                                placeholder="Tell clients about yourself and your services..."
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.bio }"
                                autoResize
                            />
                            <div class="field-footer">
                                <small v-if="form.errors.bio" class="p-error">{{ form.errors.bio }}</small>
                                <small class="char-count">{{ bioLength }}/1000</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Divider -->
                <div class="section-divider"></div>

                <!-- Contact Section -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="pi pi-phone"></i>
                        </div>
                        <div>
                            <h2 class="section-title">Contact Details</h2>
                            <p class="section-desc">How clients can reach you</p>
                        </div>
                    </div>

                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <InputText
                                    id="phone"
                                    v-model="form.phone"
                                    placeholder="+1 (876) 555-0123"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.phone }"
                                />
                                <small v-if="form.errors.phone" class="p-error">{{ form.errors.phone }}</small>
                            </div>

                            <div class="form-group">
                                <label for="website" class="form-label">Website</label>
                                <InputText
                                    id="website"
                                    v-model="form.website"
                                    placeholder="https://yourwebsite.com"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.website }"
                                />
                                <small v-if="form.errors.website" class="p-error">{{ form.errors.website }}</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">Business Address</label>
                            <InputText
                                id="address"
                                v-model="form.address"
                                placeholder="Your business address"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.address }"
                            />
                            <small v-if="form.errors.address" class="p-error">{{ form.errors.address }}</small>
                        </div>
                    </div>
                </section>

                <!-- Divider -->
                <div class="section-divider"></div>

                <!-- Service Locations Section -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="pi pi-map"></i>
                        </div>
                        <div>
                            <h2 class="section-title">Service Areas</h2>
                            <p class="section-desc">Where you offer your services</p>
                        </div>
                    </div>

                    <div class="section-content">
                        <div class="form-group">
                            <label for="locations" class="form-label">Service Locations</label>
                            <MultiSelect
                                id="locations"
                                v-model="form.location_ids"
                                :options="allLocations"
                                optionLabel="name"
                                optionValue="id"
                                optionGroupLabel="group"
                                optionGroupChildren="items"
                                placeholder="Search and select locations..."
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.primary_location_id }"
                                @change="onLocationsChange"
                                display="chip"
                                filter
                                :filterFields="['name', 'group']"
                                :maxSelectedLabels="5"
                            >
                                <template #option="{ option }">
                                    <div class="flex flex-col">
                                        <span>{{ option.name }}</span>
                                        <span class="text-xs text-[var(--p-surface-500)]">{{ option.group }}</span>
                                    </div>
                                </template>
                                <template #chip="{ value }">
                                    <span class="text-sm">{{ allLocations.find(l => l.id === value)?.name }}</span>
                                </template>
                            </MultiSelect>
                            <small class="field-hint">You can select locations from different regions and countries</small>
                            <small v-if="form.errors.primary_location_id" class="p-error">
                                {{ form.errors.primary_location_id }}
                            </small>
                        </div>

                        <div v-if="form.location_ids.length > 1" class="form-group primary-location">
                            <label for="primary_location" class="form-label">Primary Location</label>
                            <Select
                                id="primary_location"
                                v-model="form.primary_location_id"
                                :options="primaryLocationOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select primary location"
                                class="w-full"
                            />
                            <small class="field-hint">This will be displayed as your main location</small>
                        </div>
                    </div>
                </section>

                <!-- Divider -->
                <div class="section-divider"></div>

                <!-- Social Links Section -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="pi pi-share-alt"></i>
                        </div>
                        <div>
                            <h2 class="section-title">Social Presence</h2>
                            <p class="section-desc">Connect your social media accounts</p>
                        </div>
                    </div>

                    <div class="section-content">
                        <div class="social-grid">
                            <div
                                v-for="platform in socialPlatforms"
                                :key="platform.key"
                                class="social-item"
                            >
                                <div class="social-icon" :class="`social-${platform.key}`">
                                    <i :class="`pi ${platform.icon}`"></i>
                                </div>
                                <div class="social-input">
                                    <span class="social-prefix">{{ platform.prefix }}</span>
                                    <InputText
                                        :id="platform.key"
                                        v-model="(form.social_links as Record<string, string>)[platform.key]"
                                        :placeholder="platform.placeholder"
                                        class="w-full"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Mobile Submit -->
                <div class="mobile-actions">
                    <Button
                        type="button"
                        label="Discard changes"
                        severity="secondary"
                        outlined
                        class="w-full"
                        @click="form.reset()"
                        :disabled="!form.isDirty"
                    />
                    <Button
                        type="submit"
                        label="Save changes"
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
.profile-page {
    max-width: 900px;
    margin: 0 auto;
}

/* Header */
.profile-header {
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
    .profile-header {
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
    margin-bottom: 1.25rem;
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

.primary-location {
    max-width: 400px;
    margin-top: 1.25rem;
}

/* Social Links */
.social-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 640px) {
    .social-grid {
        grid-template-columns: 1fr;
    }
}

.social-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background-color: var(--p-surface-50);
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.social-item:hover {
    border-color: var(--p-surface-300);
}

.social-item:focus-within {
    border-color: var(--p-primary-color);
    box-shadow: 0 0 0 1px var(--p-primary-color);
}

.social-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.social-instagram {
    background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
}

.social-facebook {
    background-color: #1877f2;
}

.social-tiktok {
    background-color: #000000;
}

.social-youtube {
    background-color: #ff0000;
}

.social-input {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    min-width: 0;
}

.social-prefix {
    font-size: 0.75rem;
    color: var(--p-surface-400);
    white-space: nowrap;
}

.social-input :deep(.p-inputtext) {
    border: none;
    background: transparent;
    padding: 0.5rem 0;
    font-size: 0.875rem;
}

.social-input :deep(.p-inputtext:focus) {
    box-shadow: none;
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

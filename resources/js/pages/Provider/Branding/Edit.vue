<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SettingsLayout from '@/components/layout/SettingsLayout.vue';
import ConsoleFormCard from '@/components/console/ConsoleFormCard.vue';
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import GalleryUpload from '@/components/media/GalleryUpload.vue';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ColorPicker from 'primevue/colorpicker';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';

interface MediaItem {
    id: number;
    uuid: string;
    url: string;
    thumbnail: string;
    medium: string;
    large: string;
    filename: string;
    order: number;
}

interface VideoEmbed {
    id: number;
    uuid: string;
    platform: string;
    video_id: string;
    url: string;
    embed_url: string;
    title: string | null;
    thumbnail_url: string | null;
    order: number;
}

interface BrandSettings {
    primary_color: string | null;
    secondary_color: string | null;
    color_mode: 'light' | 'dark' | 'system';
}

interface DefaultColors {
    primary_color: string;
    secondary_color: string;
}

interface Template {
    value: string;
    label: string;
    description: string;
    thumbnail?: string;
    is_available: boolean;
    is_selected: boolean;
    required_tier?: string;
}

interface Industry {
    id: number;
    name: string;
    icon: string | null;
}

interface SocialLinks {
    facebook?: string;
    instagram?: string;
    twitter?: string;
    linkedin?: string;
    tiktok?: string;
}

interface Content {
    business_name: string;
    industry_id: number | null;
    bio: string | null;
    tagline: string | null;
    address: string | null;
    website: string | null;
    social_links: SocialLinks;
}

const props = defineProps<{
    canAccess: boolean;
    currentTier: string;
    currentTierLabel: string;
    brandSettings: BrandSettings;
    defaultColors: DefaultColors;
    logo: MediaItem | null;
    cover: MediaItem | null;
    gallery: MediaItem[];
    templates: Template[];
    currentTemplate: string;
    content: Content;
    industries: Industry[];
    domain: string;
    siteUrl: string;
}>();

// Reactive media state
const logoMedia = ref<MediaItem | null>(props.logo);
const coverMedia = ref<MediaItem | null>(props.cover);
const galleryMedia = ref<MediaItem[]>(props.gallery || []);

// Active tab
const activeTab = ref(0);

// ============================================
// VISUALS TAB - Colors Form
// ============================================
const colorForm = useForm({
    primary_color: props.brandSettings.primary_color || props.defaultColors.primary_color,
    secondary_color: props.brandSettings.secondary_color || props.defaultColors.secondary_color,
    color_mode: props.brandSettings.color_mode || 'system',
});

const colorFields = [
    { key: 'primary_color', label: 'Primary', description: 'Main brand color for buttons and key elements' },
    { key: 'secondary_color', label: 'Secondary', description: 'Complementary color for accents and backgrounds' },
] as const;

const colorModeOptions: { value: 'light' | 'dark' | 'system'; label: string; icon: string }[] = [
    { value: 'light', label: 'Light', icon: 'pi pi-sun' },
    { value: 'dark', label: 'Dark', icon: 'pi pi-moon' },
    { value: 'system', label: 'System', icon: 'pi pi-desktop' },
];

const toPickerValue = (hex: string | null): string => {
    if (!hex) return '';
    return hex.replace('#', '');
};

const fromPickerValue = (value: string): string => {
    if (!value) return '';
    return value.startsWith('#') ? value : `#${value}`;
};

const saveColors = () => {
    colorForm.put(resolveUrl(provider.branding.update.url()), {
        preserveScroll: true,
        onSuccess: () => {
            colorForm.defaults({
                primary_color: colorForm.primary_color,
                secondary_color: colorForm.secondary_color,
                color_mode: colorForm.color_mode,
            });
            colorForm.reset();
        },
    });
};

const resetColorsToDefaults = () => {
    colorForm.primary_color = props.defaultColors.primary_color;
    colorForm.secondary_color = props.defaultColors.secondary_color;
    colorForm.color_mode = 'system';
};

// Template Selection
const selectedTemplate = ref(props.currentTemplate);
const templateSaving = ref(false);

const selectTemplate = async (template: Template) => {
    if (!template.is_available || templateSaving.value) return;

    templateSaving.value = true;
    selectedTemplate.value = template.value;

    router.put(resolveUrl(provider.branding.template.url()), {
        template: template.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            templateSaving.value = false;
        },
    });
};

// Media handlers
const handleLogoUploaded = () => {
    router.reload({ only: ['logo'] });
};

const handleCoverUploaded = () => {
    router.reload({ only: ['cover'] });
};

const handleGalleryUpdated = () => {
    router.reload({ only: ['gallery'] });
};

// ============================================
// CONTENT TAB
// ============================================
const contentForm = useForm({
    business_name: props.content.business_name || '',
    industry_id: props.content.industry_id,
    bio: props.content.bio || '',
    tagline: props.content.tagline || '',
    address: props.content.address || '',
    website: props.content.website || '',
    social_links: {
        facebook: props.content.social_links?.facebook || '',
        instagram: props.content.social_links?.instagram || '',
        twitter: props.content.social_links?.twitter || '',
        linkedin: props.content.social_links?.linkedin || '',
        tiktok: props.content.social_links?.tiktok || '',
    },
});

const socialPlatforms = [
    { key: 'facebook', label: 'Facebook', icon: 'pi pi-facebook', placeholder: 'https://facebook.com/yourbusiness' },
    { key: 'instagram', label: 'Instagram', icon: 'pi pi-instagram', placeholder: 'https://instagram.com/yourbusiness' },
    { key: 'twitter', label: 'X (Twitter)', icon: 'pi pi-twitter', placeholder: 'https://x.com/yourbusiness' },
    { key: 'linkedin', label: 'LinkedIn', icon: 'pi pi-linkedin', placeholder: 'https://linkedin.com/company/yourbusiness' },
    { key: 'tiktok', label: 'TikTok', icon: 'pi pi-tiktok', placeholder: 'https://tiktok.com/@yourbusiness' },
];

const saveContent = () => {
    contentForm.put(resolveUrl(provider.branding.content.url()), {
        preserveScroll: true,
        onSuccess: () => {
            contentForm.defaults({
                business_name: contentForm.business_name,
                industry_id: contentForm.industry_id,
                bio: contentForm.bio,
                tagline: contentForm.tagline,
                address: contentForm.address,
                website: contentForm.website,
                social_links: { ...contentForm.social_links },
            });
            contentForm.reset();
        },
    });
};

// ============================================
// DOMAIN TAB
// ============================================
const domainForm = useForm({
    domain: props.domain,
});

const domainAvailable = ref<boolean | null>(null);
const domainChecking = ref(false);
let domainCheckTimeout: ReturnType<typeof setTimeout> | null = null;

const domainPreview = computed(() => {
    const baseDomain = props.siteUrl.replace(props.domain, '').replace(/^https?:\/\//, '').replace(/\/$/, '');
    return `${domainForm.domain}${baseDomain ? '.' + baseDomain : ''}`;
});

const checkDomainAvailability = async () => {
    if (domainForm.domain === props.domain) {
        domainAvailable.value = null;
        return;
    }

    if (!domainForm.domain || domainForm.domain.length < 3) {
        domainAvailable.value = null;
        return;
    }

    domainChecking.value = true;

    try {
        const response = await fetch(
            resolveUrl(provider.branding.checkDomain.url()) + `?domain=${encodeURIComponent(domainForm.domain)}`,
            { credentials: 'include' }
        );
        const data = await response.json();
        domainAvailable.value = data.available;
    } catch {
        domainAvailable.value = null;
    } finally {
        domainChecking.value = false;
    }
};

watch(() => domainForm.domain, (newDomain) => {
    if (domainCheckTimeout) {
        clearTimeout(domainCheckTimeout);
    }

    // Normalize: lowercase, only allow alphanumeric and hyphens
    const normalized = newDomain.toLowerCase().replace(/[^a-z0-9-]/g, '');
    if (normalized !== newDomain) {
        domainForm.domain = normalized;
        return;
    }

    domainCheckTimeout = setTimeout(checkDomainAvailability, 500);
});

const saveDomain = () => {
    domainForm.put(resolveUrl(provider.branding.domain.url()), {
        preserveScroll: true,
        onSuccess: () => {
            domainForm.defaults({ domain: domainForm.domain });
            domainForm.reset();
            domainAvailable.value = null;
        },
    });
};

// ============================================
// PREVIEW TAB
// ============================================
const previewDevice = ref<'mobile' | 'desktop'>('desktop');
const previewKey = ref(0);

const previewUrl = computed(() => {
    return `${props.siteUrl}?preview=1&v=${previewKey.value}`;
});

const refreshPreview = () => {
    previewKey.value++;
};

// ============================================
// DIRTY STATE & NAVIGATION WARNING
// ============================================
const isDirty = computed(() => {
    return colorForm.isDirty || contentForm.isDirty || domainForm.isDirty;
});

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
    if (domainCheckTimeout) {
        clearTimeout(domainCheckTimeout);
    }
});
</script>

<template>
    <SettingsLayout title="My Brand">
        <div class="branding-page">
            <!-- Tier Restriction Banner -->
            <Message v-if="!canAccess" severity="warn" :closable="false" class="tier-message">
                <div class="tier-banner">
                    <div class="tier-content">
                        <i class="pi pi-lock"></i>
                        <div>
                            <strong>Branding customization requires Premium or higher</strong>
                            <p>You're currently on the {{ currentTierLabel }} plan. Upgrade to customize your brand.</p>
                        </div>
                    </div>
                    <Button
                        label="Upgrade Plan"
                        icon="pi pi-arrow-up-right"
                        size="small"
                        @click="router.visit(resolveUrl(provider.subscription.index.url()))"
                    />
                </div>
            </Message>

            <!-- Tab Navigation -->
            <TabView v-model:activeIndex="activeTab" class="branding-tabs">
                <!-- ================================ -->
                <!-- VISUALS TAB -->
                <!-- ================================ -->
                <TabPanel value="0" header="Visuals">
                    <div class="tab-content">
                        <!-- Logo -->
                        <ConsoleFormCard title="Logo">
                            <div class="media-section">
                                <SingleImageUpload
                                    v-model="logoMedia"
                                    :uploadUrl="resolveUrl(provider.media.upload.url())"
                                    collection="logo"
                                    shape="square"
                                    placeholder="Upload Logo"
                                    @uploaded="handleLogoUploaded"
                                />
                                <div class="media-hints">
                                    <p>Your logo appears in your booking site header and email communications.</p>
                                    <ul>
                                        <li>Recommended size: 200x200 pixels</li>
                                        <li>Formats: PNG, JPG, WebP</li>
                                        <li>Maximum file size: 2MB</li>
                                    </ul>
                                </div>
                            </div>
                        </ConsoleFormCard>

                        <!-- Cover Photo -->
                        <ConsoleFormCard title="Cover Photo">
                            <div class="cover-section">
                                <SingleImageUpload
                                    v-model="coverMedia"
                                    :uploadUrl="resolveUrl(provider.media.upload.url())"
                                    collection="cover"
                                    shape="cover"
                                    placeholder="Upload Cover Photo"
                                    @uploaded="handleCoverUploaded"
                                />
                                <p class="media-hint">
                                    Recommended size: 1200x400 pixels. This appears at the top of your booking site.
                                </p>
                            </div>
                        </ConsoleFormCard>

                        <!-- Gallery -->
                        <ConsoleFormCard title="Gallery">
                            <p class="section-description">
                                Showcase your work with up to 6 images and 3 videos. Drag to reorder.
                            </p>
                            <GalleryUpload
                                v-model="galleryMedia"
                                :uploadUrl="resolveUrl(provider.media.uploadMultiple.url())"
                                :videoAddUrl="resolveUrl(provider.videos.provider.add.url())"
                                collection="gallery"
                                :maxFiles="6"
                                :maxVideos="3"
                                :showVideos="true"
                                @uploaded="handleGalleryUpdated"
                                @deleted="handleGalleryUpdated"
                            />
                        </ConsoleFormCard>

                        <!-- Brand Colors -->
                        <ConsoleFormCard title="Brand Colors">
                            <template #header-actions>
                                <Button
                                    label="Reset"
                                    icon="pi pi-refresh"
                                    text
                                    size="small"
                                    :disabled="!canAccess"
                                    @click="resetColorsToDefaults"
                                />
                            </template>

                            <p class="section-description">
                                Choose your brand colors. The system will generate a complete palette based on these.
                            </p>

                            <div class="color-section" :class="{ disabled: !canAccess }">
                                <div class="color-pickers-row">
                                    <div v-for="field in colorFields" :key="field.key" class="color-picker-field">
                                        <ColorPicker
                                            :id="field.key"
                                            :modelValue="toPickerValue((colorForm as any)[field.key])"
                                            @update:modelValue="(colorForm as any)[field.key] = fromPickerValue($event as string)"
                                            format="hex"
                                            :disabled="!canAccess"
                                            class="color-picker-circle"
                                        />
                                        <div class="color-picker-info">
                                            <label :for="field.key" class="color-picker-label">{{ field.label }}</label>
                                            <small class="color-picker-desc">{{ field.description }}</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Color Mode Toggle -->
                                <div class="color-mode-section">
                                    <label class="color-mode-label">Site Theme</label>
                                    <div class="color-mode-toggle">
                                        <button
                                            v-for="mode in colorModeOptions"
                                            :key="mode.value"
                                            type="button"
                                            class="color-mode-btn"
                                            :class="{ active: colorForm.color_mode === mode.value }"
                                            :disabled="!canAccess"
                                            @click="colorForm.color_mode = mode.value"
                                        >
                                            <i :class="mode.icon"></i>
                                            <span>{{ mode.label }}</span>
                                        </button>
                                    </div>
                                    <small class="color-mode-hint">
                                        Choose how your site appears: always light, always dark, or match visitor's system preference.
                                    </small>
                                </div>
                            </div>

                            <div v-if="colorForm.isDirty && canAccess" class="form-actions">
                                <Button
                                    label="Discard"
                                    severity="secondary"
                                    text
                                    @click="colorForm.reset()"
                                />
                                <Button
                                    label="Save Colors"
                                    :loading="colorForm.processing"
                                    @click="saveColors"
                                />
                            </div>
                        </ConsoleFormCard>

                        <!-- Template Selection -->
                        <ConsoleFormCard title="Site Template">
                            <p class="section-description">
                                Choose a template design for your booking site.
                            </p>
                            <div class="template-grid">
                                <div
                                    v-for="template in templates"
                                    :key="template.value"
                                    class="template-card"
                                    :class="{
                                        selected: selectedTemplate === template.value,
                                        unavailable: !template.is_available,
                                    }"
                                    @click="selectTemplate(template)"
                                >
                                    <div class="template-preview">
                                        <div v-if="template.thumbnail" class="template-thumb">
                                            <img :src="template.thumbnail" :alt="template.label" />
                                        </div>
                                        <div v-else class="template-thumb placeholder">
                                            <i class="pi pi-palette"></i>
                                        </div>
                                        <div v-if="selectedTemplate === template.value && templateSaving" class="template-loading">
                                            <ProgressSpinner style="width: 24px; height: 24px" strokeWidth="3" />
                                        </div>
                                    </div>
                                    <div class="template-info">
                                        <div class="template-header">
                                            <span class="template-name">{{ template.label }}</span>
                                            <Tag
                                                v-if="!template.is_available && template.required_tier"
                                                :value="template.required_tier"
                                                severity="warn"
                                                class="template-tier"
                                            />
                                            <i
                                                v-if="selectedTemplate === template.value"
                                                class="pi pi-check-circle template-check"
                                            ></i>
                                        </div>
                                        <p class="template-description">{{ template.description }}</p>
                                    </div>
                                </div>
                            </div>
                        </ConsoleFormCard>
                    </div>
                </TabPanel>

                <!-- ================================ -->
                <!-- CONTENT TAB -->
                <!-- ================================ -->
                <TabPanel value="1" header="Content">
                    <div class="tab-content">
                        <ConsoleFormCard title="Business Identity">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label for="business_name" class="form-label">Business Name *</label>
                                    <InputText
                                        id="business_name"
                                        v-model="contentForm.business_name"
                                        placeholder="Your business name"
                                        class="w-full"
                                        :class="{ 'p-invalid': contentForm.errors.business_name }"
                                        maxlength="100"
                                    />
                                    <small v-if="contentForm.errors.business_name" class="p-error">
                                        {{ contentForm.errors.business_name }}
                                    </small>
                                </div>

                                <div class="form-field">
                                    <label for="industry" class="form-label">Industry</label>
                                    <Select
                                        id="industry"
                                        v-model="contentForm.industry_id"
                                        :options="industries"
                                        optionLabel="name"
                                        optionValue="id"
                                        placeholder="Select your industry"
                                        class="w-full"
                                        :class="{ 'p-invalid': contentForm.errors.industry_id }"
                                    />
                                    <small v-if="contentForm.errors.industry_id" class="p-error">
                                        {{ contentForm.errors.industry_id }}
                                    </small>
                                </div>

                                <div class="form-field">
                                    <label for="tagline" class="form-label">Tagline</label>
                                    <InputText
                                        id="tagline"
                                        v-model="contentForm.tagline"
                                        placeholder="A short phrase that captures your business"
                                        class="w-full"
                                        :class="{ 'p-invalid': contentForm.errors.tagline }"
                                        maxlength="150"
                                    />
                                    <div class="field-footer">
                                        <small v-if="contentForm.errors.tagline" class="p-error">
                                            {{ contentForm.errors.tagline }}
                                        </small>
                                        <small class="char-count">{{ contentForm.tagline?.length || 0 }}/150</small>
                                    </div>
                                </div>

                                <div class="form-field full-width">
                                    <label for="bio" class="form-label">About / Bio</label>
                                    <Textarea
                                        id="bio"
                                        v-model="contentForm.bio"
                                        rows="5"
                                        placeholder="Tell clients about your business, experience, and what makes you unique..."
                                        class="w-full"
                                        :class="{ 'p-invalid': contentForm.errors.bio }"
                                        maxlength="1000"
                                    />
                                    <div class="field-footer">
                                        <small v-if="contentForm.errors.bio" class="p-error">
                                            {{ contentForm.errors.bio }}
                                        </small>
                                        <small class="char-count">{{ contentForm.bio?.length || 0 }}/1000</small>
                                    </div>
                                </div>
                            </div>
                        </ConsoleFormCard>

                        <ConsoleFormCard title="Contact Information">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label for="address" class="form-label">Address</label>
                                    <InputText
                                        id="address"
                                        v-model="contentForm.address"
                                        placeholder="Your business address"
                                        class="w-full"
                                        :class="{ 'p-invalid': contentForm.errors.address }"
                                    />
                                    <small v-if="contentForm.errors.address" class="p-error">
                                        {{ contentForm.errors.address }}
                                    </small>
                                </div>

                                <div class="form-field">
                                    <label for="website" class="form-label">Website</label>
                                    <InputText
                                        id="website"
                                        v-model="contentForm.website"
                                        type="url"
                                        placeholder="https://yourwebsite.com"
                                        class="w-full"
                                        :class="{ 'p-invalid': contentForm.errors.website }"
                                    />
                                    <small v-if="contentForm.errors.website" class="p-error">
                                        {{ contentForm.errors.website }}
                                    </small>
                                </div>
                            </div>
                        </ConsoleFormCard>

                        <ConsoleFormCard title="Social Links">
                            <p class="section-description">
                                Connect your social media profiles to help clients find you online.
                            </p>
                            <div class="social-links-grid">
                                <div v-for="platform in socialPlatforms" :key="platform.key" class="social-field">
                                    <label :for="`social_${platform.key}`" class="form-label">
                                        <i :class="platform.icon"></i>
                                        {{ platform.label }}
                                    </label>
                                    <InputText
                                        :id="`social_${platform.key}`"
                                        v-model="(contentForm.social_links as any)[platform.key]"
                                        :placeholder="platform.placeholder"
                                        class="w-full"
                                        :class="{ 'p-invalid': (contentForm.errors as any)[`social_links.${platform.key}`] }"
                                    />
                                    <small v-if="(contentForm.errors as any)[`social_links.${platform.key}`]" class="p-error">
                                        {{ (contentForm.errors as any)[`social_links.${platform.key}`] }}
                                    </small>
                                </div>
                            </div>
                        </ConsoleFormCard>

                        <div v-if="contentForm.isDirty" class="form-actions sticky-actions">
                            <Button
                                label="Discard"
                                severity="secondary"
                                text
                                @click="contentForm.reset()"
                            />
                            <Button
                                label="Save Content"
                                :loading="contentForm.processing"
                                @click="saveContent"
                            />
                        </div>
                    </div>
                </TabPanel>

                <!-- ================================ -->
                <!-- DOMAIN TAB -->
                <!-- ================================ -->
                <TabPanel value="2" header="Domain">
                    <div class="tab-content">
                        <ConsoleFormCard title="Your Booking Site URL">
                            <p class="section-description">
                                Customize your booking site's subdomain. This is the URL your clients will use to book with you.
                            </p>

                            <div class="domain-editor">
                                <div class="domain-input-wrapper">
                                    <InputText
                                        v-model="domainForm.domain"
                                        placeholder="your-business"
                                        class="domain-input"
                                        :class="{
                                            'p-invalid': domainForm.errors.domain || domainAvailable === false,
                                            'is-valid': domainAvailable === true,
                                        }"
                                    />
                                    <span class="domain-suffix">.zeenconnect.com</span>
                                    <div v-if="domainChecking" class="domain-status checking">
                                        <ProgressSpinner style="width: 16px; height: 16px" strokeWidth="4" />
                                    </div>
                                    <div v-else-if="domainAvailable === true" class="domain-status available">
                                        <i class="pi pi-check-circle"></i>
                                    </div>
                                    <div v-else-if="domainAvailable === false" class="domain-status unavailable">
                                        <i class="pi pi-times-circle"></i>
                                    </div>
                                </div>

                                <div class="domain-preview">
                                    <span class="preview-label">Your URL:</span>
                                    <span class="preview-url">https://{{ domainPreview }}</span>
                                </div>

                                <small v-if="domainForm.errors.domain" class="p-error">
                                    {{ domainForm.errors.domain }}
                                </small>
                                <small v-else-if="domainAvailable === false" class="p-error">
                                    This domain is already taken. Please choose another.
                                </small>

                                <Message v-if="domainForm.isDirty && domainForm.domain !== domain" severity="warn" :closable="false" class="domain-warning">
                                    <i class="pi pi-exclamation-triangle"></i>
                                    <span>Changing your domain will break any existing links to your booking site.</span>
                                </Message>
                            </div>

                            <div class="domain-rules">
                                <h4>Domain Requirements</h4>
                                <ul>
                                    <li>3-30 characters long</li>
                                    <li>Lowercase letters, numbers, and hyphens only</li>
                                    <li>Cannot start or end with a hyphen</li>
                                </ul>
                            </div>

                            <div v-if="domainForm.isDirty" class="form-actions">
                                <Button
                                    label="Discard"
                                    severity="secondary"
                                    text
                                    @click="domainForm.reset(); domainAvailable = null"
                                />
                                <Button
                                    label="Save Domain"
                                    :loading="domainForm.processing"
                                    :disabled="domainAvailable === false || domainChecking"
                                    @click="saveDomain"
                                />
                            </div>
                        </ConsoleFormCard>
                    </div>
                </TabPanel>

                <!-- ================================ -->
                <!-- PREVIEW TAB -->
                <!-- ================================ -->
                <TabPanel value="3" header="Preview">
                    <div class="tab-content preview-tab">
                        <div class="preview-controls">
                            <div class="device-toggle">
                                <button
                                    type="button"
                                    class="device-btn"
                                    :class="{ active: previewDevice === 'mobile' }"
                                    @click="previewDevice = 'mobile'"
                                >
                                    <i class="pi pi-mobile"></i>
                                    Mobile
                                </button>
                                <button
                                    type="button"
                                    class="device-btn"
                                    :class="{ active: previewDevice === 'desktop' }"
                                    @click="previewDevice = 'desktop'"
                                >
                                    <i class="pi pi-desktop"></i>
                                    Desktop
                                </button>
                            </div>
                            <Button
                                label="Refresh"
                                icon="pi pi-refresh"
                                text
                                size="small"
                                @click="refreshPreview"
                            />
                        </div>

                        <div class="preview-container" :class="previewDevice">
                            <div class="preview-frame">
                                <iframe
                                    :key="previewKey"
                                    :src="previewUrl"
                                    class="preview-iframe"
                                    title="Site Preview"
                                />
                            </div>
                        </div>

                        <p class="preview-hint">
                            <i class="pi pi-info-circle"></i>
                            This is a live preview of your booking site. Changes you make will appear after saving.
                        </p>
                    </div>
                </TabPanel>
            </TabView>
        </div>
    </SettingsLayout>
</template>

<style scoped>
.branding-page {
    max-width: 900px;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (min-width: 640px) {
    .branding-page {
        gap: 1.5rem;
    }
}

.tier-message {
    margin: 0;
}

.tier-banner {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
    width: 100%;
}

@media (min-width: 640px) {
    .tier-banner {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
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

/* Tab Styling */
.branding-tabs :deep(.p-tabview-panels) {
    padding: 0;
    background: transparent;
}

.branding-tabs :deep(.p-tabview-nav) {
    background: transparent;
    border: none;
    gap: 0.125rem;
    flex-wrap: wrap;
}

@media (min-width: 640px) {
    .branding-tabs :deep(.p-tabview-nav) {
        gap: 0.25rem;
        flex-wrap: nowrap;
    }
}

.branding-tabs :deep(.p-tabview-nav-link) {
    background: transparent;
    border: none;
    border-radius: 0.5rem;
    padding: 0.5rem 0.625rem;
    color: var(--color-slate-600, #475569);
    font-weight: 500;
    font-size: 0.8125rem;
}

@media (min-width: 640px) {
    .branding-tabs :deep(.p-tabview-nav-link) {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
}

.branding-tabs :deep(.p-tabview-nav-link:hover) {
    background: var(--color-slate-100, #f1f5f9);
}

.branding-tabs :deep(.p-tabview-selected .p-tabview-nav-link) {
    background: white;
    color: #106B4F;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tab-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding-top: 0.75rem;
}

@media (min-width: 640px) {
    .tab-content {
        gap: 1.5rem;
        padding-top: 1rem;
    }
}

/* Media Sections */
.media-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
}

@media (min-width: 640px) {
    .media-section {
        flex-direction: row;
        gap: 1.5rem;
        align-items: flex-start;
    }
}

.media-hints {
    flex: 1;
    text-align: center;
}

@media (min-width: 640px) {
    .media-hints {
        text-align: left;
    }
}

.media-hints p {
    margin: 0 0 0.75rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.media-hints ul {
    margin: 0;
    padding-left: 1.25rem;
    text-align: left;
    display: inline-block;
}

.media-hints li {
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.6;
}

.cover-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.media-hint {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
}

.section-description {
    margin: 0 0 1.25rem;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.5;
}

/* Color Section */
.color-section {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.color-section.disabled {
    opacity: 0.6;
    pointer-events: none;
}

.color-pickers-row {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

@media (min-width: 640px) {
    .color-pickers-row {
        flex-direction: row;
        gap: 3rem;
    }
}

.color-picker-field {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Make color picker circular and larger */
.color-picker-circle {
    flex-shrink: 0;
}

.color-picker-circle :deep(.p-colorpicker-preview) {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: 3px solid var(--color-slate-200, #e2e8f0);
    cursor: pointer;
    transition: all 0.15s ease;
}

.color-picker-circle :deep(.p-colorpicker-preview:hover) {
    border-color: var(--color-slate-300, #cbd5e1);
    transform: scale(1.05);
}

.color-picker-circle :deep(.p-colorpicker-preview:focus) {
    outline: none;
    border-color: #106B4F;
    box-shadow: 0 0 0 4px rgba(16, 107, 79, 0.15);
}

.color-picker-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.color-picker-label {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-slate-800, #1e293b);
}

.color-picker-desc {
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.4;
}

/* Color Mode Toggle */
.color-mode-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--color-slate-100, #f1f5f9);
}

.color-mode-label {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-slate-800, #1e293b);
}

.color-mode-toggle {
    display: flex;
    gap: 0.25rem;
    background: var(--color-slate-100, #f1f5f9);
    padding: 0.25rem;
    border-radius: 0.5rem;
    width: fit-content;
}

.color-mode-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.color-mode-btn:hover:not(:disabled) {
    color: var(--color-slate-900, #0f172a);
}

.color-mode-btn.active {
    background: white;
    color: var(--color-slate-900, #0f172a);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.color-mode-btn:disabled {
    cursor: not-allowed;
}

.color-mode-btn i {
    font-size: 1rem;
}

.color-mode-hint {
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.4;
}

/* Template Grid */
.template-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 640px) {
    .template-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 768px) {
    .template-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.template-card {
    border: 2px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.15s ease;
}

.template-card:hover:not(.unavailable) {
    border-color: var(--color-slate-300, #cbd5e1);
}

.template-card.selected {
    border-color: #106B4F;
}

.template-card.unavailable {
    opacity: 0.6;
    cursor: not-allowed;
}

.template-preview {
    position: relative;
    aspect-ratio: 16/10;
    background: var(--color-slate-100, #f1f5f9);
}

.template-thumb {
    width: 100%;
    height: 100%;
}

.template-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.template-thumb.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-slate-400, #94a3b8);
}

.template-thumb.placeholder i {
    font-size: 2rem;
}

.template-loading {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.8);
}

.template-info {
    padding: 0.75rem;
}

.template-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.template-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.template-tier {
    font-size: 0.625rem;
}

.template-check {
    margin-left: auto;
    color: #106B4F;
}

.template-description {
    margin: 0;
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.4;
}

/* Form Grid */
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

.field-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.char-count {
    font-size: 0.75rem;
    color: var(--color-slate-400, #94a3b8);
    margin-left: auto;
}

.w-full {
    width: 100%;
}

/* Domain Editor */
.domain-editor {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.domain-input-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0;
    background: white;
    border: 1px solid var(--color-slate-300, #cbd5e1);
    border-radius: 0.5rem;
    overflow: hidden;
}

@media (min-width: 640px) {
    .domain-input-wrapper {
        flex-direction: row;
        align-items: center;
    }
}

.domain-input {
    flex: 1;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}

.domain-input.is-valid {
    background: rgba(34, 197, 94, 0.05);
}

.domain-suffix {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    background: var(--color-slate-50, #f8fafc);
    border-top: 1px solid var(--color-slate-200, #e2e8f0);
    text-align: center;
}

@media (min-width: 640px) {
    .domain-suffix {
        padding: 0 1rem;
        font-size: 0.875rem;
        border-top: none;
        border-left: 1px solid var(--color-slate-200, #e2e8f0);
        line-height: 2.5rem;
        white-space: nowrap;
        text-align: left;
    }
}

.domain-status {
    padding: 0 0.75rem;
    display: flex;
    align-items: center;
}

.domain-status.available {
    color: #22c55e;
}

.domain-status.unavailable {
    color: #ef4444;
}

.domain-preview {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.preview-label {
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
}

.preview-url {
    font-size: 0.875rem;
    font-family: ui-monospace, monospace;
    color: var(--color-slate-700, #334155);
}

.domain-warning {
    margin: 0;
}

.domain-warning span {
    margin-left: 0.5rem;
}

.domain-rules {
    margin-top: 0.5rem;
}

.domain-rules h4 {
    margin: 0 0 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-slate-700, #334155);
}

.domain-rules ul {
    margin: 0;
    padding-left: 1.25rem;
}

.domain-rules li {
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.6;
}

/* Preview Tab */
.preview-tab {
    gap: 1rem;
}

.preview-controls {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.75rem;
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
}

@media (min-width: 640px) {
    .preview-controls {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
    }
}

.device-toggle {
    display: flex;
    gap: 0.25rem;
    background: var(--color-slate-100, #f1f5f9);
    padding: 0.25rem;
    border-radius: 0.5rem;
    justify-content: center;
}

.device-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
    flex: 1;
}

@media (min-width: 640px) {
    .device-btn {
        flex: none;
    }
}

.device-btn:hover {
    color: var(--color-slate-900, #0f172a);
}

.device-btn.active {
    background: white;
    color: var(--color-slate-900, #0f172a);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.preview-container {
    display: flex;
    justify-content: center;
    padding: 1rem;
    background: var(--color-slate-100, #f1f5f9);
    border-radius: 0.75rem;
    min-height: 400px;
    overflow-x: auto;
}

@media (min-width: 640px) {
    .preview-container {
        padding: 1.5rem;
        min-height: 500px;
    }
}

.preview-frame {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.preview-container.mobile .preview-frame {
    width: 320px;
    height: 568px;
}

@media (min-width: 640px) {
    .preview-container.mobile .preview-frame {
        width: 375px;
        height: 667px;
    }
}

.preview-container.desktop .preview-frame {
    width: 100%;
    max-width: 1024px;
    height: 500px;
}

@media (min-width: 640px) {
    .preview-container.desktop .preview-frame {
        height: 600px;
    }
}

.preview-iframe {
    width: 100%;
    height: 100%;
    border: none;
}

.preview-hint {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
}

.preview-hint i {
    color: var(--color-slate-400, #94a3b8);
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-slate-100, #f1f5f9);
}

.form-actions.sticky-actions {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 0.75rem 1rem;
    margin: 1rem -0.75rem -0.75rem;
    border-radius: 0 0 0.75rem 0.75rem;
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
    flex-wrap: wrap;
}

@media (min-width: 640px) {
    .form-actions.sticky-actions {
        padding: 1rem 1.5rem;
        margin: 1.5rem -1.5rem -1.5rem;
        flex-wrap: nowrap;
    }
}

/* Social Links Grid */
.social-links-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.social-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.social-field .form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.social-field .form-label i {
    font-size: 1rem;
    color: var(--color-slate-500, #64748b);
}
</style>

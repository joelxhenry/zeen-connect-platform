<script setup lang="ts">
import { computed, ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
} from '@/components/console';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface Template {
    value: string;
    label: string;
    description: string;
    preview_image: string;
    required_tier: string;
    required_tier_label: string;
    is_available: boolean;
    is_selected: boolean;
}

interface SiteFeature {
    icon: string;
    title: string;
    description: string;
}

interface Props {
    templates: Template[];
    currentTemplate: string;
    currentTier: string;
    currentTierLabel: string;
    siteUrl: string;
    siteFeatures: SiteFeature[];
}

const props = defineProps<Props>();
const toast = useToast();

// Available icons for features
const iconOptions = [
    { label: 'Star', value: 'pi pi-star', icon: 'pi pi-star' },
    { label: 'Users', value: 'pi pi-users', icon: 'pi pi-users' },
    { label: 'Clock', value: 'pi pi-clock', icon: 'pi pi-clock' },
    { label: 'Check Circle', value: 'pi pi-check-circle', icon: 'pi pi-check-circle' },
    { label: 'Heart', value: 'pi pi-heart', icon: 'pi pi-heart' },
    { label: 'Shield', value: 'pi pi-shield', icon: 'pi pi-shield' },
    { label: 'Bolt', value: 'pi pi-bolt', icon: 'pi pi-bolt' },
    { label: 'Gift', value: 'pi pi-gift', icon: 'pi pi-gift' },
    { label: 'Trophy', value: 'pi pi-trophy', icon: 'pi pi-trophy' },
    { label: 'Thumbs Up', value: 'pi pi-thumbs-up', icon: 'pi pi-thumbs-up' },
    { label: 'Verified', value: 'pi pi-verified', icon: 'pi pi-verified' },
    { label: 'Sparkles', value: 'pi pi-sparkles', icon: 'pi pi-sparkles' },
];

// Ensure we always have 4 feature slots
const ensureFourFeatures = (features: SiteFeature[] | null | undefined): SiteFeature[] => {
    const result = [...(features || [])];
    while (result.length < 4) {
        result.push({ icon: 'pi pi-star', title: '', description: '' });
    }
    return result.slice(0, 4);
};

// Form state
const selectedTemplate = ref(props.currentTemplate);
const siteFeatures = reactive<SiteFeature[]>(ensureFourFeatures(props.siteFeatures));
const isProcessing = ref(false);

// Check if the selected template uses features
const templateUsesFeatures = computed(() => {
    return selectedTemplate.value === 'barber_delux';
});

const hasChanges = computed(() => {
    const templateChanged = selectedTemplate.value !== props.currentTemplate;
    const featuresChanged = JSON.stringify(siteFeatures) !== JSON.stringify(ensureFourFeatures(props.siteFeatures));
    return templateChanged || featuresChanged;
});

const selectTemplateCard = (template: Template) => {
    if (!template.is_available) return;
    selectedTemplate.value = template.value;
};

const submit = () => {
    isProcessing.value = true;

    router.put(resolveUrl(provider.site.template.update().url), {
        template: selectedTemplate.value,
        site_features: siteFeatures,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Site template updated successfully',
                life: 3000,
            });
            isProcessing.value = false;
        },
        onError: (errors: Record<string, string>) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.template || 'Failed to update site template',
                life: 5000,
            });
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};

const goToSubscription = () => {
    router.visit(resolveUrl(provider.subscription.index().url));
};

const previewSite = () => {
    window.open(props.siteUrl, '_blank');
};

const getTierSeverity = (tier: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast' | undefined => {
    switch (tier) {
        case 'premium': return 'info';
        case 'enterprise': return 'success';
        default: return 'secondary';
    }
};
</script>

<template>
    <ConsoleLayout title="Site Template">
        <div class="w-full max-w-5xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Site Template"
                subtitle="Choose a visual template for your public storefront and customize site content."
            >
                <template #actions>
                    <Button
                        label="Preview Site"
                        icon="pi pi-external-link"
                        severity="secondary"
                        outlined
                        @click="previewSite"
                    />
                </template>
            </ConsolePageHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <ConsoleFormCard title="Available Templates" icon="pi pi-palette">
                    <p class="text-sm text-gray-500 m-0 mb-6">
                        Select a template that best represents your brand. Different templates are available based on your subscription tier.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div
                            v-for="template in templates"
                            :key="template.value"
                            class="template-card"
                            :class="{
                                'template-card--selected': selectedTemplate === template.value,
                                'template-card--unavailable': !template.is_available,
                            }"
                            @click="selectTemplateCard(template)"
                        >
                            <!-- Template Preview Image -->
                            <div class="template-preview">
                                <div class="template-preview-placeholder">
                                    <i class="pi pi-image text-4xl text-gray-300"></i>
                                    <span class="text-sm text-gray-400 mt-2">{{ template.label }}</span>
                                </div>
                                <!-- Selection Indicator -->
                                <div v-if="selectedTemplate === template.value" class="template-selected-badge">
                                    <i class="pi pi-check"></i>
                                </div>
                            </div>

                            <!-- Template Info -->
                            <div class="template-info">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-base font-semibold text-gray-900 m-0">
                                        {{ template.label }}
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        <Tag
                                            v-if="template.required_tier !== 'starter'"
                                            :value="template.required_tier_label"
                                            :severity="getTierSeverity(template.required_tier)"
                                            class="text-xs"
                                        />
                                        <Tag
                                            v-if="template.is_selected"
                                            value="Current"
                                            severity="success"
                                            class="text-xs"
                                        />
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500 m-0">
                                    {{ template.description }}
                                </p>

                                <!-- Upgrade Prompt -->
                                <div v-if="!template.is_available" class="mt-3 pt-3 border-t border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">
                                            Requires {{ template.required_tier_label }} plan
                                        </span>
                                        <Button
                                            label="Upgrade"
                                            icon="pi pi-arrow-up"
                                            size="small"
                                            text
                                            @click.stop="goToSubscription"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Site Features Section (shown for templates that support it) -->
                <ConsoleFormCard
                    v-if="templateUsesFeatures"
                    title="Site Features"
                    icon="pi pi-star"
                >
                    <p class="text-sm text-gray-500 m-0 mb-6">
                        Highlight what makes your business special. These features are displayed prominently on your homepage.
                    </p>

                    <div class="space-y-4">
                        <div
                            v-for="(feature, index) in siteFeatures"
                            :key="index"
                            class="feature-item"
                        >
                            <div class="feature-header">
                                <span class="feature-number">Feature {{ index + 1 }}</span>
                            </div>
                            <div class="feature-fields">
                                <div class="feature-icon-field">
                                    <label class="field-label">Icon</label>
                                    <Select
                                        v-model="feature.icon"
                                        :options="iconOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Select icon"
                                        class="w-full"
                                    >
                                        <template #value="slotProps">
                                            <div v-if="slotProps.value" class="flex items-center gap-2">
                                                <i :class="slotProps.value"></i>
                                                <span>{{ iconOptions.find(o => o.value === slotProps.value)?.label }}</span>
                                            </div>
                                            <span v-else>Select icon</span>
                                        </template>
                                        <template #option="slotProps">
                                            <div class="flex items-center gap-2">
                                                <i :class="slotProps.option.icon"></i>
                                                <span>{{ slotProps.option.label }}</span>
                                            </div>
                                        </template>
                                    </Select>
                                </div>
                                <div class="feature-title-field">
                                    <label class="field-label">Title</label>
                                    <InputText
                                        v-model="feature.title"
                                        placeholder="e.g., Quality Service"
                                        class="w-full"
                                        maxlength="100"
                                    />
                                </div>
                                <div class="feature-desc-field">
                                    <label class="field-label">Description</label>
                                    <Textarea
                                        v-model="feature.description"
                                        placeholder="e.g., We pride ourselves on delivering exceptional results every time."
                                        rows="2"
                                        class="w-full"
                                        maxlength="255"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <Message severity="info" :closable="false" class="mt-4">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-info-circle"></i>
                            <span>Leave a feature empty to hide it on your site. Only features with both title and description will be displayed.</span>
                        </div>
                    </Message>
                </ConsoleFormCard>

                <!-- Info Message -->
                <Message severity="info" :closable="false">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-info-circle"></i>
                        <span>Changing your template will update the look of your public storefront immediately. Your content and settings will remain the same.</span>
                    </div>
                </Message>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3">
                    <Button
                        label="Save Changes"
                        icon="pi pi-check"
                        type="submit"
                        :loading="isProcessing"
                        :disabled="!hasChanges"
                    />
                </div>
            </form>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.template-card {
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.template-card:hover:not(.template-card--unavailable) {
    border-color: #9ca3af;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.template-card--selected {
    border-color: var(--p-primary-color, #106B4F) !important;
    box-shadow: 0 0 0 1px var(--p-primary-color, #106B4F);
}

.template-card--unavailable {
    opacity: 0.6;
    cursor: not-allowed;
}

.template-preview {
    position: relative;
    height: 160px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.template-preview-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.template-selected-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 1.5rem;
    height: 1.5rem;
    background: var(--p-primary-color, #106B4F);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

.template-info {
    padding: 1rem;
}

/* Feature items */
.feature-item {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
}

.feature-header {
    margin-bottom: 0.75rem;
}

.feature-number {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.feature-fields {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 0.75rem;
}

.feature-icon-field {
    grid-column: 1;
}

.feature-title-field {
    grid-column: 2;
}

.feature-desc-field {
    grid-column: 1 / -1;
}

.field-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.375rem;
}

@media (max-width: 640px) {
    .feature-fields {
        grid-template-columns: 1fr;
    }

    .feature-icon-field,
    .feature-title-field,
    .feature-desc-field {
        grid-column: 1;
    }
}
</style>

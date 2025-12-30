<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import { ConsolePageHeader } from '@/components/console';
import Button from 'primevue/button';
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

interface Props {
    templates: Template[];
    currentTemplate: string;
    currentTier: string;
    currentTierLabel: string;
    siteUrl: string;
}

const props = defineProps<Props>();
const toast = useToast();

// Form state
const selectedTemplate = ref(props.currentTemplate);
const isProcessing = ref(false);

const hasChanges = computed(() => {
    return selectedTemplate.value !== props.currentTemplate;
});

const selectTemplateCard = (template: Template) => {
    if (!template.is_available) return;
    selectedTemplate.value = template.value;
};

const submit = () => {
    isProcessing.value = true;

    router.put(resolveUrl(provider.site.template.update().url), {
        template: selectedTemplate.value,
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

// Preview gradient colors for templates without images
const getPreviewGradient = (templateValue: string): string => {
    const gradients: Record<string, string> = {
        default: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        minimalist: 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)',
        barber_delux: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)',
        architect_bold: 'linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%)',
        grand_horizon: 'linear-gradient(135deg, #b8860b 0%, #8b6914 100%)',
    };
    return gradients[templateValue] || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
};
</script>

<template>
    <ConsoleLayout title="Site Template">
        <div class="template-page">
            <!-- Sticky Page Header with Save Button -->
            <ConsolePageHeader
                title="Site Template"
                subtitle="Choose the look for your public storefront"
                :sticky="true"
            >
                <template #actions>
                    <Button
                        label="Save Changes"
                        icon="pi pi-check"
                        :loading="isProcessing"
                        :disabled="!hasChanges"
                        @click="submit"
                    />
                    <Button
                        label="Preview Site"
                        icon="pi pi-external-link"
                        severity="secondary"
                        outlined
                        @click="previewSite"
                    />
                </template>
            </ConsolePageHeader>

            <!-- Template Grid -->
            <div class="template-grid">
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
                    <!-- Large Preview Area -->
                    <div
                        class="template-preview"
                        :style="{ background: getPreviewGradient(template.value) }"
                    >
                        <img
                            v-if="template.preview_image"
                            :src="template.preview_image"
                            :alt="template.label"
                            class="template-preview-image"
                        />
                        <div v-else class="template-preview-placeholder">
                            <span class="template-name-overlay">{{ template.label }}</span>
                        </div>

                        <!-- Floating Tier Badge -->
                        <Tag
                            v-if="template.required_tier !== 'starter'"
                            :value="template.required_tier_label"
                            :severity="getTierSeverity(template.required_tier)"
                            class="tier-badge"
                        />

                        <!-- Selected Indicator -->
                        <div v-if="selectedTemplate === template.value" class="selected-indicator">
                            <i class="pi pi-check"></i>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="template-footer">
                        <div class="template-header">
                            <h3 class="template-title">{{ template.label }}</h3>
                            <Tag
                                v-if="template.is_selected"
                                value="Current"
                                severity="success"
                                class="current-tag"
                            />
                        </div>
                        <p class="template-description">{{ template.description }}</p>

                        <!-- Upgrade Prompt for Unavailable Templates -->
                        <div v-if="!template.is_available" class="upgrade-prompt">
                            <span class="upgrade-text">
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
    </ConsoleLayout>
</template>

<style scoped>
.template-page {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

/* 3-column responsive grid */
.template-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    padding: 0 0.5rem;
}

@media (max-width: 1024px) {
    .template-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .template-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

/* Modern card styling */
.template-card {
    background: white;
    border-radius: 1rem;
    overflow: hidden;
    border: 2px solid transparent;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
}

.template-card:hover:not(.template-card--unavailable) {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
}

.template-card--selected {
    border-color: var(--p-primary-color, #106B4F);
    box-shadow: 0 0 0 4px rgba(16, 107, 79, 0.1);
}

.template-card--selected:hover {
    box-shadow: 0 0 0 4px rgba(16, 107, 79, 0.1), 0 12px 24px -8px rgba(0, 0, 0, 0.15);
}

.template-card--unavailable {
    opacity: 0.65;
    cursor: not-allowed;
}

.template-card--unavailable:hover {
    transform: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Larger preview area */
.template-preview {
    position: relative;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.template-preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.template-preview-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.template-name-overlay {
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    letter-spacing: 0.05em;
}

/* Floating tier badge */
.tier-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Floating selected checkmark */
.selected-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 2rem;
    height: 2rem;
    background: var(--p-primary-color, #106B4F);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    animation: scaleIn 0.2s ease;
}

@keyframes scaleIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Card footer */
.template-footer {
    padding: 1.25rem;
}

.template-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.template-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
}

.current-tag {
    font-size: 0.6875rem;
    flex-shrink: 0;
}

.template-description {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
}

/* Upgrade prompt */
.upgrade-prompt {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.upgrade-text {
    font-size: 0.75rem;
    color: #6b7280;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .template-preview {
        height: 180px;
    }

    .template-name-overlay {
        font-size: 1.25rem;
    }

    .template-footer {
        padding: 1rem;
    }

    .template-title {
        font-size: 1rem;
    }
}
</style>

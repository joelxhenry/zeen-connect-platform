<script setup lang="ts">
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
} from '@/components/console';
import Button from 'primevue/button';
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

interface Props {
    templates: Template[];
    currentTemplate: string;
    currentTier: string;
    currentTierLabel: string;
    siteUrl: string;
}

const props = defineProps<Props>();
const toast = useToast();

const form = useForm({
    template: props.currentTemplate,
});

const selectedTemplate = computed(() => {
    return props.templates.find(t => t.value === form.template) || props.templates[0];
});

const hasChanges = computed(() => {
    return form.template !== props.currentTemplate;
});

const selectTemplate = (template: Template) => {
    if (!template.is_available) return;
    form.template = template.value;
};

const submit = () => {
    form.put(resolveUrl(provider.site.template.update().url), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Site template updated successfully',
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors.template || 'Failed to update site template',
                life: 5000,
            });
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
                subtitle="Choose a visual template for your public storefront."
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
                                'template-card--selected': form.template === template.value,
                                'template-card--unavailable': !template.is_available,
                            }"
                            @click="selectTemplate(template)"
                        >
                            <!-- Template Preview Image -->
                            <div class="template-preview">
                                <div class="template-preview-placeholder">
                                    <i class="pi pi-image text-4xl text-gray-300"></i>
                                    <span class="text-sm text-gray-400 mt-2">{{ template.label }}</span>
                                </div>
                                <!-- Selection Indicator -->
                                <div v-if="form.template === template.value" class="template-selected-badge">
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
                        label="Save Template"
                        icon="pi pi-check"
                        type="submit"
                        :loading="form.processing"
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
</style>

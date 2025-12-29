<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleButton,
} from '@/components/console';
import ColorPicker from 'primevue/colorpicker';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface BrandSettings {
    primary_color: string | null;
    text_color: string | null;
    success_color: string | null;
    warning_color: string | null;
    danger_color: string | null;
    info_color: string | null;
    secondary_color: string | null;
}

interface DefaultColors {
    primary_color: string;
    text_color: string;
    success_color: string;
    warning_color: string;
    danger_color: string;
    info_color: string;
    secondary_color: string;
}

interface Props {
    canAccess: boolean;
    currentTier: string;
    currentTierLabel: string;
    brandSettings: BrandSettings;
    defaultColors: DefaultColors;
}

const props = defineProps<Props>();
const toast = useToast();

// Convert hex to color picker format (without #)
const hexToColorPicker = (hex: string | null): string => {
    if (!hex) return '';
    return hex.replace('#', '');
};

// Convert color picker format back to hex
const colorPickerToHex = (color: string): string | null => {
    if (!color) return null;
    return `#${color}`;
};

// Local color values for the color pickers (without #)
const primaryColorValue = ref(hexToColorPicker(props.brandSettings.primary_color || props.defaultColors.primary_color));
const textColorValue = ref(hexToColorPicker(props.brandSettings.text_color || props.defaultColors.text_color));
const successColorValue = ref(hexToColorPicker(props.brandSettings.success_color || props.defaultColors.success_color));
const warningColorValue = ref(hexToColorPicker(props.brandSettings.warning_color || props.defaultColors.warning_color));
const dangerColorValue = ref(hexToColorPicker(props.brandSettings.danger_color || props.defaultColors.danger_color));
const infoColorValue = ref(hexToColorPicker(props.brandSettings.info_color || props.defaultColors.info_color));
const secondaryColorValue = ref(hexToColorPicker(props.brandSettings.secondary_color || props.defaultColors.secondary_color));

// Form for submission
const form = useForm({
    primary_color: props.brandSettings.primary_color,
    text_color: props.brandSettings.text_color,
    success_color: props.brandSettings.success_color,
    warning_color: props.brandSettings.warning_color,
    danger_color: props.brandSettings.danger_color,
    info_color: props.brandSettings.info_color,
    secondary_color: props.brandSettings.secondary_color,
});

// Sync color picker values to form
watch(primaryColorValue, (val) => { form.primary_color = colorPickerToHex(val); });
watch(textColorValue, (val) => { form.text_color = colorPickerToHex(val); });
watch(successColorValue, (val) => { form.success_color = colorPickerToHex(val); });
watch(warningColorValue, (val) => { form.warning_color = colorPickerToHex(val); });
watch(dangerColorValue, (val) => { form.danger_color = colorPickerToHex(val); });
watch(infoColorValue, (val) => { form.info_color = colorPickerToHex(val); });
watch(secondaryColorValue, (val) => { form.secondary_color = colorPickerToHex(val); });

// Helper to calculate hover color (darken by 15%)
const darkenColor = (hex: string, percent: number): string => {
    const cleanHex = hex.replace('#', '');
    const r = Math.max(0, Math.floor(parseInt(cleanHex.substring(0, 2), 16) * (100 - percent) / 100));
    const g = Math.max(0, Math.floor(parseInt(cleanHex.substring(2, 4), 16) * (100 - percent) / 100));
    const b = Math.max(0, Math.floor(parseInt(cleanHex.substring(4, 6), 16) * (100 - percent) / 100));
    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
};

// Helper to convert hex to RGB for opacity support
const hexToRgb = (hex: string): string => {
    const cleanHex = hex.replace('#', '');
    const r = parseInt(cleanHex.substring(0, 2), 16);
    const g = parseInt(cleanHex.substring(2, 4), 16);
    const b = parseInt(cleanHex.substring(4, 6), 16);
    return `${r}, ${g}, ${b}`;
};

// Computed preview styles
const previewStyles = computed(() => {
    const primary = form.primary_color || props.defaultColors.primary_color;
    const text = form.text_color || props.defaultColors.text_color;
    const success = form.success_color || props.defaultColors.success_color;
    const warning = form.warning_color || props.defaultColors.warning_color;
    const danger = form.danger_color || props.defaultColors.danger_color;
    const info = form.info_color || props.defaultColors.info_color;
    const secondary = form.secondary_color || props.defaultColors.secondary_color;

    return {
        '--preview-primary': primary,
        '--preview-primary-rgb': hexToRgb(primary),
        '--preview-hover': darkenColor(primary, 15),
        '--preview-text': text,
        '--preview-success': success,
        '--preview-warning': warning,
        '--preview-danger': danger,
        '--preview-info': info,
        '--preview-secondary': secondary,
    };
});

// Check if colors have been modified from saved state
const hasChanges = computed(() => {
    return form.primary_color !== props.brandSettings.primary_color ||
           form.text_color !== props.brandSettings.text_color ||
           form.success_color !== props.brandSettings.success_color ||
           form.warning_color !== props.brandSettings.warning_color ||
           form.danger_color !== props.brandSettings.danger_color ||
           form.info_color !== props.brandSettings.info_color ||
           form.secondary_color !== props.brandSettings.secondary_color;
});

// Reset to defaults
const resetToDefaults = () => {
    primaryColorValue.value = hexToColorPicker(props.defaultColors.primary_color);
    textColorValue.value = hexToColorPicker(props.defaultColors.text_color);
    successColorValue.value = hexToColorPicker(props.defaultColors.success_color);
    warningColorValue.value = hexToColorPicker(props.defaultColors.warning_color);
    dangerColorValue.value = hexToColorPicker(props.defaultColors.danger_color);
    infoColorValue.value = hexToColorPicker(props.defaultColors.info_color);
    secondaryColorValue.value = hexToColorPicker(props.defaultColors.secondary_color);
    form.primary_color = null;
    form.text_color = null;
    form.success_color = null;
    form.warning_color = null;
    form.danger_color = null;
    form.info_color = null;
    form.secondary_color = null;
};

// Submit form
const submit = () => {
    form.put(resolveUrl(provider.branding.update().url), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Branding settings updated successfully',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update branding settings',
                life: 3000,
            });
        },
    });
};

// Navigate to subscription page
const goToSubscription = () => {
    router.visit(resolveUrl(provider.subscription.index().url));
};

// Color configuration for cleaner template
const colorFields = [
    { key: 'primary', label: 'Primary Color', description: 'Used for buttons, links, and accent elements', ref: primaryColorValue, formKey: 'primary_color' as const },
    { key: 'text', label: 'Text Color', description: 'Used for headings and important text', ref: textColorValue, formKey: 'text_color' as const },
    { key: 'success', label: 'Success Color', description: 'Used for success messages and confirmations', ref: successColorValue, formKey: 'success_color' as const },
    { key: 'warning', label: 'Warning Color', description: 'Used for warning messages and alerts', ref: warningColorValue, formKey: 'warning_color' as const },
    { key: 'danger', label: 'Danger/Error Color', description: 'Used for error messages and destructive actions', ref: dangerColorValue, formKey: 'danger_color' as const },
    { key: 'info', label: 'Info Color', description: 'Used for informational messages', ref: infoColorValue, formKey: 'info_color' as const },
    { key: 'secondary', label: 'Secondary Color', description: 'Used for secondary buttons and muted elements', ref: secondaryColorValue, formKey: 'secondary_color' as const },
];
</script>

<template>
    <ConsoleLayout title="Branding">
        <div class="w-full max-w-5xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Branding"
                subtitle="Customize your public storefront colors to match your brand identity."
            />

            <!-- Upgrade Banner for Starter Tier -->
            <Message v-if="!canAccess" severity="warn" :closable="false" class="mb-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-medium m-0">Custom branding requires Premium or Enterprise</p>
                        <p class="text-sm m-0 mt-1 opacity-80">
                            Upgrade your plan to customize your storefront colors and create a unique brand experience for your clients.
                        </p>
                    </div>
                    <Button
                        label="Upgrade"
                        icon="pi pi-arrow-up"
                        size="small"
                        @click="goToSubscription"
                    />
                </div>
            </Message>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Color Settings Card -->
                    <div class="lg:col-span-2">
                        <ConsoleFormCard title="Brand Colors" icon="pi pi-palette">
                            <div class="space-y-4">
                                <p class="text-sm text-gray-500 m-0 mb-4">
                                    Choose colors that represent your brand. These will be applied to your public storefront.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div v-for="field in colorFields" :key="field.key" class="color-field">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ field.label }}
                                        </label>
                                        <div class="flex items-center gap-3">
                                            <ColorPicker
                                                v-model="field.ref.value"
                                                :disabled="!canAccess"
                                                format="hex"
                                            />
                                            <InputText
                                                :modelValue="form[field.formKey] || defaultColors[field.formKey]"
                                                readonly
                                                class="w-24 font-mono text-xs"
                                                :disabled="!canAccess"
                                            />
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1 m-0">
                                            {{ field.description }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Reset Button -->
                                <div class="pt-4 border-t border-gray-200">
                                    <Button
                                        label="Reset to Defaults"
                                        icon="pi pi-refresh"
                                        severity="secondary"
                                        text
                                        size="small"
                                        :disabled="!canAccess"
                                        @click="resetToDefaults"
                                    />
                                </div>
                            </div>
                        </ConsoleFormCard>
                    </div>

                    <!-- Preview Card -->
                    <div class="lg:col-span-1">
                        <ConsoleFormCard title="Preview" icon="pi pi-eye">
                            <div class="space-y-4">
                                <p class="text-sm text-gray-500 m-0">
                                    See how your colors will look.
                                </p>

                                <!-- Preview Container -->
                                <div
                                    class="preview-container rounded-lg border border-gray-200 p-4 bg-white"
                                    :style="previewStyles"
                                >
                                    <!-- Sample Header -->
                                    <h3 class="preview-heading text-base font-semibold mb-3">
                                        Your Business
                                    </h3>

                                    <!-- Sample Button -->
                                    <button type="button" class="preview-button w-full px-3 py-2 rounded-lg text-white text-sm font-medium mb-3">
                                        Book Now
                                    </button>

                                    <!-- Sample Status Badges -->
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span class="preview-badge preview-badge--success text-xs px-2 py-0.5 rounded">
                                                Confirmed
                                            </span>
                                            <span class="text-xs text-gray-500">Success</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="preview-badge preview-badge--warning text-xs px-2 py-0.5 rounded">
                                                Pending
                                            </span>
                                            <span class="text-xs text-gray-500">Warning</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="preview-badge preview-badge--danger text-xs px-2 py-0.5 rounded">
                                                Cancelled
                                            </span>
                                            <span class="text-xs text-gray-500">Danger</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="preview-badge preview-badge--info text-xs px-2 py-0.5 rounded">
                                                Info
                                            </span>
                                            <span class="text-xs text-gray-500">Info</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="preview-badge preview-badge--secondary text-xs px-2 py-0.5 rounded">
                                                Draft
                                            </span>
                                            <span class="text-xs text-gray-500">Secondary</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </ConsoleFormCard>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3">
                    <ConsoleButton
                        label="Save Changes"
                        icon="pi pi-check"
                        type="submit"
                        :loading="form.processing"
                        :disabled="!canAccess || !hasChanges"
                    />
                </div>
            </form>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.preview-container {
    --preview-primary: #106B4F;
    --preview-primary-rgb: 16, 107, 79;
    --preview-hover: #0D5A42;
    --preview-text: #0D1F1B;
    --preview-success: #22C55E;
    --preview-warning: #F59E0B;
    --preview-danger: #EF4444;
    --preview-info: #3B82F6;
    --preview-secondary: #6B7280;
}

.preview-heading {
    color: var(--preview-text);
}

.preview-button {
    background-color: var(--preview-primary);
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.preview-button:hover {
    background-color: var(--preview-hover);
}

.preview-badge--success {
    background-color: var(--preview-success);
    color: white;
}

.preview-badge--warning {
    background-color: var(--preview-warning);
    color: white;
}

.preview-badge--danger {
    background-color: var(--preview-danger);
    color: white;
}

.preview-badge--info {
    background-color: var(--preview-info);
    color: white;
}

.preview-badge--secondary {
    background-color: var(--preview-secondary);
    color: white;
}

.color-field {
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 0.5rem;
}
</style>

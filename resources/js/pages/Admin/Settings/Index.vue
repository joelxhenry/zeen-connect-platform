<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Panel from 'primevue/panel';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import SettingsController from '@/actions/App/Domains/Admin/Controllers/SettingsController';

interface SettingMeta {
    label: string;
    type: 'percentage' | 'currency';
    min: number;
    max?: number;
    step?: number;
    description: string;
    value: number;
    key: string;
    default: number;
}

interface CategoryData {
    label: string;
    description: string;
    icon: string;
    settings: Record<string, SettingMeta>;
}

interface Props {
    settings: Record<string, CategoryData>;
}

const props = defineProps<Props>();
const toast = useToast();

// Build initial form data from all settings
const buildFormData = (): Record<string, number> => {
    const data: Record<string, number> = {};
    for (const category of Object.values(props.settings)) {
        for (const [key, setting] of Object.entries(category.settings)) {
            data[key] = setting.value;
        }
    }
    return data;
};

const form = useForm(buildFormData());

const submit = () => {
    form.put(SettingsController.update().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'System settings updated successfully',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update settings. Please check the form.',
                life: 5000,
            });
        },
    });
};
</script>

<template>
    <AdminLayout title="System Settings">
        <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-800 m-0">System Settings</h1>
                <p class="text-gray-500 mt-1 m-0">
                    Configure platform-wide settings for pricing, fees, and policies.
                </p>
            </div>

            <Message severity="warn" :closable="false" class="mb-6">
                Changes to these settings will affect all providers and transactions immediately.
            </Message>

            <form @submit.prevent="submit" class="space-y-6">
                <Panel
                    v-for="(category, categoryKey) in settings"
                    :key="categoryKey"
                    :header="category.label"
                    toggleable
                    class="settings-panel"
                >
                    <template #icons>
                        <i :class="category.icon" class="mr-2 text-[#106B4F]"></i>
                    </template>

                    <p class="text-sm text-gray-500 mb-4 m-0">{{ category.description }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="(setting, key) in category.settings"
                            :key="key"
                            class="space-y-1"
                        >
                            <label :for="key" class="block text-sm font-medium text-gray-700">
                                {{ setting.label }}
                            </label>
                            <InputNumber
                                :id="key"
                                v-model="form[key]"
                                :mode="setting.type === 'currency' ? 'currency' : 'decimal'"
                                :currency="setting.type === 'currency' ? 'JMD' : undefined"
                                :suffix="setting.type === 'percentage' ? '%' : undefined"
                                :min="setting.min"
                                :max="setting.max"
                                :step="setting.step || 1"
                                :minFractionDigits="setting.step && setting.step < 1 ? 1 : 0"
                                :maxFractionDigits="setting.step && setting.step < 1 ? 2 : 0"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors[key] }"
                            />
                            <small class="text-xs text-gray-500 block">
                                {{ setting.description }}
                            </small>
                            <small v-if="form.errors[key]" class="text-red-500 block">
                                {{ form.errors[key] }}
                            </small>
                        </div>
                    </div>
                </Panel>

                <div class="flex justify-end gap-3">
                    <Button
                        label="Save All Settings"
                        icon="pi pi-check"
                        type="submit"
                        :loading="form.processing"
                        class="!bg-[#106B4F] !border-[#106B4F]"
                    />
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
:deep(.settings-panel .p-panel-header) {
    background-color: #f9fafb;
    border-color: #e5e7eb;
}

:deep(.settings-panel .p-panel-content) {
    border-color: #e5e7eb;
}

:deep(.settings-panel .p-panel-title) {
    font-weight: 600;
    color: #0D1F1B;
}
</style>

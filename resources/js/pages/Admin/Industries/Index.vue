<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import StatCard from '@/components/admin/StatCard.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ToggleSwitch from 'primevue/toggleswitch';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import admin from '@/routes/admin';
import { resolveUrl } from '@/utils/url';

interface Industry {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon?: string;
    description?: string;
    is_active: boolean;
    sort_order: number;
    providers_count?: number;
}

interface Props {
    industries: Industry[];
    stats: {
        total: number;
        active: number;
        inactive: number;
    };
}

const props = defineProps<Props>();
const toast = useToast();
const confirm = useConfirm();

// Modal state
const showModal = ref(false);
const isEditing = ref(false);
const editingIndustry = ref<Industry | null>(null);
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

// Form data
const formData = ref({
    name: '',
    description: '',
    icon: '',
    is_active: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingIndustry.value = null;
    formData.value = {
        name: '',
        description: '',
        icon: '',
        is_active: true,
    };
    errors.value = {};
    showModal.value = true;
};

const openEditModal = (industry: Industry) => {
    isEditing.value = true;
    editingIndustry.value = industry;
    formData.value = {
        name: industry.name,
        description: industry.description || '',
        icon: industry.icon || '',
        is_active: industry.is_active,
    };
    errors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingIndustry.value = null;
    errors.value = {};
};

const submitForm = async () => {
    isSubmitting.value = true;
    errors.value = {};

    const url = isEditing.value && editingIndustry.value
        ? resolveUrl(admin.industries.update.url(editingIndustry.value.uuid))
        : resolveUrl(admin.industries.store.url());

    const method = isEditing.value ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(formData.value),
        });

        const data = await response.json();

        if (response.ok) {
            closeModal();
            router.reload({ only: ['industries', 'stats'] });
            toast.add({
                severity: 'success',
                summary: isEditing.value ? 'Updated' : 'Created',
                detail: `Industry ${isEditing.value ? 'updated' : 'created'} successfully`,
                life: 3000,
            });
        } else {
            if (data.errors) {
                errors.value = data.errors;
            } else if (data.message) {
                errors.value = { general: data.message };
            }
        }
    } catch (error) {
        errors.value = { general: 'An error occurred. Please try again.' };
    } finally {
        isSubmitting.value = false;
    }
};

const deleteIndustry = (industry: Industry) => {
    if (industry.providers_count && industry.providers_count > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Cannot Delete',
            detail: `This industry has ${industry.providers_count} provider(s) assigned. Remove them first.`,
            life: 5000,
        });
        return;
    }

    confirm.require({
        message: `Are you sure you want to delete "${industry.name}"?`,
        header: 'Delete Industry',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-500 !border-red-500',
        accept: async () => {
            try {
                const response = await fetch(resolveUrl(admin.industries.destroy.url(industry.uuid)), {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                if (response.ok) {
                    router.reload({ only: ['industries', 'stats'] });
                    toast.add({
                        severity: 'success',
                        summary: 'Deleted',
                        detail: `Industry "${industry.name}" deleted`,
                        life: 3000,
                    });
                }
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete industry',
                    life: 3000,
                });
            }
        },
    });
};

const toggleActive = async (industry: Industry) => {
    try {
        const response = await fetch(resolveUrl(admin.industries.update.url(industry.uuid)), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                name: industry.name,
                is_active: !industry.is_active,
            }),
        });

        if (response.ok) {
            router.reload({ only: ['industries', 'stats'] });
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update industry status',
            life: 3000,
        });
    }
};
</script>

<template>
    <AdminLayout title="Industries">
        <div class="industries-page">
            <!-- Header -->
            <div class="page-header">
                <div class="header-info">
                    <h1 class="page-title">Industries</h1>
                    <p class="page-subtitle">Manage business types for providers</p>
                </div>
                <Button
                    label="Add Industry"
                    icon="pi pi-plus"
                    @click="openCreateModal"
                />
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <StatCard
                    title="Total Industries"
                    :value="stats.total"
                    icon="pi pi-building"
                    icon-bg="#4f46e5"
                />
                <StatCard
                    title="Active"
                    :value="stats.active"
                    icon="pi pi-check-circle"
                    icon-bg="#10b981"
                />
                <StatCard
                    title="Inactive"
                    :value="stats.inactive"
                    icon="pi pi-times-circle"
                    icon-bg="#f59e0b"
                />
            </div>

            <!-- Table -->
            <div class="table-container">
                <DataTable
                    :value="industries"
                    stripedRows
                    class="industries-table"
                >
                    <template #empty>
                        <div class="empty-state">
                            <i class="pi pi-building"></i>
                            <p>No industries found</p>
                            <Button
                                label="Create Industry"
                                icon="pi pi-plus"
                                size="small"
                                @click="openCreateModal"
                            />
                        </div>
                    </template>

                    <Column field="name" header="Industry" style="min-width: 250px">
                        <template #body="{ data }">
                            <div class="industry-cell">
                                <div v-if="data.icon" class="industry-icon">
                                    <i :class="data.icon"></i>
                                </div>
                                <div class="industry-info">
                                    <span class="industry-name">{{ data.name }}</span>
                                    <span v-if="data.description" class="industry-description">
                                        {{ data.description }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="slug" header="Slug" style="width: 180px">
                        <template #body="{ data }">
                            <span class="slug-badge">{{ data.slug }}</span>
                        </template>
                    </Column>

                    <Column field="providers_count" header="Providers" style="width: 120px">
                        <template #body="{ data }">
                            <span>{{ data.providers_count || 0 }}</span>
                        </template>
                    </Column>

                    <Column field="is_active" header="Status" style="width: 120px">
                        <template #body="{ data }">
                            <ToggleSwitch
                                :modelValue="data.is_active"
                                @update:modelValue="toggleActive(data)"
                            />
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 140px">
                        <template #body="{ data }">
                            <div class="actions">
                                <Button
                                    icon="pi pi-pencil"
                                    text
                                    rounded
                                    severity="info"
                                    v-tooltip.top="'Edit'"
                                    @click="openEditModal(data)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    text
                                    rounded
                                    severity="danger"
                                    v-tooltip.top="'Delete'"
                                    @click="deleteIndustry(data)"
                                    :disabled="data.providers_count > 0"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog
            v-model:visible="showModal"
            :header="isEditing ? 'Edit Industry' : 'Create Industry'"
            :modal="true"
            :closable="true"
            :style="{ width: '450px' }"
        >
            <div class="modal-form">
                <div v-if="errors.general" class="error-message">
                    {{ errors.general }}
                </div>

                <div class="form-field">
                    <label for="name" class="form-label">Name *</label>
                    <InputText
                        id="name"
                        v-model="formData.name"
                        placeholder="e.g., Hair Salon, Spa, Fitness"
                        class="form-input"
                        :class="{ 'p-invalid': errors.name }"
                    />
                    <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
                </div>

                <div class="form-field">
                    <label for="description" class="form-label">Description</label>
                    <Textarea
                        id="description"
                        v-model="formData.description"
                        rows="3"
                        placeholder="Optional description"
                        class="form-input"
                    />
                </div>

                <div class="form-field">
                    <label for="icon" class="form-label">Icon (PrimeIcons class)</label>
                    <InputText
                        id="icon"
                        v-model="formData.icon"
                        placeholder="e.g., pi pi-star"
                        class="form-input"
                    />
                    <small class="form-hint">
                        Use PrimeIcons class names like "pi pi-star", "pi pi-heart"
                    </small>
                </div>

                <div class="form-field">
                    <label class="form-label">Status</label>
                    <div class="switch-field">
                        <ToggleSwitch v-model="formData.is_active" />
                        <span class="switch-label">
                            {{ formData.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="modal-footer">
                    <Button
                        label="Cancel"
                        severity="secondary"
                        @click="closeModal"
                    />
                    <Button
                        :label="isEditing ? 'Save Changes' : 'Create Industry'"
                        :loading="isSubmitting"
                        @click="submitForm"
                    />
                </div>
            </template>
        </Dialog>
    </AdminLayout>
</template>

<style scoped>
.industries-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
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
    color: #0f172a;
}

.page-subtitle {
    margin: 0;
    font-size: 0.875rem;
    color: #64748b;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}

@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

.table-container {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.industry-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.industry-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 8px;
    color: #475569;
}

.industry-icon i {
    font-size: 1.125rem;
}

.industry-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.industry-name {
    font-weight: 500;
    color: #0f172a;
}

.industry-description {
    font-size: 0.75rem;
    color: #64748b;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.slug-badge {
    font-size: 0.8125rem;
    color: #64748b;
    font-family: monospace;
}

.actions {
    display: flex;
    gap: 0.25rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #94a3b8;
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

.empty-state p {
    margin: 0 0 1rem;
    font-size: 0.875rem;
}

/* Modal Form */
.modal-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #334155;
}

.form-input {
    width: 100%;
}

.form-hint {
    font-size: 0.75rem;
    color: #64748b;
}

.switch-field {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.switch-label {
    font-size: 0.875rem;
    color: #475569;
}

.error-message {
    padding: 0.75rem 1rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: #dc2626;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}
</style>

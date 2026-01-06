<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import { FloatingActionButton, ConsoleFormCard } from '@/components/console';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import providerRoutes from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    description?: string;
    type: 'service' | 'event';
    is_active: boolean;
    sort_order: number;
    services_count?: number;
}

interface Props {
    categories: Category[];
    type: 'service' | 'event';
}

const props = defineProps<Props>();
const confirm = useConfirm();

// Modal state
const showModal = ref(false);
const isEditing = ref(false);
const editingCategory = ref<Category | null>(null);
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

// Form data
const formData = ref({
    name: '',
    description: '',
    is_active: true,
});

// Tab state
const activeTab = computed(() => props.type);

const switchTab = (tab: 'service' | 'event') => {
    router.get(resolveUrl(providerRoutes.categories.index.url()), { type: tab }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const openCreateModal = () => {
    isEditing.value = false;
    editingCategory.value = null;
    formData.value = {
        name: '',
        description: '',
        is_active: true,
    };
    errors.value = {};
    showModal.value = true;
};

const openEditModal = (category: Category) => {
    isEditing.value = true;
    editingCategory.value = category;
    formData.value = {
        name: category.name,
        description: category.description || '',
        is_active: category.is_active,
    };
    errors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCategory.value = null;
    errors.value = {};
};

const submitForm = async () => {
    isSubmitting.value = true;
    errors.value = {};

    const url = isEditing.value && editingCategory.value
        ? resolveUrl(providerRoutes.categories.update.url(editingCategory.value.id))
        : resolveUrl(providerRoutes.categories.store.url());

    const method = isEditing.value ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                ...formData.value,
                type: activeTab.value,
            }),
        });

        const data = await response.json();

        if (response.ok) {
            closeModal();
            router.reload({ only: ['categories'] });
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

const deleteCategory = (category: Category) => {
    if (category.services_count && category.services_count > 0) {
        confirm.require({
            message: `This category is assigned to ${category.services_count} service(s). Please remove the category from these services before deleting.`,
            header: 'Cannot Delete Category',
            icon: 'pi pi-exclamation-triangle',
            rejectLabel: 'Close',
            acceptLabel: 'OK',
            accept: () => {},
        });
        return;
    }

    confirm.require({
        message: `Are you sure you want to delete "${category.name}"?`,
        header: 'Delete Category',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                const response = await fetch(resolveUrl(providerRoutes.categories.destroy.url(category.id)), {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                if (response.ok) {
                    router.reload({ only: ['categories'] });
                }
            } catch (error) {
                console.error('Failed to delete category:', error);
            }
        },
    });
};

const typeLabel = computed(() => activeTab.value === 'service' ? 'Service' : 'Event');
</script>

<template>
    <ConsoleLayout title="Categories">
        <ConfirmDialog />

        <div class="categories-page">
            <!-- Header -->
            <div class="page-header">
                <div class="header-info">
                    <h1 class="page-title">Categories</h1>
                    <p class="page-subtitle">Organize your services and events into categories</p>
                </div>
            </div>

            <!-- View Toggle -->
            <div class="view-toggle">
                <button
                    class="toggle-btn"
                    :class="{ active: activeTab === 'service' }"
                    @click="switchTab('service')"
                >
                    <i class="pi pi-briefcase"></i>
                    Services
                </button>
                <button
                    class="toggle-btn"
                    :class="{ active: activeTab === 'event' }"
                    @click="switchTab('event')"
                >
                    <i class="pi pi-calendar"></i>
                    Events
                </button>
            </div>

            <!-- Categories List -->
            <ConsoleFormCard>
                <div v-if="categories.length === 0" class="empty-state">
                    <i class="pi pi-folder-open"></i>
                    <h3>No {{ typeLabel.toLowerCase() }} categories yet</h3>
                    <p>Create categories to organize your {{ typeLabel.toLowerCase() }}s</p>
                    <ConsoleButton
                        label="Create Category"
                        icon="pi pi-plus"
                        variant="primary"
                        size="small"
                        @click="openCreateModal"
                    />
                </div>

                <div v-else class="categories-list">
                    <div
                        v-for="category in categories"
                        :key="category.id"
                        class="category-item"
                        :class="{ inactive: !category.is_active }"
                    >
                        <div class="category-info">
                            <div class="category-details">
                                <h4 class="category-name">{{ category.name }}</h4>
                                <p v-if="category.description" class="category-description">
                                    {{ category.description }}
                                </p>
                                <div class="category-meta">
                                    <span v-if="category.services_count !== undefined" class="meta-item">
                                        <i class="pi pi-box"></i>
                                        {{ category.services_count }} {{ category.services_count === 1 ? 'service' : 'services' }}
                                    </span>
                                    <span v-if="!category.is_active" class="meta-item inactive-badge">
                                        <i class="pi pi-eye-slash"></i>
                                        Inactive
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="category-actions">
                            <button class="action-btn edit-btn" @click="openEditModal(category)" title="Edit">
                                <i class="pi pi-pencil"></i>
                            </button>
                            <button class="action-btn delete-btn" @click="deleteCategory(category)" title="Delete">
                                <i class="pi pi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </ConsoleFormCard>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog
            v-model:visible="showModal"
            :header="isEditing ? 'Edit Category' : 'Create Category'"
            :modal="true"
            :closable="true"
            :style="{ width: '450px' }"
            class="category-dialog"
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
                        placeholder="e.g., Hair Services, Makeup, Consultations"
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
                        placeholder="Optional description for this category"
                        class="form-input"
                    />
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
                    <ConsoleButton
                        label="Cancel"
                        variant="secondary"
                        size="small"
                        @click="closeModal"
                    />
                    <ConsoleButton
                        :label="isEditing ? 'Save Changes' : 'Create Category'"
                        variant="primary"
                        size="small"
                        :loading="isSubmitting"
                        @click="submitForm"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Floating Action Button -->
        <FloatingActionButton
            icon="pi pi-plus"
            label="Add Category"
            @click="openCreateModal"
        />
    </ConsoleLayout>
</template>

<style scoped>
.categories-page {
    max-width: 800px;
    margin: 0 auto;
}

/* Header */
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
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

/* View Toggle */
.view-toggle {
    display: flex;
    background-color: var(--color-slate-100, #f1f5f9);
    border-radius: 0.5rem;
    padding: 0.25rem;
    margin-bottom: 1.5rem;
    width: fit-content;
}

.toggle-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    background: none;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
}

.toggle-btn:hover {
    color: var(--color-slate-700, #334155);
}

.toggle-btn.active {
    background-color: white;
    color: #106B4F;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.toggle-btn i {
    font-size: 0.875rem;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 3rem 2rem;
    text-align: center;
}

.empty-state i {
    font-size: 3rem;
    color: var(--color-slate-300, #cbd5e1);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-slate-700, #334155);
}

.empty-state p {
    margin: 0 0 1.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

/* Categories List */
.categories-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.category-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    transition: all 0.15s ease;
}

.category-item:hover {
    border-color: var(--color-slate-300, #cbd5e1);
}

.category-item.inactive {
    opacity: 0.6;
}

.category-info {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.category-details {
    flex: 1;
    min-width: 0;
}

.category-name {
    margin: 0 0 0.25rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.category-description {
    margin: 0 0 0.5rem;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.4;
}

.category-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.meta-item i {
    font-size: 0.75rem;
}

.inactive-badge {
    color: #f59e0b;
}

.category-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.375rem;
    color: var(--color-slate-500, #64748b);
    cursor: pointer;
    transition: all 0.15s ease;
}

.edit-btn:hover {
    border-color: #4f46e5;
    color: #4f46e5;
    background: #eef2ff;
}

.delete-btn:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: #fef2f2;
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
}

.switch-label {
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
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

@media (max-width: 639px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }

    .category-item {
        flex-direction: column;
        align-items: stretch;
    }

    .category-actions {
        justify-content: flex-end;
        padding-top: 0.75rem;
        border-top: 1px solid var(--color-slate-100, #f1f5f9);
    }
}
</style>

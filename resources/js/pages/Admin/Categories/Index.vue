<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon: string | null;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    services_count: number;
    created_at: string;
}

interface PaginatedCategories {
    data: Category[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    categories: PaginatedCategories;
    filters: {
        search: string | null;
        status: string | null;
        sort: string;
        dir: string;
    };
}>();

const confirm = useConfirm();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');

const showDialog = ref(false);
const isEditing = ref(false);
const selectedCategory = ref<Category | null>(null);

const form = ref({
    name: '',
    icon: '',
    description: '',
    is_active: true,
    sort_order: 0,
});

const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('admin.categories.index'), {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch([search, selectedStatus], applyFilters);

const openCreateDialog = () => {
    isEditing.value = false;
    selectedCategory.value = null;
    form.value = {
        name: '',
        icon: '',
        description: '',
        is_active: true,
        sort_order: 0,
    };
    showDialog.value = true;
};

const openEditDialog = (category: Category) => {
    isEditing.value = true;
    selectedCategory.value = category;
    form.value = {
        name: category.name,
        icon: category.icon || '',
        description: category.description || '',
        is_active: category.is_active,
        sort_order: category.sort_order,
    };
    showDialog.value = true;
};

const saveCategory = () => {
    if (isEditing.value && selectedCategory.value) {
        router.put(route('admin.categories.update', selectedCategory.value.uuid), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showDialog.value = false;
            },
        });
    } else {
        router.post(route('admin.categories.store'), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showDialog.value = false;
            },
        });
    }
};

const toggleStatus = (category: Category) => {
    const action = category.is_active ? 'deactivate' : 'activate';
    confirm.require({
        message: `Are you sure you want to ${action} "${category.name}"?`,
        header: `${action.charAt(0).toUpperCase() + action.slice(1)} Category`,
        icon: category.is_active ? 'pi pi-eye-slash' : 'pi pi-eye',
        accept: () => {
            router.post(route('admin.categories.toggle-status', category.uuid), {}, {
                preserveScroll: true,
            });
        },
    });
};

const deleteCategory = (category: Category) => {
    if (category.services_count > 0) {
        confirm.require({
            message: `Cannot delete "${category.name}" because it has ${category.services_count} services.`,
            header: 'Cannot Delete',
            icon: 'pi pi-exclamation-triangle',
            rejectLabel: 'OK',
            acceptLabel: ' ',
            acceptClass: 'hidden',
        });
        return;
    }

    confirm.require({
        message: `Are you sure you want to delete "${category.name}"? This action cannot be undone.`,
        header: 'Delete Category',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.categories.destroy', category.uuid), {
                preserveScroll: true,
            });
        },
    });
};
</script>

<template>
    <AdminLayout title="Categories">
        <Head title="Category Management" />

        <ConfirmDialog />

        <Dialog
            v-model:visible="showDialog"
            modal
            :header="isEditing ? 'Edit Category' : 'Create Category'"
            :style="{ width: '500px' }"
        >
            <div class="category-form">
                <div class="form-field">
                    <label>Name *</label>
                    <InputText v-model="form.name" placeholder="Category name" class="w-full" />
                </div>

                <div class="form-field">
                    <label>Icon (PrimeIcons class)</label>
                    <InputText v-model="form.icon" placeholder="e.g., pi-scissors" class="w-full" />
                    <small v-if="form.icon" class="icon-preview">
                        Preview: <i :class="['pi', form.icon]"></i>
                    </small>
                </div>

                <div class="form-field">
                    <label>Description</label>
                    <Textarea v-model="form.description" placeholder="Category description" rows="3" class="w-full" />
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>Sort Order</label>
                        <InputNumber v-model="form.sort_order" :min="0" class="w-full" />
                    </div>

                    <div class="form-field checkbox-field">
                        <Checkbox v-model="form.is_active" :binary="true" inputId="is_active" />
                        <label for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDialog = false" />
                <Button :label="isEditing ? 'Update' : 'Create'" @click="saveCategory" :disabled="!form.name" />
            </template>
        </Dialog>

        <div class="categories-page">
            <Card>
                <template #title>
                    <div class="card-header">
                        <div class="header-left">
                            <h2>Category Management</h2>
                            <Tag :value="`${categories.total} categories`" severity="secondary" />
                        </div>
                        <Button label="Add Category" icon="pi pi-plus" @click="openCreateDialog" />
                    </div>
                </template>

                <template #content>
                    <!-- Filters -->
                    <div class="filters">
                        <div class="search-box">
                            <i class="pi pi-search"></i>
                            <InputText
                                v-model="search"
                                placeholder="Search by name..."
                                class="search-input"
                            />
                        </div>

                        <Select
                            v-model="selectedStatus"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filter by status"
                            class="filter-select"
                        />
                    </div>

                    <!-- Categories Table -->
                    <DataTable
                        :value="categories.data"
                        :paginator="false"
                        :rows="20"
                        class="categories-table"
                        stripedRows
                    >
                        <Column header="Order" style="width: 80px">
                            <template #body="{ data }">
                                {{ data.sort_order }}
                            </template>
                        </Column>

                        <Column header="Category" style="min-width: 250px">
                            <template #body="{ data }">
                                <div class="category-cell">
                                    <div v-if="data.icon" class="category-icon">
                                        <i :class="['pi', data.icon]"></i>
                                    </div>
                                    <div v-else class="category-icon placeholder">
                                        <i class="pi pi-tag"></i>
                                    </div>
                                    <div class="category-info">
                                        <span class="category-name">{{ data.name }}</span>
                                        <span class="category-slug">{{ data.slug }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Description">
                            <template #body="{ data }">
                                <span v-if="data.description" class="description">{{ data.description }}</span>
                                <span v-else class="no-data">No description</span>
                            </template>
                        </Column>

                        <Column header="Services">
                            <template #body="{ data }">
                                <Tag :value="data.services_count.toString()" severity="secondary" />
                            </template>
                        </Column>

                        <Column header="Status">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'secondary'" :value="data.is_active ? 'Active' : 'Inactive'" />
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 150px">
                            <template #body="{ data }">
                                <div class="action-buttons">
                                    <Button
                                        icon="pi pi-pencil"
                                        text
                                        rounded
                                        size="small"
                                        @click="openEditDialog(data)"
                                        v-tooltip.top="'Edit'"
                                    />
                                    <Button
                                        :icon="data.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'"
                                        text
                                        rounded
                                        size="small"
                                        @click="toggleStatus(data)"
                                        v-tooltip.top="data.is_active ? 'Deactivate' : 'Activate'"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        text
                                        rounded
                                        size="small"
                                        severity="danger"
                                        @click="deleteCategory(data)"
                                        v-tooltip.top="'Delete'"
                                        :disabled="data.services_count > 0"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="pagination" v-if="categories.last_page > 1">
                        <a
                            v-for="link in categories.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="['page-link', { active: link.active, disabled: !link.url }]"
                            v-html="link.label"
                            @click.prevent="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </AdminLayout>
</template>

<style scoped>
.categories-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-left h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--p-surface-400);
}

.search-input {
    width: 100%;
    padding-left: 2.5rem;
}

.filter-select {
    min-width: 150px;
}

.category-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.category-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background-color: var(--p-red-100);
    color: var(--p-red-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
}

.category-icon.placeholder {
    background-color: var(--p-surface-100);
    color: var(--p-surface-400);
}

.category-info {
    display: flex;
    flex-direction: column;
}

.category-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.category-slug {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.description {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: 0.875rem;
    color: var(--p-surface-600);
}

.no-data {
    color: var(--p-surface-400);
    font-style: italic;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.category-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-field label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-700);
}

.form-row {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
}

.form-row .form-field {
    flex: 1;
}

.checkbox-field {
    flex-direction: row !important;
    align-items: center;
    gap: 0.5rem;
}

.icon-preview {
    color: var(--p-surface-500);
    font-size: 0.75rem;
}

.icon-preview i {
    margin-left: 0.25rem;
    font-size: 1rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
    margin-top: 1.5rem;
}

.page-link {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--p-surface-200);
    border-radius: 6px;
    text-decoration: none;
    color: var(--p-surface-700);
    font-size: 0.875rem;
    transition: all 0.2s;
    cursor: pointer;
}

.page-link:hover:not(.disabled) {
    background-color: var(--p-surface-100);
}

.page-link.active {
    background-color: var(--p-red-500);
    border-color: var(--p-red-500);
    color: white;
}

.page-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .filters {
        flex-direction: column;
    }

    .search-box,
    .filter-select {
        width: 100%;
    }

    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>

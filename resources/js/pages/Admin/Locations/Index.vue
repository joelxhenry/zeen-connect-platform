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
import Checkbox from 'primevue/checkbox';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

interface Location {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    region: {
        id: number;
        name: string;
        country: string;
    };
    is_active: boolean;
    providers_count: number;
    created_at: string;
}

interface Region {
    id: number;
    name: string;
    country: string;
}

interface PaginatedLocations {
    data: Location[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    locations: PaginatedLocations;
    regions: Region[];
    filters: {
        search: string | null;
        region: number | null;
        status: string | null;
        sort: string;
        dir: string;
    };
}>();

const confirm = useConfirm();

const search = ref(props.filters.search || '');
const selectedRegion = ref(props.filters.region?.toString() || '');
const selectedStatus = ref(props.filters.status || '');

const showDialog = ref(false);
const isEditing = ref(false);
const selectedLocation = ref<Location | null>(null);

const form = ref({
    name: '',
    region_id: null as number | null,
    is_active: true,
});

const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const regionOptions = [
    { id: '', name: 'All Regions' },
    ...props.regions.map(r => ({ id: r.id.toString(), name: `${r.name}, ${r.country}` })),
];

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('admin.locations.index'), {
            search: search.value || undefined,
            region: selectedRegion.value || undefined,
            status: selectedStatus.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch([search, selectedRegion, selectedStatus], applyFilters);

const openCreateDialog = () => {
    isEditing.value = false;
    selectedLocation.value = null;
    form.value = {
        name: '',
        region_id: null,
        is_active: true,
    };
    showDialog.value = true;
};

const openEditDialog = (location: Location) => {
    isEditing.value = true;
    selectedLocation.value = location;
    form.value = {
        name: location.name,
        region_id: location.region.id,
        is_active: location.is_active,
    };
    showDialog.value = true;
};

const saveLocation = () => {
    if (isEditing.value && selectedLocation.value) {
        router.put(route('admin.locations.update', selectedLocation.value.uuid), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showDialog.value = false;
            },
        });
    } else {
        router.post(route('admin.locations.store'), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showDialog.value = false;
            },
        });
    }
};

const toggleStatus = (location: Location) => {
    const action = location.is_active ? 'deactivate' : 'activate';
    confirm.require({
        message: `Are you sure you want to ${action} "${location.name}"?`,
        header: `${action.charAt(0).toUpperCase() + action.slice(1)} Location`,
        icon: location.is_active ? 'pi pi-eye-slash' : 'pi pi-eye',
        accept: () => {
            router.post(route('admin.locations.toggle-status', location.uuid), {}, {
                preserveScroll: true,
            });
        },
    });
};

const deleteLocation = (location: Location) => {
    if (location.providers_count > 0) {
        confirm.require({
            message: `Cannot delete "${location.name}" because it has ${location.providers_count} providers.`,
            header: 'Cannot Delete',
            icon: 'pi pi-exclamation-triangle',
            rejectLabel: 'OK',
            acceptLabel: ' ',
            acceptClass: 'hidden',
        });
        return;
    }

    confirm.require({
        message: `Are you sure you want to delete "${location.name}"? This action cannot be undone.`,
        header: 'Delete Location',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.locations.destroy', location.uuid), {
                preserveScroll: true,
            });
        },
    });
};
</script>

<template>
    <AdminLayout title="Locations">
        <Head title="Location Management" />

        <ConfirmDialog />

        <Dialog
            v-model:visible="showDialog"
            modal
            :header="isEditing ? 'Edit Location' : 'Create Location'"
            :style="{ width: '450px' }"
        >
            <div class="location-form">
                <div class="form-field">
                    <label>Name *</label>
                    <InputText v-model="form.name" placeholder="Location name" class="w-full" />
                </div>

                <div class="form-field">
                    <label>Region *</label>
                    <Select
                        v-model="form.region_id"
                        :options="regions"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select region"
                        class="w-full"
                    >
                        <template #option="{ option }">
                            {{ option.name }}, {{ option.country }}
                        </template>
                    </Select>
                </div>

                <div class="form-field checkbox-field">
                    <Checkbox v-model="form.is_active" :binary="true" inputId="is_active" />
                    <label for="is_active">Active</label>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showDialog = false" />
                <Button :label="isEditing ? 'Update' : 'Create'" @click="saveLocation" :disabled="!form.name || !form.region_id" />
            </template>
        </Dialog>

        <div class="locations-page">
            <Card>
                <template #title>
                    <div class="card-header">
                        <div class="header-left">
                            <h2>Location Management</h2>
                            <Tag :value="`${locations.total} locations`" severity="secondary" />
                        </div>
                        <Button label="Add Location" icon="pi pi-plus" @click="openCreateDialog" />
                    </div>
                </template>

                <template #content>
                    <!-- Filters -->
                    <div class="filters">
                        <div class="search-box">
                            <i class="pi pi-search"></i>
                            <InputText
                                v-model="search"
                                placeholder="Search by name or region..."
                                class="search-input"
                            />
                        </div>

                        <Select
                            v-model="selectedRegion"
                            :options="regionOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Filter by region"
                            class="filter-select region-select"
                        />

                        <Select
                            v-model="selectedStatus"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filter by status"
                            class="filter-select"
                        />
                    </div>

                    <!-- Locations Table -->
                    <DataTable
                        :value="locations.data"
                        :paginator="false"
                        :rows="20"
                        class="locations-table"
                        stripedRows
                    >
                        <Column header="Location" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="location-cell">
                                    <div class="location-icon">
                                        <i class="pi pi-map-marker"></i>
                                    </div>
                                    <div class="location-info">
                                        <span class="location-name">{{ data.name }}</span>
                                        <span class="location-slug">{{ data.slug }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Region">
                            <template #body="{ data }">
                                <div class="region-cell">
                                    <span class="region-name">{{ data.region.name }}</span>
                                    <span class="region-country">{{ data.region.country }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Providers">
                            <template #body="{ data }">
                                <Tag :value="data.providers_count.toString()" severity="secondary" />
                            </template>
                        </Column>

                        <Column header="Status">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'secondary'" :value="data.is_active ? 'Active' : 'Inactive'" />
                            </template>
                        </Column>

                        <Column header="Created">
                            <template #body="{ data }">
                                {{ data.created_at }}
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
                                        @click="deleteLocation(data)"
                                        v-tooltip.top="'Delete'"
                                        :disabled="data.providers_count > 0"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="pagination" v-if="locations.last_page > 1">
                        <a
                            v-for="link in locations.links"
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
.locations-page {
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

.region-select {
    min-width: 200px;
}

.location-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.location-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background-color: var(--p-blue-100);
    color: var(--p-blue-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
}

.location-info {
    display: flex;
    flex-direction: column;
}

.location-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.location-slug {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.region-cell {
    display: flex;
    flex-direction: column;
}

.region-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.region-country {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.location-form {
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

.checkbox-field {
    flex-direction: row !important;
    align-items: center;
    gap: 0.5rem;
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
    .filter-select,
    .region-select {
        width: 100%;
    }

    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>

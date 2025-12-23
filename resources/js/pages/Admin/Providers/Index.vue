<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import ConfirmDialog from 'primevue/confirmdialog';
import Dialog from 'primevue/dialog';
import { useConfirm } from 'primevue/useconfirm';

interface Provider {
    id: number;
    uuid: string;
    slug: string;
    business_name: string;
    user: {
        name: string;
        email: string;
        avatar: string | null;
    };
    location: string | null;
    status: string;
    is_featured: boolean;
    rating_avg: number;
    rating_count: number;
    total_bookings: number;
    services_count: number;
    reviews_count: number;
    commission_rate: number;
    verified_at: string | null;
    created_at: string;
}

interface PaginatedProviders {
    data: Provider[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Status {
    value: string;
    label: string;
}

const props = defineProps<{
    providers: PaginatedProviders;
    filters: {
        search: string | null;
        status: string | null;
        featured: string | null;
        sort: string;
        dir: string;
    };
    statuses: Status[];
}>();

const confirm = useConfirm();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedFeatured = ref(props.filters.featured || '');

const showStatusDialog = ref(false);
const selectedProvider = ref<Provider | null>(null);
const newStatus = ref('');

const featuredOptions = [
    { value: '', label: 'All' },
    { value: 'yes', label: 'Featured' },
    { value: 'no', label: 'Not Featured' },
];

const statusOptions = [
    { value: '', label: 'All Status' },
    ...props.statuses,
];

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('admin.providers.index'), {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
            featured: selectedFeatured.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch([search, selectedStatus, selectedFeatured], applyFilters);

const getStatusSeverity = (status: string) => {
    const severities: Record<string, string> = {
        pending: 'warning',
        active: 'success',
        suspended: 'danger',
    };
    return severities[status] || 'secondary';
};

const openStatusDialog = (provider: Provider) => {
    selectedProvider.value = provider;
    newStatus.value = provider.status;
    showStatusDialog.value = true;
};

const updateStatus = () => {
    if (selectedProvider.value && newStatus.value) {
        router.put(route('admin.providers.status', selectedProvider.value.uuid), {
            status: newStatus.value,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showStatusDialog.value = false;
                selectedProvider.value = null;
            },
        });
    }
};

const toggleFeatured = (provider: Provider) => {
    const action = provider.is_featured ? 'unfeature' : 'feature';
    confirm.require({
        message: `Are you sure you want to ${action} ${provider.business_name}?`,
        header: `${action.charAt(0).toUpperCase() + action.slice(1)} Provider`,
        icon: 'pi pi-star',
        accept: () => {
            router.post(route('admin.providers.toggle-featured', provider.uuid), {}, {
                preserveScroll: true,
            });
        },
    });
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <AdminLayout title="Providers">
        <Head title="Provider Management" />

        <ConfirmDialog />

        <Dialog
            v-model:visible="showStatusDialog"
            modal
            header="Update Provider Status"
            :style="{ width: '400px' }"
        >
            <div class="status-dialog">
                <p>Update status for <strong>{{ selectedProvider?.business_name }}</strong></p>
                <Select
                    v-model="newStatus"
                    :options="statuses"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select status"
                    class="w-full"
                />
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showStatusDialog = false" />
                <Button label="Update" @click="updateStatus" />
            </template>
        </Dialog>

        <div class="providers-page">
            <Card>
                <template #title>
                    <div class="card-header">
                        <div class="header-left">
                            <h2>Provider Management</h2>
                            <Tag :value="`${providers.total} providers`" severity="secondary" />
                        </div>
                    </div>
                </template>

                <template #content>
                    <!-- Filters -->
                    <div class="filters">
                        <div class="search-box">
                            <i class="pi pi-search"></i>
                            <InputText
                                v-model="search"
                                placeholder="Search by business name or email..."
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

                        <Select
                            v-model="selectedFeatured"
                            :options="featuredOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Featured"
                            class="filter-select"
                        />
                    </div>

                    <!-- Providers Table -->
                    <DataTable
                        :value="providers.data"
                        :paginator="false"
                        :rows="20"
                        class="providers-table"
                        stripedRows
                    >
                        <Column header="Provider" style="min-width: 280px">
                            <template #body="{ data }">
                                <div class="provider-cell">
                                    <Avatar
                                        v-if="data.user.avatar"
                                        :image="data.user.avatar"
                                        shape="circle"
                                        size="normal"
                                    />
                                    <Avatar
                                        v-else
                                        :label="getInitials(data.business_name)"
                                        shape="circle"
                                        size="normal"
                                        class="provider-avatar"
                                    />
                                    <div class="provider-info">
                                        <div class="provider-name-row">
                                            <span class="provider-name">{{ data.business_name }}</span>
                                            <i v-if="data.is_featured" class="pi pi-star-fill featured-icon"></i>
                                        </div>
                                        <span class="provider-owner">{{ data.user.name }} &bull; {{ data.user.email }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Location">
                            <template #body="{ data }">
                                <span v-if="data.location">{{ data.location }}</span>
                                <span v-else class="no-location">Not set</span>
                            </template>
                        </Column>

                        <Column header="Status">
                            <template #body="{ data }">
                                <Tag :severity="getStatusSeverity(data.status)" :value="data.status" class="capitalize" />
                            </template>
                        </Column>

                        <Column header="Rating">
                            <template #body="{ data }">
                                <div v-if="data.rating_count > 0" class="rating">
                                    <i class="pi pi-star-fill rating-icon"></i>
                                    <span>{{ data.rating_avg.toFixed(1) }}</span>
                                    <span class="rating-count">({{ data.rating_count }})</span>
                                </div>
                                <span v-else class="no-rating">No reviews</span>
                            </template>
                        </Column>

                        <Column header="Bookings">
                            <template #body="{ data }">
                                {{ data.total_bookings }}
                            </template>
                        </Column>

                        <Column header="Services">
                            <template #body="{ data }">
                                {{ data.services_count }}
                            </template>
                        </Column>

                        <Column header="Commission">
                            <template #body="{ data }">
                                {{ data.commission_rate }}%
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 150px">
                            <template #body="{ data }">
                                <div class="action-buttons">
                                    <Link :href="route('admin.providers.show', data.uuid)">
                                        <Button
                                            icon="pi pi-eye"
                                            text
                                            rounded
                                            size="small"
                                            v-tooltip.top="'View'"
                                        />
                                    </Link>
                                    <Button
                                        icon="pi pi-pencil"
                                        text
                                        rounded
                                        size="small"
                                        @click="openStatusDialog(data)"
                                        v-tooltip.top="'Update Status'"
                                    />
                                    <Button
                                        :icon="data.is_featured ? 'pi pi-star-fill' : 'pi pi-star'"
                                        :severity="data.is_featured ? 'warning' : 'secondary'"
                                        text
                                        rounded
                                        size="small"
                                        @click="toggleFeatured(data)"
                                        v-tooltip.top="data.is_featured ? 'Unfeature' : 'Feature'"
                                    />
                                    <a :href="`/providers/${data.slug}`" target="_blank">
                                        <Button
                                            icon="pi pi-external-link"
                                            text
                                            rounded
                                            size="small"
                                            v-tooltip.top="'View Profile'"
                                        />
                                    </a>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="pagination" v-if="providers.last_page > 1">
                        <Link
                            v-for="link in providers.links"
                            :key="link.label"
                            :href="link.url || ''"
                            :class="['page-link', { active: link.active, disabled: !link.url }]"
                            v-html="link.label"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </AdminLayout>
</template>

<style scoped>
.providers-page {
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

.provider-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.provider-avatar {
    background-color: var(--p-primary-color);
    color: white;
}

.provider-info {
    display: flex;
    flex-direction: column;
}

.provider-name-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.provider-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.featured-icon {
    color: var(--p-yellow-500);
    font-size: 0.75rem;
}

.provider-owner {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.no-location,
.no-rating {
    color: var(--p-surface-400);
    font-style: italic;
}

.rating {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.rating-icon {
    color: var(--p-yellow-500);
    font-size: 0.875rem;
}

.rating-count {
    color: var(--p-surface-400);
    font-size: 0.75rem;
}

.capitalize {
    text-transform: capitalize;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.status-dialog {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-dialog p {
    margin: 0;
    color: var(--p-surface-600);
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
}
</style>

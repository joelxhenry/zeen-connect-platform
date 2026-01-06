<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useWindowSize } from '@vueuse/core';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsoleEmptyState,
    FloatingActionButton,
    ServiceListItem,
    InlinePageTitle,
} from '@/components/console';
import InputText from 'primevue/inputtext';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import providerRoutes from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon?: string;
}

interface Service {
    id: number;
    uuid: string;
    name: string;
    description: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    is_active: boolean;
    sort_order: number;
    category: Category | null;
    cover_url?: string;
    cover_thumbnail?: string;
    total_bookings: number;
}

interface Props {
    services: Service[];
    stats: {
        total: number;
        active: number;
        inactive: number;
    };
    categories: Category[];
    providerDefaults: Record<string, unknown>;
    tierRestrictions: Record<string, unknown>;
}

const props = defineProps<Props>();
const confirm = useConfirm();

// Mobile detection
const { width } = useWindowSize();
const isMobile = computed(() => width.value < 640);

// Filters
const searchQuery = ref('');
const categoryFilter = ref<number | null>(null);
const statusFilter = ref<'all' | 'active' | 'inactive'>('all');

// Status pills
const statusPills = computed(() => [
    { label: 'All', value: 'all' as const, count: props.stats.total },
    { label: 'Active', value: 'active' as const, count: props.stats.active },
    { label: 'Inactive', value: 'inactive' as const, count: props.stats.inactive },
]);

// Category options with "All" option
const categoryOptions = computed(() => [
    { id: null, name: 'All Categories' },
    ...props.categories,
]);

// Filtered services
const filteredServices = computed(() => {
    let result = [...props.services];

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (s) =>
                s.name.toLowerCase().includes(query) ||
                s.description?.toLowerCase().includes(query) ||
                s.category?.name.toLowerCase().includes(query)
        );
    }

    // Apply category filter
    if (categoryFilter.value !== null) {
        result = result.filter((s) => s.category?.id === categoryFilter.value);
    }

    // Apply status filter
    if (statusFilter.value === 'active') {
        result = result.filter((s) => s.is_active);
    } else if (statusFilter.value === 'inactive') {
        result = result.filter((s) => !s.is_active);
    }

    return result;
});

// Group services by category
const groupedServices = computed(() => {
    const groups: Record<string, { category: Category | null; services: Service[] }> = {};

    for (const service of filteredServices.value) {
        const key = service.category?.id?.toString() ?? 'uncategorized';
        if (!groups[key]) {
            groups[key] = {
                category: service.category,
                services: [],
            };
        }
        groups[key].services.push(service);
    }

    return Object.values(groups).sort((a, b) => {
        if (!a.category) return 1;
        if (!b.category) return -1;
        return a.category.name.localeCompare(b.category.name);
    });
});

const clearSearch = () => {
    searchQuery.value = '';
};

const toggleServiceActive = (service: Service) => {
    router.post(
        resolveUrl(providerRoutes.services.toggleActive.url(service.uuid)),
        {},
        { preserveScroll: true }
    );
};

const editService = (service: Service) => {
    router.get(resolveUrl(providerRoutes.services.edit.url(service.uuid)));
};

const deleteService = (service: Service) => {
    confirm.require({
        message: `Are you sure you want to delete "${service.name}"? This action cannot be undone.`,
        header: 'Delete Service',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(resolveUrl(providerRoutes.services.destroy.url(service.uuid)), {
                preserveScroll: true,
            });
        },
    });
};

const createService = () => {
    router.get(resolveUrl(providerRoutes.services.create.url()));
};
</script>

<template>
    <ConsoleLayout title="Services">
        <ConfirmDialog />

        <div class="services-page">
            <!-- Header -->
            <InlinePageTitle
                title="Services"
                :count="stats.total"
                count-label="services"
            />

            <!-- Search -->
            <div class="search-section">
                <div class="search-wrapper">
                    <i class="pi pi-search search-icon"></i>
                    <InputText
                        v-model="searchQuery"
                        placeholder="Search services..."
                        class="search-input"
                    />
                    <button v-if="searchQuery" class="search-clear" @click="clearSearch">
                        <i class="pi pi-times"></i>
                    </button>
                </div>
            </div>

            <!-- Category Filter Pills -->
            <div class="filter-pills-section">
                <div class="pills-row">
                    <button
                        v-for="cat in categoryOptions"
                        :key="cat.id ?? 'all'"
                        class="filter-pill"
                        :class="{ active: categoryFilter === cat.id }"
                        @click="categoryFilter = cat.id"
                    >
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <!-- Status Pills -->
            <div class="status-pills-section">
                <div class="pills-row">
                    <button
                        v-for="pill in statusPills"
                        :key="pill.value"
                        class="status-pill"
                        :class="{
                            active: statusFilter === pill.value,
                            [`status-${pill.value}`]: true,
                        }"
                        @click="statusFilter = pill.value"
                    >
                        {{ pill.label }}
                        <span class="pill-count">{{ pill.count }}</span>
                    </button>
                </div>
            </div>

            <!-- Services List -->
            <div class="services-list">
                <ConsoleEmptyState
                    v-if="filteredServices.length === 0"
                    icon="pi pi-th-large"
                    :title="searchQuery ? 'No services found' : 'No services yet'"
                    :description="
                        searchQuery
                            ? 'Try a different search term'
                            : 'Create your first service to start accepting bookings'
                    "
                    :action-label="!searchQuery ? 'Add Service' : undefined"
                    :action-href="!searchQuery ? resolveUrl(providerRoutes.services.create.url()) : undefined"
                    action-icon="pi pi-plus"
                />

                <div v-else class="service-groups">
                    <div
                        v-for="group in groupedServices"
                        :key="group.category?.id ?? 'uncategorized'"
                        class="service-group"
                    >
                        <div class="group-header">
                            <span class="group-label">
                                <i v-if="group.category?.icon" :class="group.category.icon"></i>
                                {{ group.category?.name ?? 'Uncategorized' }}
                            </span>
                            <span class="group-count">{{ group.services.length }}</span>
                        </div>

                        <div class="service-list">
                            <ServiceListItem
                                v-for="service in group.services"
                                :key="service.uuid"
                                :service="service"
                                :compact="isMobile"
                                @edit="editService"
                                @delete="deleteService"
                                @toggle="toggleServiceActive"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Floating Action Button -->
            <FloatingActionButton
                icon="pi pi-plus"
                label="Add Service"
                @click="createService"
            />
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.services-page {
    max-width: 800px;
    margin: 0 auto;
    padding-bottom: 5rem; /* Space for FAB */
}

/* Search Section */
.search-section {
    margin-bottom: 1rem;
}

.search-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-slate-400, #94a3b8);
    font-size: 0.875rem;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding-left: 2.5rem;
    padding-right: 2.5rem;
    height: 44px;
    font-size: 0.9375rem;
    border-radius: 0.625rem;
}

.search-clear {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-slate-100, #f1f5f9);
    border: none;
    border-radius: 50%;
    color: var(--color-slate-500, #64748b);
    cursor: pointer;
    transition: all 0.15s ease;
}

.search-clear:hover {
    background: var(--color-slate-200, #e2e8f0);
    color: var(--color-slate-700, #334155);
}

/* Filter Pills Section */
.filter-pills-section {
    margin-bottom: 0.75rem;
}

.pills-row {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 0.25rem;
}

.pills-row::-webkit-scrollbar {
    display: none;
}

.filter-pill {
    flex-shrink: 0;
    padding: 0.5rem 0.875rem;
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.filter-pill:hover {
    background-color: var(--color-slate-50, #f8fafc);
    border-color: var(--color-slate-300, #cbd5e1);
}

.filter-pill.active {
    background-color: #106b4f;
    border-color: #106b4f;
    color: white;
}

/* Status Pills Section */
.status-pills-section {
    margin-bottom: 1rem;
}

.status-pill {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.4375rem 0.75rem;
    background-color: var(--color-slate-50, #f8fafc);
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.status-pill:hover {
    background-color: var(--color-slate-100, #f1f5f9);
}

.status-pill.active {
    background-color: var(--color-slate-900, #0f172a);
    border-color: var(--color-slate-900, #0f172a);
    color: white;
}

.status-pill.active.status-active {
    background-color: #10b981;
    border-color: #10b981;
}

.status-pill.active.status-inactive {
    background-color: #6b7280;
    border-color: #6b7280;
}

.pill-count {
    padding: 0.0625rem 0.375rem;
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
}

.status-pill:not(.active) .pill-count {
    background-color: var(--color-slate-200, #e2e8f0);
}

/* Service Groups */
.service-groups {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.service-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 0.25rem;
}

.group-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-slate-700, #334155);
}

.group-label i {
    color: #106b4f;
}

.group-count {
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--color-slate-100, #f1f5f9);
    border-radius: 50%;
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--color-slate-600, #475569);
}

/* Service List */
.service-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
</style>

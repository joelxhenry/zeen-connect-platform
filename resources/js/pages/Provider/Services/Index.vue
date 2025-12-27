<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleEmptyState,
    ConsoleDataCard,
    ConsoleFormCard,
    ConsoleStatCard,
    ConsoleButton,
} from '@/components/console';
import TierRestrictionBanner from '@/components/service/TierRestrictionBanner.vue';
import AppLink from '@/components/common/AppLink.vue';
import provider from '@/routes/provider';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import type { ServicesIndexProps, Service } from '@/types/service';

const props = defineProps<ServicesIndexProps>();
const confirm = useConfirm();
const toast = useToast();

// Filters
const searchQuery = ref('');
const selectedCategory = ref<number | null>(null);
const selectedStatus = ref<'all' | 'active' | 'inactive'>('all');

// Category filter options
const categoryOptions = computed(() => [
    { value: null, label: 'All Categories' },
    ...props.categories.map(cat => ({ value: cat.id, label: cat.name })),
]);

// Status filter options
const statusOptions = [
    { value: 'all', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

// Filtered services
const filteredServices = computed(() => {
    let result = props.services;

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(service =>
            service.name.toLowerCase().includes(query) ||
            service.description?.toLowerCase().includes(query)
        );
    }

    // Category filter
    if (selectedCategory.value !== null) {
        result = result.filter(service => service.category_id === selectedCategory.value);
    }

    // Status filter
    if (selectedStatus.value !== 'all') {
        const isActive = selectedStatus.value === 'active';
        result = result.filter(service => service.is_active === isActive);
    }

    return result;
});

const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = null;
    selectedStatus.value = 'all';
};

const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedCategory.value !== null || selectedStatus.value !== 'all';
});

const deleteService = (service: Service) => {
    confirm.require({
        message: `Are you sure you want to delete "${service.name}"? This action cannot be undone.`,
        header: 'Delete Service',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-500 !border-red-500',
        accept: () => {
            router.delete(provider.services.destroy.url(service.uuid), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Deleted',
                        detail: 'Service deleted successfully',
                        life: 3000,
                    });
                },
            });
        },
    });
};

const toggleActive = (service: Service) => {
    router.post(provider.services.toggleActive.url(service.uuid), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: service.is_active ? 'Deactivated' : 'Activated',
                detail: `Service is now ${service.is_active ? 'inactive' : 'active'}`,
                life: 3000,
            });
        },
    });
};

const getPlaceholderImage = (service: Service) => {
    // Generate a consistent placeholder based on service name
    const hue = service.name.charCodeAt(0) * 10 % 360;
    return `linear-gradient(135deg, hsl(${hue}, 40%, 85%) 0%, hsl(${hue}, 50%, 75%) 100%)`;
};
</script>

<template>
    <ConsoleLayout title="Services">
        <ConfirmDialog />

        <div class="w-full max-w-7xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Services"
                subtitle="Manage the services you offer to clients"
            >
                <template #title-badge>
                    <Tag
                        :value="tierRestrictions.tier_label"
                        :severity="tierRestrictions.tier === 'enterprise' ? 'success' : tierRestrictions.tier === 'premium' ? 'info' : 'secondary'"
                        class="ml-2"
                    />
                </template>
                <template #actions>
                    <ConsoleButton
                        label="Add Service"
                        icon="pi pi-plus"
                        :href="provider.services.create.url()"
                    />
                </template>
            </ConsolePageHeader>

            <!-- Stats Row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <ConsoleStatCard
                    title="Total Services"
                    :value="stats.total"
                    icon="pi pi-th-large"
                    icon-color="primary"
                />
                <ConsoleStatCard
                    title="Active"
                    :value="stats.active"
                    icon="pi pi-check-circle"
                    icon-color="success"
                />
                <ConsoleStatCard
                    title="Inactive"
                    :value="stats.inactive"
                    icon="pi pi-pause-circle"
                    icon-color="warning"
                />
            </div>

            <!-- Tier Restriction Banner -->
            <TierRestrictionBanner
                :restrictions="tierRestrictions"
                class="mb-6"
            />

            <!-- Filters -->
            <ConsoleFormCard v-if="services.length > 0" class="mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search services..."
                            class="w-full"
                        >
                            <template #prefix>
                                <i class="pi pi-search text-gray-400" />
                            </template>
                        </InputText>
                    </div>
                    <Select
                        v-model="selectedCategory"
                        :options="categoryOptions"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full sm:w-48"
                    />
                    <Select
                        v-model="selectedStatus"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full sm:w-36"
                    />
                    <Button
                        v-if="hasActiveFilters"
                        icon="pi pi-times"
                        severity="secondary"
                        outlined
                        v-tooltip="'Clear filters'"
                        @click="clearFilters"
                    />
                </div>
            </ConsoleFormCard>

            <!-- Empty State - No Services -->
            <ConsoleDataCard v-if="services.length === 0" :hoverable="false">
                <ConsoleEmptyState
                    icon="pi pi-th-large"
                    title="No services yet"
                    description="Start by adding your first service. Services define what you offer and how much you charge."
                    action-label="Add Your First Service"
                    :action-href="provider.services.create.url()"
                    action-icon="pi pi-plus"
                />
            </ConsoleDataCard>

            <!-- Empty State - No Results -->
            <ConsoleDataCard v-else-if="filteredServices.length === 0" :hoverable="false">
                <ConsoleEmptyState
                    icon="pi pi-search"
                    title="No services found"
                    description="No services match your current filters. Try adjusting your search criteria."
                    action-label="Clear Filters"
                    action-icon="pi pi-times"
                    size="compact"
                    @action="clearFilters"
                />
            </ConsoleDataCard>

            <!-- Services Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <ConsoleDataCard
                    v-for="service in filteredServices"
                    :key="service.uuid"
                    class="flex flex-col"
                >
                    <!-- Cover Image -->
                    <div class="relative -mx-4 -mt-4 mb-4 h-32 overflow-hidden rounded-t-xl">
                        <img
                            v-if="service.cover?.thumbnail_url"
                            :src="service.cover.thumbnail_url"
                            :alt="service.name"
                            class="w-full h-full object-cover"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center"
                            :style="{ background: getPlaceholderImage(service) }"
                        >
                            <i class="pi pi-image text-3xl text-white/60" />
                        </div>
                        <!-- Status Badge -->
                        <div class="absolute top-3 right-3">
                            <Tag
                                :value="service.is_active ? 'Active' : 'Inactive'"
                                :severity="service.is_active ? 'success' : 'warn'"
                            />
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 flex flex-col">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-[#0D1F1B] m-0 mb-1 truncate">
                                    {{ service.name }}
                                </h3>
                                <Tag
                                    v-if="service.category"
                                    :value="service.category.name"
                                    severity="secondary"
                                    class="!text-xs"
                                />
                            </div>
                            <div class="text-right shrink-0 ml-3">
                                <p class="font-bold text-[#106B4F] m-0 text-lg">
                                    {{ service.price_display }}
                                </p>
                            </div>
                        </div>

                        <p v-if="service.description" class="text-sm text-gray-500 m-0 mb-3 line-clamp-2">
                            {{ service.description }}
                        </p>

                        <div class="flex items-center gap-4 text-sm text-gray-600 mt-auto">
                            <div class="flex items-center gap-1.5">
                                <i class="pi pi-clock text-xs text-gray-400" />
                                <span>{{ service.duration_display }}</span>
                            </div>
                            <div v-if="service.total_bookings !== undefined" class="flex items-center gap-1.5">
                                <i class="pi pi-calendar text-xs text-gray-400" />
                                <span>{{ service.total_bookings }} bookings</span>
                            </div>
                        </div>
                    </div>

                    <template #footer>
                        <div class="flex items-center gap-2">
                            <AppLink :href="provider.services.edit.url(service.uuid)" class="flex-1">
                                <Button
                                    label="Edit"
                                    icon="pi pi-pencil"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    class="w-full"
                                />
                            </AppLink>
                            <Button
                                :icon="service.is_active ? 'pi pi-pause' : 'pi pi-play'"
                                size="small"
                                :severity="service.is_active ? 'warn' : 'success'"
                                outlined
                                v-tooltip="service.is_active ? 'Deactivate' : 'Activate'"
                                @click="toggleActive(service)"
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                outlined
                                v-tooltip="'Delete'"
                                @click="deleteService(service)"
                            />
                        </div>
                    </template>
                </ConsoleDataCard>
            </div>
        </div>
    </ConsoleLayout>
</template>

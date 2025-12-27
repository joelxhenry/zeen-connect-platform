<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleEmptyState,
    ConsoleDataCard,
    ConsoleStatCard,
    ConsoleButton,
} from '@/components/console';
import TierRestrictionBanner from '@/components/service/TierRestrictionBanner.vue';
import ServiceCard from '@/components/service/ServiceCard.vue';
import ServiceFilters from '@/components/service/ServiceFilters.vue';
import provider from '@/routes/provider';
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
            <ServiceFilters
                v-if="services.length > 0"
                v-model:search="searchQuery"
                v-model:category="selectedCategory"
                v-model:status="selectedStatus"
                :categories="categories"
                class="mb-6"
                @clear="clearFilters"
            />

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
                <ServiceCard
                    v-for="service in filteredServices"
                    :key="service.uuid"
                    :service="service"
                    :edit-url="provider.services.edit.url(service.uuid)"
                    @toggle-active="toggleActive"
                    @delete="deleteService"
                />
            </div>
        </div>
    </ConsoleLayout>
</template>

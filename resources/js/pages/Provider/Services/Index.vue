<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import type { Service } from '@/types/models';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import ConfirmDialog from 'primevue/confirmdialog';

interface Props {
    services: Service[];
}

defineProps<Props>();
const confirm = useConfirm();

const deleteService = (service: Service) => {
    confirm.require({
        message: `Are you sure you want to delete "${service.name}"?`,
        header: 'Delete Service',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('provider.services.destroy', service.uuid), {
                preserveScroll: true,
            });
        },
    });
};

const formatDuration = (minutes: number): string => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours > 0 && mins > 0) return `${hours}h ${mins}m`;
    if (hours > 0) return `${hours}h`;
    return `${mins}m`;
};

const formatPrice = (price: number): string => {
    return '$' + Number(price).toFixed(2);
};
</script>

<template>
    <ConsoleLayout title="Services">
        <ConfirmDialog />

        <div class="flex flex-col gap-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-[var(--p-surface-900)] m-0">My Services</h1>
                    <p class="text-sm text-[var(--p-surface-500)] mt-1 m-0">Manage the services you offer to clients</p>
                </div>
                <Link :href="route('provider.services.create')">
                    <Button label="Add Service" icon="pi pi-plus" />
                </Link>
            </div>

            <!-- Services Table -->
            <div class="bg-[var(--p-surface-0)] border border-[var(--p-surface-200)] rounded-xl overflow-hidden">
                <DataTable
                    :value="services"
                    :paginator="services.length > 10"
                    :rows="10"
                    dataKey="id"
                    :rowHover="true"
                    responsiveLayout="scroll"
                    class="services-table"
                    :pt="{
                        table: { class: 'w-full' },
                        header: { class: 'bg-[var(--p-surface-50)]' },
                    }"
                >
                    <template #empty>
                        <div class="text-center py-12 px-4">
                            <div class="w-16 h-16 bg-[var(--p-surface-100)] rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i class="pi pi-box text-2xl text-[var(--p-surface-400)]"></i>
                            </div>
                            <h3 class="text-[0.9375rem] font-semibold text-[var(--p-surface-900)] m-0 mb-1.5">No services yet</h3>
                            <p class="text-[0.8125rem] text-[var(--p-surface-500)] m-0 mb-4">Add your first service to start accepting bookings</p>
                            <Link :href="route('provider.services.create')">
                                <Button label="Add Your First Service" icon="pi pi-plus" size="small" />
                            </Link>
                        </div>
                    </template>

                    <Column field="name" header="Service" style="min-width: 200px">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-medium text-[var(--p-surface-900)]">{{ data.name }}</span>
                                <span v-if="data.category" class="text-xs text-[var(--p-surface-500)]">
                                    {{ data.category.name }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column field="duration_minutes" header="Duration" style="min-width: 100px">
                        <template #body="{ data }">
                            <span class="text-[var(--p-surface-700)]">{{ formatDuration(data.duration_minutes) }}</span>
                        </template>
                    </Column>

                    <Column field="price" header="Price" style="min-width: 100px">
                        <template #body="{ data }">
                            <span class="font-medium text-[var(--p-surface-900)]">{{ formatPrice(data.price) }}</span>
                        </template>
                    </Column>

                    <Column field="is_active" header="Status" style="min-width: 100px">
                        <template #body="{ data }">
                            <Tag
                                :value="data.is_active ? 'Active' : 'Inactive'"
                                :severity="data.is_active ? 'success' : 'secondary'"
                            />
                        </template>
                    </Column>

                    <Column header="Actions" style="min-width: 120px" :exportable="false">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <Link :href="route('provider.services.edit', data.uuid)">
                                    <Button icon="pi pi-pencil" text rounded size="small" severity="secondary" />
                                </Link>
                                <Button
                                    icon="pi pi-trash"
                                    text
                                    rounded
                                    size="small"
                                    severity="danger"
                                    @click="deleteService(data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
:deep(.services-table .p-datatable-thead > tr > th) {
    background-color: var(--p-surface-50);
    padding: 0.875rem 1rem;
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--p-surface-600);
    border-bottom: 1px solid var(--p-surface-200);
}

:deep(.services-table .p-datatable-tbody > tr > td) {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--p-surface-100);
}

:deep(.services-table .p-datatable-tbody > tr:last-child > td) {
    border-bottom: none;
}

:deep(.services-table .p-datatable-tbody > tr:hover) {
    background-color: var(--p-surface-50);
}
</style>

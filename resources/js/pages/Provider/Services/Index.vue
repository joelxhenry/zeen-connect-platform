<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import provider from '@/routes/provider';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

interface Service {
    uuid: string;
    name: string;
    description?: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    is_active: boolean;
    category?: { name: string };
}

interface BookingSettings {
    requires_approval: boolean;
    deposit_type: 'none' | 'fixed' | 'percentage';
    deposit_amount: number | null;
    cancellation_policy: 'flexible' | 'moderate' | 'strict';
    advance_booking_days: number;
    min_booking_notice_hours: number;
}

interface Props {
    services: Service[];
    providerDefaults: BookingSettings;
}

const props = defineProps<Props>();
const confirm = useConfirm();
const toast = useToast();

const deleteService = (service: Service) => {
    confirm.require({
        message: `Are you sure you want to delete "${service.name}"?`,
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
    });
};
</script>

<template>
    <ConsoleLayout title="Services">
        <ConfirmDialog />

        <div class="w-full max-w-7xl mx-auto">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-[#0D1F1B] m-0 mb-1">Services</h1>
                    <p class="text-gray-500 m-0 text-sm lg:text-base">
                        Manage the services you offer to clients
                    </p>
                </div>
                <Link :href="provider.services.create.url()">
                    <Button label="Add Service" icon="pi pi-plus" class="!bg-[#106B4F] !border-[#106B4F]" />
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="services.length === 0" class="bg-white rounded-xl p-8 lg:p-12 shadow-sm text-center">
                <i class="pi pi-th-large text-4xl lg:text-5xl text-gray-300 mb-4 block"></i>
                <h2 class="text-lg lg:text-xl font-semibold text-[#0D1F1B] m-0 mb-2">No services yet</h2>
                <p class="text-gray-500 m-0 mb-6 max-w-md mx-auto">
                    Start by adding your first service. Services define what you offer and how much you charge.
                </p>
                <Link :href="provider.services.create.url()">
                    <Button label="Add Your First Service" icon="pi pi-plus" class="!bg-[#106B4F] !border-[#106B4F]" />
                </Link>
            </div>

            <!-- Services Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="service in services" :key="service.uuid"
                    class="bg-white rounded-xl shadow-sm overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="p-4 lg:p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-[#0D1F1B] m-0 mb-1 truncate">{{ service.name }}</h3>
                                <Tag v-if="service.category" :value="service.category.name" severity="secondary"
                                    class="!text-xs" />
                            </div>
                            <Tag :value="service.is_active ? 'Active' : 'Inactive'"
                                :severity="service.is_active ? 'success' : 'warn'" class="shrink-0 ml-2" />
                        </div>

                        <p v-if="service.description" class="text-sm text-gray-500 m-0 mb-3 line-clamp-2">
                            {{ service.description }}
                        </p>

                        <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                            <div class="flex items-center gap-1">
                                <i class="pi pi-clock text-xs"></i>
                                <span>{{ service.duration_display }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="font-semibold text-[#106B4F]">{{ service.price_display }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                            <Link :href="provider.services.edit.url(service.uuid)" class="flex-1">
                                <Button label="Edit" icon="pi pi-pencil" size="small" severity="secondary" outlined
                                    class="w-full" />
                            </Link>
                            <Button :icon="service.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'" size="small"
                                severity="secondary" outlined v-tooltip="service.is_active ? 'Deactivate' : 'Activate'"
                                @click="toggleActive(service)" />
                            <Button icon="pi pi-trash" size="small" severity="danger" outlined v-tooltip="'Delete'"
                                @click="deleteService(service)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

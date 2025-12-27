<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleEmptyState,
    ConsoleDataCard,
    ConsoleButton,
} from '@/components/console';
import AppLink from '@/components/common/AppLink.vue';
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
            <ConsolePageHeader
                title="Services"
                subtitle="Manage the services you offer to clients"
            >
                <template #actions>
                    <ConsoleButton
                        label="Add Service"
                        icon="pi pi-plus"
                        :href="provider.services.create.url()"
                    />
                </template>
            </ConsolePageHeader>

            <!-- Empty State -->
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

            <!-- Services Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <ConsoleDataCard
                    v-for="service in services"
                    :key="service.uuid"
                >
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-[#0D1F1B] m-0 mb-1 truncate">{{ service.name }}</h3>
                            <Tag
                                v-if="service.category"
                                :value="service.category.name"
                                severity="secondary"
                                class="!text-xs"
                            />
                        </div>
                        <Tag
                            :value="service.is_active ? 'Active' : 'Inactive'"
                            :severity="service.is_active ? 'success' : 'warn'"
                            class="shrink-0 ml-2"
                        />
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
                                :icon="service.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'"
                                size="small"
                                severity="secondary"
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

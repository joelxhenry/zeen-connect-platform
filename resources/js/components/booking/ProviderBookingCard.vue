<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import AppLink from '@/components/common/AppLink.vue';
import ProviderBookingController from '@/actions/App/Domains/Booking/Controllers/ProviderBookingController';
import type { Booking } from '@/types/models/booking';

interface Props {
    booking: Booking;
}

defineProps<Props>();

const emit = defineEmits<{
    confirm: [booking: Booking];
}>();

const getStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast' | undefined => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        case 'no_show': return 'secondary';
        default: return 'secondary';
    }
};

const getInitials = (name: string) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
        <div class="p-5">
            <div class="flex items-start gap-4">
                <!-- Client Avatar -->
                <Avatar
                    v-if="booking.client?.avatar"
                    :image="booking.client.avatar"
                    shape="circle"
                    class="!w-12 !h-12 shrink-0"
                />
                <Avatar
                    v-else
                    :label="getInitials(booking.client?.name ?? '')"
                    shape="circle"
                    class="!w-12 !h-12 shrink-0"
                    :class="booking.client?.is_guest ? '!bg-gray-400' : '!bg-[#106B4F]'"
                />

                <!-- Booking Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap justify-between items-start gap-2">
                        <div>
                            <h3 class="font-medium text-[#0D1F1B] m-0 flex items-center gap-2">
                                {{ booking.client?.name || 'Unknown Client' }}
                                <span v-if="booking.client?.is_guest" class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">
                                    Guest
                                </span>
                            </h3>
                            <p class="text-sm text-gray-500 m-0">{{ booking.service?.name }}</p>
                        </div>
                        <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                    </div>

                    <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <i class="pi pi-calendar"></i>
                            {{ booking.formatted_date }}
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="pi pi-clock"></i>
                            {{ booking.formatted_time }}
                        </span>
                        <span class="font-medium text-[#0D1F1B]">
                            {{ booking.total_display }}
                        </span>
                    </div>

                    <!-- Today Badge -->
                    <div
                        v-if="booking.is_today && !booking.is_past && booking.status === 'confirmed'"
                        class="flex items-center gap-2 mt-3 px-3 py-2 bg-[#106B4F]/10 rounded-lg text-[#106B4F] text-sm"
                    >
                        <i class="pi pi-star-fill"></i>
                        <span class="font-medium">Today</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-2 mt-4">
                        <Button
                            v-if="booking.can_confirm"
                            label="Confirm"
                            icon="pi pi-check"
                            size="small"
                            class="!bg-[#106B4F] !border-[#106B4F]"
                            @click.stop="emit('confirm', booking)"
                        />
                        <AppLink :href="ProviderBookingController.show({ uuid: booking.uuid }).url">
                            <Button
                                label="View Details"
                                icon="pi pi-arrow-right"
                                size="small"
                                outlined
                                class="!border-gray-300 !text-gray-700"
                            />
                        </AppLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

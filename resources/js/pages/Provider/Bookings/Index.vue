<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import ProviderBookingCard from '@/components/booking/ProviderBookingCard.vue';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import ProviderBookingController from '@/actions/App/Domains/Booking/Controllers/ProviderBookingController';

interface Client {
    name: string;
    email: string;
    phone?: string;
    avatar?: string;
    is_guest: boolean;
}

interface Booking {
    id: number;
    uuid: string;
    client: Client;
    service: {
        name: string;
        duration_minutes: number;
    };
    booking_date: string;
    formatted_date: string;
    formatted_time: string;
    status: string;
    status_label: string;
    status_color: string;
    total_display: string;
    client_notes?: string;
    is_past: boolean;
    is_today: boolean;
    can_confirm: boolean;
    can_complete: boolean;
    can_cancel: boolean;
    is_guest_booking: boolean;
}

interface Props {
    bookings: {
        data: Booking[];
        current_page: number;
        last_page: number;
        total: number;
    };
    currentStatus: string;
    currentDate: string | null;
    counts: {
        all: number;
        pending: number;
        confirmed: number;
        completed: number;
        cancelled: number;
    };
    statusOptions: Array<{ value: string; label: string; color: string }>;
}

const props = defineProps<Props>();
const toast = useToast();

const statusTabs = [
    { value: 'all', label: 'All', countKey: 'all' as const },
    { value: 'pending', label: 'Pending', countKey: 'pending' as const },
    { value: 'confirmed', label: 'Confirmed', countKey: 'confirmed' as const },
    { value: 'completed', label: 'Completed', countKey: 'completed' as const },
    { value: 'cancelled', label: 'Cancelled', countKey: 'cancelled' as const },
];

const switchTab = (status: string) => {
    router.get(ProviderBookingController.index().url, { status }, { preserveState: true });
};

const confirmBooking = (booking: Booking) => {
    router.post(ProviderBookingController.confirm({ uuid: booking.uuid }).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Booking Confirmed',
                detail: 'The booking has been confirmed.',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to confirm booking.',
                life: 3000,
            });
        },
    });
};
</script>

<template>
    <ConsoleLayout title="Bookings">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-[#0D1F1B] m-0">Bookings</h1>
                    <p class="text-gray-500 m-0 mt-1">Manage your appointments</p>
                </div>
            </div>

            <!-- Status Tabs -->
            <div class="flex gap-1 mb-6 border-b border-gray-200 overflow-x-auto">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    @click="switchTab(tab.value)"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-[1px] whitespace-nowrap flex items-center gap-2"
                    :class="currentStatus === tab.value
                        ? 'border-[#106B4F] text-[#106B4F]'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                >
                    {{ tab.label }}
                    <span
                        v-if="counts[tab.countKey] > 0"
                        class="px-2 py-0.5 text-xs rounded-full"
                        :class="currentStatus === tab.value
                            ? 'bg-[#106B4F] text-white'
                            : 'bg-gray-200 text-gray-600'"
                    >
                        {{ counts[tab.countKey] }}
                    </span>
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="bookings.data.length === 0" class="text-center py-16 bg-white rounded-xl shadow-sm">
                <i class="pi pi-calendar text-5xl text-gray-300 mb-4 block"></i>
                <h3 class="text-lg font-medium text-[#0D1F1B] m-0">No bookings found</h3>
                <p class="text-gray-500 mt-2">
                    {{ currentStatus === 'pending'
                        ? "You don't have any pending bookings"
                        : currentStatus === 'confirmed'
                            ? "You don't have any confirmed bookings"
                            : currentStatus === 'completed'
                                ? "You don't have any completed bookings"
                                : currentStatus === 'cancelled'
                                    ? "No cancelled bookings"
                                    : "You don't have any bookings yet"
                    }}
                </p>
            </div>

            <!-- Bookings List -->
            <div v-else class="space-y-4">
                <ProviderBookingCard
                    v-for="booking in bookings.data"
                    :key="booking.uuid"
                    :booking="booking"
                    @confirm="confirmBooking"
                />

                <!-- Pagination -->
                <div v-if="bookings.last_page > 1" class="flex justify-center gap-2 pt-4">
                    <Button
                        icon="pi pi-chevron-left"
                        :disabled="bookings.current_page === 1"
                        severity="secondary"
                        text
                        @click="router.get(ProviderBookingController.index().url, { status: currentStatus, page: bookings.current_page - 1 })"
                    />
                    <span class="flex items-center px-3 text-sm text-gray-500">
                        Page {{ bookings.current_page }} of {{ bookings.last_page }}
                    </span>
                    <Button
                        icon="pi pi-chevron-right"
                        :disabled="bookings.current_page === bookings.last_page"
                        severity="secondary"
                        text
                        @click="router.get(ProviderBookingController.index().url, { status: currentStatus, page: bookings.current_page + 1 })"
                    />
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

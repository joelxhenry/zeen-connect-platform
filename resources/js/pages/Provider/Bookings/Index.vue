<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleEmptyState,
    ConsoleButton,
} from '@/components/console';
import ProviderBookingCard from '@/components/booking/ProviderBookingCard.vue';
import { useToast } from 'primevue/usetoast';
import ProviderBookingController from '@/actions/App/Domains/Booking/Controllers/ProviderBookingController';
import type { Booking, BookingCounts, BookingStatusOption, PaginatedBookings } from '@/types/models/booking';

interface Props {
    bookings: PaginatedBookings;
    current_status: string;
    current_date: string | null;
    counts: BookingCounts;
    status_options: BookingStatusOption[];
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
            <ConsolePageHeader
                title="Bookings"
                subtitle="Manage your appointments"
            />

            <!-- Status Tabs -->
            <div class="flex gap-1 mb-6 border-b border-gray-200 overflow-x-auto">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    @click="switchTab(tab.value)"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-[1px] whitespace-nowrap flex items-center gap-2"
                    :class="current_status === tab.value
                        ? 'border-[#106B4F] text-[#106B4F]'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                >
                    {{ tab.label }}
                    <span
                        v-if="counts[tab.countKey] > 0"
                        class="px-2 py-0.5 text-xs rounded-full"
                        :class="current_status === tab.value
                            ? 'bg-[#106B4F] text-white'
                            : 'bg-gray-200 text-gray-600'"
                    >
                        {{ counts[tab.countKey] }}
                    </span>
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="bookings.data.length === 0" class="bg-white rounded-xl shadow-sm">
                <ConsoleEmptyState
                    icon="pi pi-calendar"
                    title="No bookings found"
                    :description="current_status === 'pending'
                        ? 'You don\'t have any pending bookings'
                        : current_status === 'confirmed'
                            ? 'You don\'t have any confirmed bookings'
                            : current_status === 'completed'
                                ? 'You don\'t have any completed bookings'
                                : current_status === 'cancelled'
                                    ? 'No cancelled bookings'
                                    : 'You don\'t have any bookings yet'"
                />
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
                    <ConsoleButton
                        icon="pi pi-chevron-left"
                        :disabled="bookings.current_page === 1"
                        variant="ghost"
                        @click="router.get(ProviderBookingController.index().url, { status: current_status, page: bookings.current_page - 1 })"
                    />
                    <span class="flex items-center px-3 text-sm text-gray-500">
                        Page {{ bookings.current_page }} of {{ bookings.last_page }}
                    </span>
                    <ConsoleButton
                        icon="pi pi-chevron-right"
                        :disabled="bookings.current_page === bookings.last_page"
                        variant="ghost"
                        @click="router.get(ProviderBookingController.index().url, { status: current_status, page: bookings.current_page + 1 })"
                    />
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

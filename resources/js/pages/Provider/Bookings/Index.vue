<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleEmptyState,
    ConsoleButton,
} from '@/components/console';
import ProviderBookingCard from '@/components/booking/ProviderBookingCard.vue';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import Badge from 'primevue/badge';
import { useToast } from 'primevue/usetoast';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
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
    { value: 'all', label: 'All', countKey: 'all' as const, icon: 'pi pi-list' },
    { value: 'pending', label: 'Pending', countKey: 'pending' as const, icon: 'pi pi-clock' },
    { value: 'confirmed', label: 'Confirmed', countKey: 'confirmed' as const, icon: 'pi pi-check' },
    { value: 'completed', label: 'Completed', countKey: 'completed' as const, icon: 'pi pi-check-circle' },
    { value: 'cancelled', label: 'Cancelled', countKey: 'cancelled' as const, icon: 'pi pi-times-circle' },
];

const activeTab = computed({
    get: () => props.current_status,
    set: (value: string) => {
        router.get(resolveUrl(provider.bookings.index().url), { status: value }, { preserveState: true });
    },
});

const confirmBooking = (booking: Booking) => {
    router.post(resolveUrl(provider.bookings.confirm(booking.uuid).url), {}, {
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
            <Tabs v-model:value="activeTab" class="bookings-tabs">
                <TabList>
                    <Tab
                        v-for="tab in statusTabs"
                        :key="tab.value"
                        :value="tab.value"
                    >
                        <i :class="[tab.icon, 'mr-2']"></i>
                        <span class="tab-label">{{ tab.label }}</span>
                        <Badge
                            v-if="counts[tab.countKey] > 0"
                            :value="counts[tab.countKey]"
                            :severity="tab.value === 'pending' ? 'warn' : 'secondary'"
                            class="ml-2"
                        />
                    </Tab>
                </TabList>
            </Tabs>

            <!-- Content -->
            <div class="bookings-content">
                <!-- Empty State -->
                <div v-if="bookings.data.length === 0" class="bookings-empty">
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
                <div v-else class="bookings-list">
                    <ProviderBookingCard
                        v-for="booking in bookings.data"
                        :key="booking.uuid"
                        :booking="booking"
                        @confirm="confirmBooking"
                    />

                    <!-- Pagination -->
                    <div v-if="bookings.last_page > 1" class="bookings-pagination">
                        <ConsoleButton
                            icon="pi pi-chevron-left"
                            :disabled="bookings.current_page === 1"
                            variant="ghost"
                            @click="router.get(resolveUrl(provider.bookings.index().url), { status: current_status, page: bookings.current_page - 1 })"
                        />
                        <span class="pagination-text">
                            Page {{ bookings.current_page }} of {{ bookings.last_page }}
                        </span>
                        <ConsoleButton
                            icon="pi pi-chevron-right"
                            :disabled="bookings.current_page === bookings.last_page"
                            variant="ghost"
                            @click="router.get(resolveUrl(provider.bookings.index().url), { status: current_status, page: bookings.current_page + 1 })"
                        />
                    </div>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.bookings-tabs {
    background: white;
    border-radius: 0.75rem 0.75rem 0 0;
    border: 1px solid #e5e7eb;
    border-bottom: none;
    overflow: hidden;
}

.bookings-tabs :deep(.p-tablist) {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    padding: 0.5rem 0.5rem 0;
    overflow-x: auto;
}

.bookings-tabs :deep(.p-tab) {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    border-radius: 0.5rem 0.5rem 0 0;
    margin-right: 0.25rem;
    border: none;
    background: transparent;
    white-space: nowrap;
}

.bookings-tabs :deep(.p-tab:hover) {
    color: #0D1F1B;
    background: rgba(16, 107, 79, 0.05);
}

.bookings-tabs :deep(.p-tab-active) {
    color: #106B4F;
    background: white;
    border: 1px solid #e5e7eb;
    border-bottom-color: white;
    margin-bottom: -1px;
}

.bookings-content {
    background: white;
    border-radius: 0 0 0.75rem 0.75rem;
    border: 1px solid #e5e7eb;
    border-top: none;
    padding: 1.5rem;
}

.bookings-empty {
    padding: 2rem 0;
}

.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.bookings-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    padding-top: 1.5rem;
    margin-top: 0.5rem;
    border-top: 1px solid #e5e7eb;
}

.pagination-text {
    font-size: 0.875rem;
    color: #6b7280;
    padding: 0 0.75rem;
}

@media (max-width: 640px) {
    .bookings-tabs :deep(.p-tab) {
        padding: 0.625rem 0.75rem;
        font-size: 0.8125rem;
    }

    .tab-label {
        display: none;
    }

    .bookings-tabs :deep(.p-tab i) {
        margin-right: 0;
    }

    .bookings-content {
        padding: 1rem;
    }
}
</style>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import DashboardLayout from '@/components/layout/DashboardLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

interface Booking {
    id: number;
    uuid: string;
    provider: {
        business_name: string;
        slug: string;
        avatar?: string;
    };
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
    is_past: boolean;
    is_today: boolean;
    can_cancel: boolean;
    requires_deposit: boolean;
    deposit_amount: number;
    deposit_paid: boolean;
    can_pay: boolean;
}

interface Props {
    bookings: {
        data: Booking[];
        current_page: number;
        last_page: number;
        total: number;
    };
    currentStatus: string;
}

const props = defineProps<Props>();

const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'past', label: 'Past' },
];

const switchTab = (status: string) => {
    router.get('/dashboard/bookings', { status }, { preserveState: true });
};

const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'success';
        case 'completed': return 'info';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <DashboardLayout title="My Bookings">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-[#0D1F1B] m-0">My Bookings</h1>
                    <p class="text-gray-500 m-0 mt-1">View and manage your appointments</p>
                </div>
                <AppLink href="/explore">
                    <Button label="Book New" icon="pi pi-plus" class="!bg-[#106B4F] !border-[#106B4F]" />
                </AppLink>
            </div>

            <!-- Status Tabs -->
            <div class="flex gap-2 mb-6 border-b border-gray-200">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    @click="switchTab(tab.value)"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-[1px]"
                    :class="currentStatus === tab.value
                        ? 'border-[#106B4F] text-[#106B4F]'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="bookings.data.length === 0" class="text-center py-16 bg-white rounded-xl shadow-sm">
                <i class="pi pi-calendar text-5xl text-gray-300 mb-4 block"></i>
                <h3 class="text-lg font-medium text-[#0D1F1B] m-0">No bookings found</h3>
                <p class="text-gray-500 mt-2">
                    {{ currentStatus === 'upcoming'
                        ? "You don't have any upcoming appointments"
                        : currentStatus === 'past'
                            ? "You don't have any past appointments"
                            : "You haven't made any bookings yet"
                    }}
                </p>
                <AppLink href="/explore" class="inline-block mt-4">
                    <Button label="Find Services" icon="pi pi-search" outlined class="!border-[#106B4F] !text-[#106B4F]" />
                </AppLink>
            </div>

            <!-- Bookings List -->
            <div v-else class="space-y-4">
                <AppLink
                    v-for="booking in bookings.data"
                    :key="booking.uuid"
                    :href="`/dashboard/bookings/${booking.uuid}`"
                    class="block bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow no-underline"
                >
                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <!-- Provider Avatar -->
                            <Avatar
                                v-if="booking.provider.avatar"
                                :image="booking.provider.avatar"
                                shape="circle"
                                class="!w-12 !h-12 shrink-0"
                            />
                            <Avatar
                                v-else
                                :label="getInitials(booking.provider.business_name)"
                                shape="circle"
                                class="!w-12 !h-12 !bg-[#106B4F] shrink-0"
                            />

                            <!-- Booking Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap justify-between items-start gap-2">
                                    <div>
                                        <h3 class="font-medium text-[#0D1F1B] m-0">{{ booking.service.name }}</h3>
                                        <p class="text-sm text-gray-500 m-0">{{ booking.provider.business_name }}</p>
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

                                <!-- Payment Alert -->
                                <div
                                    v-if="booking.can_pay"
                                    class="flex items-center gap-2 mt-3 px-3 py-2 bg-yellow-50 rounded-lg text-yellow-700 text-sm"
                                >
                                    <i class="pi pi-exclamation-circle"></i>
                                    <span>Deposit payment required - ${{ booking.deposit_amount.toFixed(2) }}</span>
                                    <AppLink
                                        :href="`/payment/${booking.uuid}/checkout`"
                                        @click.stop
                                        class="ml-auto font-medium text-yellow-800 hover:underline"
                                    >
                                        Pay Now
                                    </AppLink>
                                </div>

                                <!-- Today Badge -->
                                <div
                                    v-if="booking.is_today && !booking.is_past"
                                    class="flex items-center gap-2 mt-3 px-3 py-2 bg-[#106B4F]/10 rounded-lg text-[#106B4F] text-sm"
                                >
                                    <i class="pi pi-star-fill"></i>
                                    <span class="font-medium">Today</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </AppLink>

                <!-- Pagination -->
                <div v-if="bookings.last_page > 1" class="flex justify-center gap-2 pt-4">
                    <Button
                        icon="pi pi-chevron-left"
                        :disabled="bookings.current_page === 1"
                        severity="secondary"
                        text
                        @click="router.get('/dashboard/bookings', { status: currentStatus, page: bookings.current_page - 1 })"
                    />
                    <span class="flex items-center px-3 text-sm text-gray-500">
                        Page {{ bookings.current_page }} of {{ bookings.last_page }}
                    </span>
                    <Button
                        icon="pi pi-chevron-right"
                        :disabled="bookings.current_page === bookings.last_page"
                        severity="secondary"
                        text
                        @click="router.get('/dashboard/bookings', { status: currentStatus, page: bookings.current_page + 1 })"
                    />
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

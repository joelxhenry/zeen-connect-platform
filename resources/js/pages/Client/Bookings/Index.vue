<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ClientLayout from '@/components/layout/ClientLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { PaginatedBookings } from '@/types/models/booking';
import provider from '@/routes/provider';
import providersite, { book } from '@/routes/providersite';

interface Props {
    bookings: PaginatedBookings;
    currentStatus: string;
}

const props = defineProps<Props>();

const statusTabs = [
    { value: 'all', label: 'All', icon: 'pi pi-list' },
    { value: 'upcoming', label: 'Upcoming', icon: 'pi pi-calendar' },
    { value: 'past', label: 'Past', icon: 'pi pi-history' },
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

const getRelativeDate = (dateStr: string) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const date = new Date(dateStr);
    date.setHours(0, 0, 0, 0);
    const diffDays = Math.ceil((date.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Tomorrow';
    if (diffDays === -1) return 'Yesterday';
    if (diffDays < 0 && diffDays > -7) return `${Math.abs(diffDays)} days ago`;
    return null;
};

const emptyStateContent = computed(() => {
    switch (props.currentStatus) {
        case 'upcoming':
            return {
                icon: 'pi pi-calendar-plus',
                title: 'No upcoming appointments',
                message: 'Your next adventure awaits! Browse services and book something special.',
                cta: 'Find Services'
            };
        case 'past':
            return {
                icon: 'pi pi-history',
                title: 'No past appointments',
                message: 'Your booking history will appear here once you complete your first appointment.',
                cta: 'Book Your First'
            };
        default:
            return {
                icon: 'pi pi-calendar',
                title: 'No bookings yet',
                message: 'Start your journey by discovering amazing services and providers.',
                cta: 'Explore Services'
            };
    }
});
</script>

<template>
    <ClientLayout title="My Bookings">
        <div class="min-h-screen bg-gray-50/50">
            <div class="max-w-3xl mx-auto px-4 py-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-[#0D1F1B] m-0">My Bookings</h1>
                            <p class="text-gray-500 mt-1 m-0">Manage your appointments</p>
                        </div>
                        <AppLink href="/explore">
                            <Button label="Book New" icon="pi pi-plus"
                                class="!bg-[#106B4F] !border-[#106B4F] !rounded-full !px-5" />
                        </AppLink>
                    </div>
                </div>

                <!-- Filter Pills -->
                <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
                    <button v-for="tab in statusTabs" :key="tab.value" @click="switchTab(tab.value)"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap transition-all"
                        :class="currentStatus === tab.value
                            ? 'bg-[#106B4F] text-white shadow-sm'
                            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'">
                        <i :class="tab.icon" class="text-xs"></i>
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="bookings.data.length === 0"
                    class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                    <div
                        class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-[#106B4F]/10 to-[#106B4F]/5 flex items-center justify-center">
                        <i :class="emptyStateContent.icon" class="text-3xl text-[#106B4F]"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-[#0D1F1B] m-0">{{ emptyStateContent.title }}</h2>
                    <p class="text-gray-500 mt-2 mb-6 max-w-sm mx-auto">{{ emptyStateContent.message }}</p>
                    <AppLink href="/explore">
                        <Button :label="emptyStateContent.cta" icon="pi pi-search"
                            class="!bg-[#106B4F] !border-[#106B4F] !rounded-full !px-6" />
                    </AppLink>
                </div>

                <!-- Bookings List -->
                <div v-else class="space-y-3">
                    <AppLink v-for="booking in bookings.data" :key="booking.uuid"
                        :href="`/dashboard/bookings/${booking.uuid}`"
                        class="block bg-white rounded-2xl border border-gray-100 overflow-hidden hover:border-[#106B4F]/30 hover:shadow-md transition-all no-underline group">
                        <!-- Today/Tomorrow Badge -->
                        <div v-if="getRelativeDate(booking.booking_date) && !booking.is_past"
                            class="px-5 py-2 bg-gradient-to-r from-[#106B4F] to-[#0D5A42] text-white text-sm font-medium">
                            <i class="pi pi-star-fill mr-2 text-xs"></i>
                            {{ getRelativeDate(booking.booking_date) }}
                        </div>

                        <div class="p-5">
                            <div class="flex items-start gap-4">
                                <!-- Provider Avatar -->
                                <div class="relative">
                                    <Avatar v-if="booking.provider?.avatar" :image="booking.provider.avatar"
                                        shape="circle" class="!w-14 !h-14" />
                                    <Avatar v-else :label="getInitials(booking.provider?.business_name || '')"
                                        shape="circle" class="!w-14 !h-14 !bg-[#106B4F] !text-white" />
                                </div>

                                <!-- Booking Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap justify-between items-start gap-2 mb-2">
                                        <div>
                                            <h3
                                                class="font-semibold text-[#0D1F1B] m-0 text-lg group-hover:text-[#106B4F] transition-colors">
                                                {{ booking.service?.name }}
                                            </h3>
                                            <p class="text-gray-500 m-0 text-sm">{{ booking.provider?.business_name }}
                                            </p>
                                        </div>
                                        <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)"
                                            class="!rounded-full" />
                                    </div>

                                    <div class="flex flex-wrap items-center gap-4 text-sm">
                                        <span class="flex items-center gap-1.5 text-gray-600">
                                            <i class="pi pi-calendar text-[#106B4F]"></i>
                                            {{ booking.formatted_date }}
                                        </span>
                                        <span class="flex items-center gap-1.5 text-gray-600">
                                            <i class="pi pi-clock text-[#106B4F]"></i>
                                            {{ booking.formatted_time }}
                                        </span>
                                        <span class="font-semibold text-[#0D1F1B] ml-auto">
                                            {{ booking.total_display }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Arrow -->
                                <div
                                    class="hidden sm:flex items-center self-center text-gray-300 group-hover:text-[#106B4F] transition-colors">
                                    <i class="pi pi-chevron-right"></i>
                                </div>
                            </div>

                            <!-- Payment Alert -->
                            <div v-if="booking.can_pay"
                                class="flex items-center gap-3 mt-4 px-4 py-3 bg-amber-50 rounded-xl border border-amber-100">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                                    <i class="pi pi-credit-card text-amber-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-amber-800 m-0 text-sm">Deposit Required</p>
                                    <p class="text-amber-600 m-0 text-xs">${{ booking.deposit_amount.toFixed(2) }} to
                                        confirm your booking</p>
                                </div>
                                <AppLink :href="providersite.payment.checkout({
                                    provider: booking.provider?.domain ?? '',
                                    bookingUuid: booking.uuid
                                }).url" @click.stop class="shrink-0">
                                    <Button label="Pay" size="small"
                                        class="!bg-amber-500 !border-amber-500 !rounded-full !px-4" />
                                </AppLink>
                            </div>
                        </div>
                    </AppLink>

                    <!-- Pagination -->
                    <div v-if="bookings.last_page > 1" class="flex justify-center items-center gap-1 pt-6">
                        <Button icon="pi pi-chevron-left" :disabled="bookings.current_page === 1" text rounded
                            class="!w-10 !h-10"
                            @click="router.get('/dashboard/bookings', { status: currentStatus, page: bookings.current_page - 1 })" />
                        <div class="flex items-center gap-1 px-2">
                            <template v-for="page in bookings.last_page" :key="page">
                                <button
                                    v-if="page === 1 || page === bookings.last_page || (page >= bookings.current_page - 1 && page <= bookings.current_page + 1)"
                                    @click="router.get('/dashboard/bookings', { status: currentStatus, page })"
                                    class="w-10 h-10 rounded-full text-sm font-medium transition-colors" :class="page === bookings.current_page
                                        ? 'bg-[#106B4F] text-white'
                                        : 'text-gray-600 hover:bg-gray-100'">
                                    {{ page }}
                                </button>
                                <span v-else-if="page === 2 || page === bookings.last_page - 1"
                                    class="text-gray-400 px-1">
                                    ...
                                </span>
                            </template>
                        </div>
                        <Button icon="pi pi-chevron-right" :disabled="bookings.current_page === bookings.last_page" text
                            rounded class="!w-10 !h-10"
                            @click="router.get('/dashboard/bookings', { status: currentStatus, page: bookings.current_page + 1 })" />
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>

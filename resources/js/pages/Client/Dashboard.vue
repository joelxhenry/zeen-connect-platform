<script setup lang="ts">
import { computed } from 'vue';
import ClientLayout from '@/components/layout/ClientLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { Booking } from '@/types/models/booking';

interface Stats {
    upcoming: number;
    completed: number;
    reviews: number;
}

interface Props {
    userName: string;
    stats: Stats;
    nextBooking: Booking | null;
    upcomingBookings: Booking[];
    recentBookings: Booking[];
    pendingReviews: Booking[];
}

const props = defineProps<Props>();

const firstName = computed(() => props.userName.split(' ')[0]);

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
    if (diffDays < 7) return `In ${diffDays} days`;
    return dateStr;
};
</script>

<template>
    <ClientLayout title="Dashboard">
        <div class="min-h-screen bg-gray-50/50">
            <div class="max-w-5xl mx-auto px-4 py-8">
                <!-- Welcome Section -->
                <div class="mb-8">
                    <h1 class="text-2xl font-semibold text-[#0D1F1B] m-0">
                        Welcome back, {{ firstName }}!
                    </h1>
                    <p class="text-gray-500 mt-1 m-0">Here's what's coming up</p>
                </div>

                <!-- Hero: Next Appointment Card -->
                <div v-if="nextBooking" class="mb-8">
                    <div class="bg-gradient-to-br from-[#106B4F] to-[#0D5A42] rounded-2xl p-6 text-white shadow-lg">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <Avatar
                                    v-if="nextBooking.provider?.avatar"
                                    :image="nextBooking.provider.avatar"
                                    shape="circle"
                                    class="!w-14 !h-14 !border-2 !border-white/30"
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(nextBooking.provider?.business_name || '')"
                                    shape="circle"
                                    class="!w-14 !h-14 !bg-white/20 !text-white"
                                />
                                <div>
                                    <p class="text-white/70 text-sm m-0 mb-1">Next Appointment</p>
                                    <h2 class="text-xl font-semibold m-0">{{ nextBooking.service?.name }}</h2>
                                    <p class="text-white/80 m-0 mt-1">{{ nextBooking.provider?.business_name }}</p>
                                </div>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-2xl font-bold m-0">{{ getRelativeDate(nextBooking.booking_date) }}</p>
                                <p class="text-white/80 m-0">{{ nextBooking.formatted_time }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-white/20">
                            <AppLink :href="`/dashboard/bookings/${nextBooking.uuid}`">
                                <Button
                                    label="View Details"
                                    icon="pi pi-eye"
                                    class="!bg-white !text-[#106B4F] !border-white hover:!bg-white/90"
                                />
                            </AppLink>
                            <AppLink v-if="nextBooking.provider?.address" :href="`https://maps.google.com/?q=${encodeURIComponent(nextBooking.provider.address)}`" target="_blank">
                                <Button
                                    label="Get Directions"
                                    icon="pi pi-map-marker"
                                    outlined
                                    class="!border-white/50 !text-white hover:!bg-white/10"
                                />
                            </AppLink>
                        </div>
                    </div>
                </div>

                <!-- Empty State: No Next Appointment -->
                <div v-else class="mb-8">
                    <div class="bg-gradient-to-br from-gray-100 to-gray-50 rounded-2xl p-8 text-center border border-gray-200">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[#106B4F]/10 flex items-center justify-center">
                            <i class="pi pi-calendar-plus text-[#106B4F] text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-[#0D1F1B] m-0">Ready for your next appointment?</h2>
                        <p class="text-gray-500 mt-2 mb-4">Browse services and book with top providers near you</p>
                        <AppLink href="/explore">
                            <Button label="Find Services" icon="pi pi-search" class="!bg-[#106B4F] !border-[#106B4F]" />
                        </AppLink>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="grid grid-cols-3 gap-4 mb-8">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[#106B4F]/10 flex items-center justify-center shrink-0">
                                <i class="pi pi-calendar text-[#106B4F]"></i>
                            </div>
                            <div>
                                <p class="text-xl font-semibold text-[#0D1F1B] m-0">{{ stats.upcoming }}</p>
                                <p class="text-xs text-gray-500 m-0">Upcoming</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                                <i class="pi pi-check-circle text-blue-500"></i>
                            </div>
                            <div>
                                <p class="text-xl font-semibold text-[#0D1F1B] m-0">{{ stats.completed }}</p>
                                <p class="text-xs text-gray-500 m-0">Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                                <i class="pi pi-star text-amber-500"></i>
                            </div>
                            <div>
                                <p class="text-xl font-semibold text-[#0D1F1B] m-0">{{ stats.reviews }}</p>
                                <p class="text-xs text-gray-500 m-0">Reviews</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Upcoming Appointments -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-semibold text-[#0D1F1B] m-0">Upcoming</h3>
                            <AppLink href="/dashboard/bookings?status=upcoming" class="text-sm text-[#106B4F] hover:underline no-underline">
                                View all
                            </AppLink>
                        </div>
                        <div v-if="upcomingBookings.length === 0" class="p-8 text-center">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                                <i class="pi pi-calendar text-gray-400"></i>
                            </div>
                            <p class="text-gray-500 m-0 text-sm">Your schedule is clear</p>
                            <AppLink href="/explore" class="text-[#106B4F] text-sm hover:underline">
                                Browse services
                            </AppLink>
                        </div>
                        <div v-else class="divide-y divide-gray-50">
                            <AppLink
                                v-for="booking in upcomingBookings.slice(0, 4)"
                                :key="booking.uuid"
                                :href="`/dashboard/bookings/${booking.uuid}`"
                                class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 no-underline transition-colors"
                            >
                                <Avatar
                                    v-if="booking.provider?.avatar"
                                    :image="booking.provider.avatar"
                                    shape="circle"
                                    class="!w-9 !h-9 shrink-0"
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(booking.provider?.business_name || '')"
                                    shape="circle"
                                    class="!w-9 !h-9 !bg-[#106B4F] !text-xs shrink-0"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-[#0D1F1B] m-0 text-sm truncate">{{ booking.service?.name }}</p>
                                    <p class="text-xs text-gray-500 m-0">{{ booking.formatted_date }}</p>
                                </div>
                                <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" class="!text-xs" />
                            </AppLink>
                        </div>
                    </div>

                    <!-- Pending Reviews -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-[#0D1F1B] m-0">Leave a Review</h3>
                        </div>
                        <div v-if="pendingReviews.length === 0" class="p-8 text-center">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-50 flex items-center justify-center">
                                <i class="pi pi-check text-emerald-500"></i>
                            </div>
                            <p class="text-gray-600 font-medium m-0">All caught up!</p>
                            <p class="text-gray-400 text-sm m-0 mt-1">No pending reviews</p>
                        </div>
                        <div v-else class="divide-y divide-gray-50">
                            <div
                                v-for="booking in pendingReviews"
                                :key="booking.uuid"
                                class="flex items-center justify-between gap-3 px-5 py-3"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium text-[#0D1F1B] m-0 text-sm truncate">{{ booking.service?.name }}</p>
                                    <p class="text-xs text-gray-500 m-0">{{ booking.provider?.business_name }}</p>
                                </div>
                                <AppLink :href="`/dashboard/bookings/${booking.uuid}/review`">
                                    <Button label="Review" icon="pi pi-star" size="small" outlined class="!border-[#106B4F] !text-[#106B4F] !text-xs" />
                                </AppLink>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="font-semibold text-[#0D1F1B] m-0">Recent Activity</h3>
                        <AppLink href="/dashboard/bookings" class="text-sm text-[#106B4F] hover:underline no-underline">
                            View all
                        </AppLink>
                    </div>
                    <div v-if="recentBookings.length === 0" class="p-8 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                            <i class="pi pi-history text-gray-400"></i>
                        </div>
                        <p class="text-gray-500 m-0 text-sm">Your booking history will appear here</p>
                    </div>
                    <div v-else class="divide-y divide-gray-50">
                        <AppLink
                            v-for="booking in recentBookings"
                            :key="booking.uuid"
                            :href="`/dashboard/bookings/${booking.uuid}`"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 no-underline transition-colors"
                        >
                            <Avatar
                                v-if="booking.provider?.avatar"
                                :image="booking.provider.avatar"
                                shape="circle"
                                class="!w-9 !h-9 shrink-0"
                            />
                            <Avatar
                                v-else
                                :label="getInitials(booking.provider?.business_name || '')"
                                shape="circle"
                                class="!w-9 !h-9 !bg-[#106B4F] !text-xs shrink-0"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-[#0D1F1B] m-0 text-sm truncate">{{ booking.service?.name }}</p>
                                <p class="text-xs text-gray-500 m-0">{{ booking.provider?.business_name }} · {{ booking.formatted_date }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" class="!text-xs" />
                                <p class="text-xs font-medium text-[#0D1F1B] m-0 mt-1">{{ booking.total_display }}</p>
                            </div>
                        </AppLink>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>

<script setup lang="ts">
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import provider from '@/routes/provider';

interface Props {
    provider: {
        business_name: string;
        tagline: string | null;
        rating_avg: number | null;
        rating_count: number;
    };
    stats: {
        totalEarnings: number;
        pendingPayout: number;
        completedBookings: number;
        activeServices: number;
        pendingBookings: number;
    };
    upcomingBookings: Array<{
        uuid: string;
        client: { name: string; avatar?: string };
        service: { name: string };
        date: string;
        time: string;
        total_amount: string;
        status: string;
        status_label: string;
        status_color: string;
    }>;
    recentPayments: Array<{
        uuid: string;
        amount: string;
        service_name: string;
        date: string;
    }>;
    recentReviews: Array<{
        uuid: string;
        rating: number;
        comment: string;
        client: { name: string };
        service_name: string | null;
        date: string;
        has_response: boolean;
    }>;
    unrespondedReviewsCount: number;
}

const props = defineProps<Props>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-JM', {
        style: 'currency',
        currency: 'JMD',
        minimumFractionDigits: 0,
    }).format(amount);
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
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
</script>

<template>
    <ConsoleLayout title="Dashboard">
        <div class="w-full max-w-7xl mx-auto">
            <!-- Welcome Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 sm:gap-0 mb-6">
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-[#0D1F1B] m-0 mb-1">Welcome back, {{ provider.business_name }}</h1>
                    <p v-if="provider.tagline" class="text-gray-500 m-0 text-sm lg:text-base">{{ provider.tagline }}</p>
                </div>
                <Tag v-if="provider.rating_count > 0" severity="warn" class="!px-3 !py-2 !text-sm">
                    <i class="pi pi-star-fill mr-1"></i>
                    {{ provider.rating_avg?.toFixed(1) }}
                    <span class="font-normal ml-1">({{ provider.rating_count }} reviews)</span>
                </Tag>
            </div>

            <!-- Alert Banner for Pending Bookings -->
            <div v-if="stats.pendingBookings > 0" class="flex flex-wrap items-center gap-3 px-4 py-3 bg-yellow-500/10 border border-yellow-500/30 rounded-lg mb-6 text-yellow-800">
                <i class="pi pi-info-circle text-lg"></i>
                <span class="flex-1">You have <strong>{{ stats.pendingBookings }}</strong> pending booking{{ stats.pendingBookings > 1 ? 's' : '' }} awaiting confirmation</span>
                <AppLink :href="provider.bookings.index.url({ query: { status: 'pending' }})" class="font-medium text-yellow-800 no-underline hover:underline">View All</AppLink>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 lg:p-5 shadow-sm">
                    <div class="flex items-center gap-3 lg:gap-4">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl flex items-center justify-center text-lg lg:text-xl bg-[#106B4F]/10 text-[#106B4F]">
                            <i class="pi pi-wallet"></i>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-base lg:text-xl font-semibold text-[#0D1F1B] truncate">{{ formatCurrency(stats.totalEarnings) }}</span>
                            <span class="text-xs lg:text-[13px] text-gray-500">Total Earnings</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-4 lg:p-5 shadow-sm">
                    <div class="flex items-center gap-3 lg:gap-4">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl flex items-center justify-center text-lg lg:text-xl bg-yellow-500/10 text-yellow-600">
                            <i class="pi pi-clock"></i>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-base lg:text-xl font-semibold text-[#0D1F1B] truncate">{{ formatCurrency(stats.pendingPayout) }}</span>
                            <span class="text-xs lg:text-[13px] text-gray-500">Pending Payout</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-4 lg:p-5 shadow-sm">
                    <div class="flex items-center gap-3 lg:gap-4">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl flex items-center justify-center text-lg lg:text-xl bg-[#1ABC9C]/10 text-[#1ABC9C]">
                            <i class="pi pi-check-circle"></i>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-base lg:text-xl font-semibold text-[#0D1F1B]">{{ stats.completedBookings }}</span>
                            <span class="text-xs lg:text-[13px] text-gray-500">Completed</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-4 lg:p-5 shadow-sm">
                    <div class="flex items-center gap-3 lg:gap-4">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl flex items-center justify-center text-lg lg:text-xl bg-[#5B5BD6]/10 text-[#5B5BD6]">
                            <i class="pi pi-th-large"></i>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-base lg:text-xl font-semibold text-[#0D1F1B]">{{ stats.activeServices }}</span>
                            <span class="text-xs lg:text-[13px] text-gray-500">Services</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid - 3 column on xl -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6 mb-6">
                <!-- Upcoming Bookings - Takes 2 columns on large screens -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="flex justify-between items-center px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                        <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0">Upcoming Bookings</h2>
                        <AppLink :href="provider.bookings.index.url()" class="text-xs lg:text-sm text-[#106B4F] no-underline hover:underline">View All</AppLink>
                    </div>
                    <div class="p-4 lg:p-5">
                        <div v-if="upcomingBookings.length === 0" class="text-center py-12 text-gray-400">
                            <i class="pi pi-calendar text-4xl mb-3 block"></i>
                            <p class="m-0 text-sm">No upcoming bookings</p>
                            <AppLink :href="provider.availability.edit.url()" class="text-sm text-[#106B4F] no-underline hover:underline mt-2 inline-block">Set your availability</AppLink>
                        </div>
                        <div v-else class="grid gap-3">
                            <div v-for="booking in upcomingBookings" :key="booking.uuid" class="flex flex-wrap items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <Avatar
                                    v-if="booking.client.avatar"
                                    :image="booking.client.avatar"
                                    shape="circle"
                                    class="!w-10 !h-10 shrink-0"
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(booking.client.name)"
                                    shape="circle"
                                    class="!w-10 !h-10 !bg-[#106B4F] shrink-0"
                                />
                                <div class="flex-1 min-w-0">
                                    <span class="block font-medium text-[#0D1F1B] text-sm truncate">{{ booking.client.name }}</span>
                                    <span class="block text-xs text-gray-500 truncate">{{ booking.service.name }}</span>
                                </div>
                                <div class="text-right hidden sm:block">
                                    <span class="block text-[13px] font-medium text-[#0D1F1B]">{{ booking.date }}</span>
                                    <span class="block text-xs text-gray-500">{{ booking.time }}</span>
                                </div>
                                <Tag :severity="getStatusSeverity(booking.status)" :value="booking.status_label" rounded class="shrink-0" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Activity -->
                <div class="space-y-4 lg:space-y-6">
                    <!-- Recent Payments -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="flex justify-between items-center px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                            <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                                <i class="pi pi-wallet text-[#106B4F]"></i>
                                Payments
                            </h2>
                            <AppLink :href="provider.payments.index.url()" class="text-xs lg:text-sm text-[#106B4F] no-underline hover:underline">View All</AppLink>
                        </div>
                        <div class="p-4 lg:p-5">
                            <div v-if="recentPayments.length === 0" class="text-center py-6 text-gray-400">
                                <p class="m-0 text-sm">No payments yet</p>
                            </div>
                            <div v-else class="space-y-2">
                                <div v-for="(payment, index) in recentPayments.slice(0, 4)" :key="payment.uuid" class="flex justify-between items-center py-2" :class="{ 'border-b border-gray-100': index !== Math.min(recentPayments.length, 4) - 1 }">
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-sm text-[#0D1F1B] truncate">{{ payment.service_name }}</span>
                                        <span class="text-xs text-gray-400">{{ payment.date }}</span>
                                    </div>
                                    <span class="font-semibold text-[#106B4F] shrink-0 ml-2">+${{ payment.amount }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Reviews -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="flex justify-between items-center px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                            <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                                <i class="pi pi-star text-yellow-500"></i>
                                Reviews
                                <Tag v-if="unrespondedReviewsCount > 0" severity="danger" :value="`${unrespondedReviewsCount}`" rounded class="!text-[10px] !px-1.5 !py-0.5 !min-w-[20px]" />
                            </h2>
                            <AppLink :href="provider.reviews.index.url()" class="text-xs lg:text-sm text-[#106B4F] no-underline hover:underline">View All</AppLink>
                        </div>
                        <div class="p-4 lg:p-5">
                            <div v-if="recentReviews.length === 0" class="text-center py-6 text-gray-400">
                                <p class="m-0 text-sm">No reviews yet</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="review in recentReviews.slice(0, 3)" :key="review.uuid" class="pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-medium text-sm text-[#0D1F1B]">{{ review.client.name }}</span>
                                        <div class="flex gap-0.5">
                                            <i v-for="i in 5" :key="i" class="pi text-xs" :class="i <= review.rating ? 'pi-star-fill text-yellow-500' : 'pi-star text-gray-300'"></i>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 m-0 leading-relaxed line-clamp-2">{{ review.comment }}</p>
                                    <Tag v-if="!review.has_response" severity="danger" value="Needs response" class="!text-[10px] !px-1.5 !py-0.5 mt-1.5" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions - Full width bottom bar -->
            <div class="bg-white rounded-xl p-4 lg:p-5 shadow-sm">
                <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 lg:gap-3">
                    <AppLink :href="provider.services.create.url()" class="flex items-center justify-center gap-2 py-3 px-4 bg-[#106B4F] text-white rounded-lg text-sm font-medium no-underline hover:bg-[#0D5A42] transition-colors">
                        <i class="pi pi-plus"></i>
                        <span class="hidden sm:inline">Add Service</span>
                        <span class="sm:hidden">Service</span>
                    </AppLink>
                    <AppLink :href="provider.availability.edit.url()" class="flex items-center justify-center gap-2 py-3 px-4 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium no-underline hover:bg-gray-200 transition-colors">
                        <i class="pi pi-clock"></i>
                        <span class="hidden sm:inline">Availability</span>
                        <span class="sm:hidden">Hours</span>
                    </AppLink>
                    <AppLink :href="provider.profile.edit.url()" class="flex items-center justify-center gap-2 py-3 px-4 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium no-underline hover:bg-gray-200 transition-colors">
                        <i class="pi pi-user"></i>
                        Profile
                    </AppLink>
                    <AppLink :href="provider.bookings.index.url()" class="flex items-center justify-center gap-2 py-3 px-4 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium no-underline hover:bg-gray-200 transition-colors">
                        <i class="pi pi-calendar"></i>
                        Bookings
                    </AppLink>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

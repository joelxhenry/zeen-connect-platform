<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';

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
        <div>
            <!-- Welcome Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 sm:gap-0 mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-[#0D1F1B] m-0 mb-1">Welcome back, {{ provider.business_name }}</h1>
                    <p v-if="provider.tagline" class="text-gray-500 m-0">{{ provider.tagline }}</p>
                </div>
                <Tag v-if="provider.rating_count > 0" severity="warn" class="!px-3 !py-2 !text-sm">
                    <i class="pi pi-star-fill mr-1"></i>
                    {{ provider.rating_avg?.toFixed(1) }}
                    <span class="font-normal ml-1">({{ provider.rating_count }} reviews)</span>
                </Tag>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Card class="!shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-[#106B4F]/10 text-[#106B4F]">
                                <i class="pi pi-wallet"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xl font-semibold text-[#0D1F1B]">{{ formatCurrency(stats.totalEarnings) }}</span>
                                <span class="text-[13px] text-gray-500">Total Earnings</span>
                            </div>
                        </div>
                    </template>
                </Card>
                <Card class="!shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-yellow-500/10 text-yellow-600">
                                <i class="pi pi-clock"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xl font-semibold text-[#0D1F1B]">{{ formatCurrency(stats.pendingPayout) }}</span>
                                <span class="text-[13px] text-gray-500">Pending Payout</span>
                            </div>
                        </div>
                    </template>
                </Card>
                <Card class="!shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-[#1ABC9C]/10 text-[#1ABC9C]">
                                <i class="pi pi-check-circle"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xl font-semibold text-[#0D1F1B]">{{ stats.completedBookings }}</span>
                                <span class="text-[13px] text-gray-500">Completed Bookings</span>
                            </div>
                        </div>
                    </template>
                </Card>
                <Card class="!shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-[#5B5BD6]/10 text-[#5B5BD6]">
                                <i class="pi pi-th-large"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xl font-semibold text-[#0D1F1B]">{{ stats.activeServices }}</span>
                                <span class="text-[13px] text-gray-500">Active Services</span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Alert Banner for Pending Bookings -->
            <div v-if="stats.pendingBookings > 0" class="flex items-center gap-3 px-4 py-3.5 bg-yellow-500/10 border border-yellow-500/30 rounded-lg mb-6 text-yellow-800">
                <i class="pi pi-info-circle text-lg"></i>
                <span>You have <strong>{{ stats.pendingBookings }}</strong> pending booking{{ stats.pendingBookings > 1 ? 's' : '' }} awaiting confirmation</span>
                <Link href="/console/bookings?status=pending" class="ml-auto font-medium text-yellow-800 no-underline hover:underline">View All</Link>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Upcoming Bookings -->
                <Card class="!shadow-sm !overflow-hidden">
                    <template #header>
                        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Upcoming Bookings</h2>
                            <Link href="/console/bookings" class="text-sm text-[#106B4F] no-underline hover:underline">View All</Link>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="upcomingBookings.length === 0" class="text-center py-8 px-4 text-gray-400">
                            <i class="pi pi-calendar text-3xl mb-2 block"></i>
                            <p class="m-0">No upcoming bookings</p>
                        </div>
                        <div v-else class="flex flex-col gap-3">
                            <div v-for="booking in upcomingBookings" :key="booking.uuid" class="flex flex-wrap items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <Avatar
                                    v-if="booking.client.avatar"
                                    :image="booking.client.avatar"
                                    shape="circle"
                                    class="!w-10 !h-10"
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(booking.client.name)"
                                    shape="circle"
                                    class="!w-10 !h-10 !bg-[#106B4F]"
                                />
                                <div class="flex-1 min-w-0">
                                    <span class="block font-medium text-[#0D1F1B] text-sm">{{ booking.client.name }}</span>
                                    <span class="block text-xs text-gray-500">{{ booking.service.name }}</span>
                                </div>
                                <div class="text-right sm:w-auto w-full sm:mt-0 mt-1 flex sm:flex-col gap-2 sm:gap-0">
                                    <span class="block text-[13px] font-medium text-[#0D1F1B]">{{ booking.date }}</span>
                                    <span class="block text-xs text-gray-500">{{ booking.time }}</span>
                                </div>
                                <Tag :severity="getStatusSeverity(booking.status)" :value="booking.status_label" rounded />
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Recent Activity -->
                <Card class="!shadow-sm !overflow-hidden">
                    <template #header>
                        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Recent Activity</h2>
                        </div>
                    </template>
                    <template #content>
                        <!-- Recent Payments -->
                        <div class="mb-6 last:mb-0">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 m-0 mb-3">
                                <i class="pi pi-wallet"></i>
                                Recent Payments
                            </h3>
                            <div v-if="recentPayments.length === 0" class="text-center py-4 text-gray-400">
                                <p class="m-0 text-sm">No payments yet</p>
                            </div>
                            <div v-else class="flex flex-col gap-2">
                                <div v-for="(payment, index) in recentPayments" :key="payment.uuid" class="flex justify-between items-center py-2" :class="{ 'border-b border-gray-100': index !== recentPayments.length - 1 }">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-[#0D1F1B]">{{ payment.service_name }}</span>
                                        <span class="text-xs text-gray-400">{{ payment.date }}</span>
                                    </div>
                                    <span class="font-semibold text-[#106B4F]">+${{ payment.amount }}</span>
                                </div>
                            </div>
                            <Link href="/console/payments" class="block mt-3 text-[13px] text-[#106B4F] no-underline hover:underline">View payment history</Link>
                        </div>

                        <!-- Recent Reviews -->
                        <div class="mb-6 last:mb-0">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 m-0 mb-3">
                                <i class="pi pi-star"></i>
                                Recent Reviews
                                <Tag v-if="unrespondedReviewsCount > 0" severity="danger" :value="`${unrespondedReviewsCount} new`" rounded class="!text-[11px] !px-2 !py-0.5" />
                            </h3>
                            <div v-if="recentReviews.length === 0" class="text-center py-4 text-gray-400">
                                <p class="m-0 text-sm">No reviews yet</p>
                            </div>
                            <div v-else class="flex flex-col gap-2">
                                <div v-for="(review, index) in recentReviews.slice(0, 3)" :key="review.uuid" class="flex flex-col items-start gap-1 py-2" :class="{ 'border-b border-gray-100': index !== Math.min(recentReviews.length, 3) - 1 }">
                                    <div class="flex justify-between items-center w-full">
                                        <span class="font-medium text-sm text-[#0D1F1B]">{{ review.client.name }}</span>
                                        <div class="flex gap-0.5">
                                            <i v-for="i in 5" :key="i" class="pi text-xs" :class="i <= review.rating ? 'pi-star-fill text-yellow-500' : 'pi-star text-gray-300'"></i>
                                        </div>
                                    </div>
                                    <p class="text-[13px] text-gray-500 m-0 leading-relaxed">{{ review.comment?.slice(0, 80) }}{{ review.comment?.length > 80 ? '...' : '' }}</p>
                                    <Tag v-if="!review.has_response" severity="danger" value="Needs response" class="!text-[11px] !px-1.5 !py-0.5" />
                                </div>
                            </div>
                            <Link href="/console/reviews" class="block mt-3 text-[13px] text-[#106B4F] no-underline hover:underline">View all reviews</Link>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card class="!shadow-sm">
                <template #content>
                    <h2 class="text-base font-semibold text-[#0D1F1B] m-0 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <Button as="router-link" to="/console/services/create" outlined class="!justify-center !gap-2">
                            <i class="pi pi-plus"></i>
                            Add Service
                        </Button>
                        <Button as="router-link" to="/console/availability" outlined class="!justify-center !gap-2">
                            <i class="pi pi-clock"></i>
                            Edit Availability
                        </Button>
                        <Button as="router-link" to="/console/profile" outlined class="!justify-center !gap-2">
                            <i class="pi pi-user"></i>
                            Edit Profile
                        </Button>
                        <Button as="router-link" to="/console/bookings" outlined class="!justify-center !gap-2">
                            <i class="pi pi-calendar"></i>
                            Manage Bookings
                        </Button>
                    </div>
                </template>
            </Card>
        </div>
    </ConsoleLayout>
</template>

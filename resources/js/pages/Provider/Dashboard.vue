<script setup lang="ts">
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleStatCard,
    ConsoleFormCard,
    ConsoleAlertBanner,
    ConsoleEmptyState,
    ConsoleButton,
} from '@/components/console';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import AppLink from '@/components/common/AppLink.vue';
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
            <ConsolePageHeader
                :title="`Welcome back, ${provider.business_name}`"
                :subtitle="provider.tagline || undefined"
            >
                <template v-if="provider.rating_count > 0" #badge>
                    <Tag severity="warn" class="!px-3 !py-2 !text-sm">
                        <i class="pi pi-star-fill mr-1"></i>
                        {{ provider.rating_avg?.toFixed(1) }}
                        <span class="font-normal ml-1">({{ provider.rating_count }} reviews)</span>
                    </Tag>
                </template>
            </ConsolePageHeader>

            <!-- Alert Banner for Pending Bookings -->
            <ConsoleAlertBanner
                v-if="stats.pendingBookings > 0"
                variant="warning"
                action-label="View All"
                :action-href="provider.bookings.index.url({ query: { status: 'pending' }})"
            >
                You have <strong>{{ stats.pendingBookings }}</strong> pending booking{{ stats.pendingBookings > 1 ? 's' : '' }} awaiting confirmation
            </ConsoleAlertBanner>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6">
                <ConsoleStatCard
                    title="Total Earnings"
                    :value="formatCurrency(stats.totalEarnings)"
                    icon="pi pi-wallet"
                    icon-color="primary"
                />
                <ConsoleStatCard
                    title="Pending Payout"
                    :value="formatCurrency(stats.pendingPayout)"
                    icon="pi pi-clock"
                    icon-color="warning"
                />
                <ConsoleStatCard
                    title="Completed"
                    :value="stats.completedBookings"
                    icon="pi pi-check-circle"
                    icon-color="accent"
                />
                <ConsoleStatCard
                    title="Services"
                    :value="stats.activeServices"
                    icon="pi pi-th-large"
                    icon-color="purple"
                />
            </div>

            <!-- Main Content Grid - 3 column on xl -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6 mb-6">
                <!-- Upcoming Bookings - Takes 2 columns on large screens -->
                <div class="lg:col-span-2">
                    <ConsoleFormCard title="Upcoming Bookings" no-padding>
                        <template #header-actions>
                            <AppLink :href="provider.bookings.index.url()" class="text-xs lg:text-sm text-[#106B4F] no-underline hover:underline">
                                View All
                            </AppLink>
                        </template>

                        <div class="p-4 lg:p-5">
                            <ConsoleEmptyState
                                v-if="upcomingBookings.length === 0"
                                icon="pi pi-calendar"
                                title="No upcoming bookings"
                                description="Set your availability to start receiving bookings from clients"
                                action-label="Set Availability"
                                :action-href="provider.availability.edit.url()"
                                size="compact"
                            />
                            <div v-else class="grid gap-3">
                                <div
                                    v-for="booking in upcomingBookings"
                                    :key="booking.uuid"
                                    class="flex flex-wrap items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                >
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
                    </ConsoleFormCard>
                </div>

                <!-- Right Column - Activity -->
                <div class="space-y-4 lg:space-y-6">
                    <!-- Recent Payments -->
                    <ConsoleFormCard title="Payments" icon="pi pi-wallet" no-padding>
                        <template #header-actions>
                            <AppLink :href="provider.payments.index.url()" class="text-xs lg:text-sm text-[#106B4F] no-underline hover:underline">
                                View All
                            </AppLink>
                        </template>

                        <div class="p-4 lg:p-5">
                            <div v-if="recentPayments.length === 0" class="text-center py-6 text-gray-400">
                                <p class="m-0 text-sm">No payments yet</p>
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="(payment, index) in recentPayments.slice(0, 4)"
                                    :key="payment.uuid"
                                    class="flex justify-between items-center py-2"
                                    :class="{ 'border-b border-gray-100': index !== Math.min(recentPayments.length, 4) - 1 }"
                                >
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-sm text-[#0D1F1B] truncate">{{ payment.service_name }}</span>
                                        <span class="text-xs text-gray-400">{{ payment.date }}</span>
                                    </div>
                                    <span class="font-semibold text-[#106B4F] shrink-0 ml-2">+${{ payment.amount }}</span>
                                </div>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Recent Reviews -->
                    <ConsoleFormCard no-padding>
                        <template #default>
                            <div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-100">
                                <div class="flex justify-between items-center">
                                    <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                                        <i class="pi pi-star text-yellow-500"></i>
                                        Reviews
                                        <Tag v-if="unrespondedReviewsCount > 0" severity="danger" :value="`${unrespondedReviewsCount}`" rounded class="!text-[10px] !px-1.5 !py-0.5 !min-w-[20px]" />
                                    </h2>
                                    <AppLink :href="provider.reviews.index.url()" class="text-xs lg:text-sm text-[#106B4F] no-underline hover:underline">
                                        View All
                                    </AppLink>
                                </div>
                            </div>
                            <div class="p-4 lg:p-5">
                                <div v-if="recentReviews.length === 0" class="text-center py-6 text-gray-400">
                                    <p class="m-0 text-sm">No reviews yet</p>
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="review in recentReviews.slice(0, 3)"
                                        :key="review.uuid"
                                        class="pb-3 border-b border-gray-100 last:border-0 last:pb-0"
                                    >
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-medium text-sm text-[#0D1F1B]">{{ review.client.name }}</span>
                                            <div class="flex gap-0.5">
                                                <i
                                                    v-for="i in 5"
                                                    :key="i"
                                                    class="pi text-xs"
                                                    :class="i <= review.rating ? 'pi-star-fill text-yellow-500' : 'pi-star text-gray-300'"
                                                ></i>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 m-0 leading-relaxed line-clamp-2">{{ review.comment }}</p>
                                        <Tag v-if="!review.has_response" severity="danger" value="Needs response" class="!text-[10px] !px-1.5 !py-0.5 mt-1.5" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </ConsoleFormCard>
                </div>
            </div>

            <!-- Quick Actions - Full width bottom bar -->
            <ConsoleFormCard title="Quick Actions">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 lg:gap-3">
                    <ConsoleButton
                        label="Add Service"
                        icon="pi pi-plus"
                        :href="provider.services.create.url()"
                        variant="primary"
                    />
                    <ConsoleButton
                        label="Availability"
                        icon="pi pi-clock"
                        :href="provider.availability.edit.url()"
                        variant="secondary"
                    />
                    <ConsoleButton
                        label="Profile"
                        icon="pi pi-user"
                        :href="provider.profile.edit.url()"
                        variant="secondary"
                    />
                    <ConsoleButton
                        label="Bookings"
                        icon="pi pi-calendar"
                        :href="provider.bookings.index.url()"
                        variant="secondary"
                    />
                </div>
            </ConsoleFormCard>
        </div>
    </ConsoleLayout>
</template>

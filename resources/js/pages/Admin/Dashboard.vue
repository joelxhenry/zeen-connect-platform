<script setup lang="ts">
import { computed } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import StatCard from '@/components/admin/StatCard.vue';
import ProviderStatusTable from '@/components/admin/ProviderStatusTable.vue';
import FinancialLedgerChart from '@/components/admin/FinancialLedgerChart.vue';
import PayoutStatusBadge from '@/components/admin/PayoutStatusBadge.vue';
import Avatar from 'primevue/avatar';
import { router } from '@inertiajs/vue3';

interface Stats {
    total_users: number;
    total_providers: number;
    active_providers: number;
    pending_providers: number;
    total_bookings: number;
    completed_bookings: number;
    pending_bookings: number;
    total_revenue: number;
    total_reviews: number;
    mrr: number;
    pending_payouts_amount: number;
    waitlist_count: number;
}

interface RevenueData {
    date: string;
    total: number;
    count: number;
}

interface Provider {
    id: number;
    uuid: string;
    business_name: string;
    user_name: string;
    user_email: string;
    avatar?: string;
    status: 'pending' | 'active' | 'suspended' | 'inactive';
    kyc_status: 'pending' | 'submitted' | 'verified' | 'rejected';
    total_bookings: number;
    created_at: string;
}

interface RecentBooking {
    id: number;
    uuid: string;
    client_name: string;
    provider_name: string;
    service_name: string;
    booking_date: string;
    start_time: string;
    status: string;
    status_label: string;
    total_amount: string;
    created_at: string;
}

interface RecentPayment {
    id: number;
    uuid: string;
    client_name: string;
    amount: string;
    booking_uuid: string;
    paid_at: string;
}

interface TopProvider {
    id: number;
    business_name: string;
    avatar?: string;
    total_bookings: number;
    rating_avg: number;
    rating_count: number;
}

interface Props {
    stats: Stats;
    revenueByDay: RevenueData[];
    bookingsByStatus: Record<string, number>;
    recentBookings: RecentBooking[];
    recentPayments: RecentPayment[];
    pendingProviders: Provider[];
    topProviders: TopProvider[];
    financialData?: Array<{ date: string; revenue: number; payouts: number }>;
}

const props = defineProps<Props>();

// Transform providers for the table
const tableProviders = computed(() => {
    return props.pendingProviders.map(p => ({
        ...p,
        kyc_status: 'pending' as const,
    }));
});

// Transform financial data for the chart
const chartData = computed(() => {
    if (props.financialData) {
        return props.financialData;
    }
    // Fallback: use revenue data with placeholder payouts
    return props.revenueByDay.map(d => ({
        date: d.date,
        revenue: d.total,
        payouts: d.total * 0.85, // Platform takes ~15%
    }));
});

// Calculate trends (placeholder - would come from backend in production)
const providerTrend = computed(() => {
    return { value: 12, direction: 'up' as const, label: 'vs last month' };
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'JMD',
        maximumFractionDigits: 0,
    }).format(amount);
};

const handleProviderApprove = (provider: Provider) => {
    router.post(`/admin/providers/${provider.uuid}/approve`);
};

const handleProviderReject = (provider: Provider) => {
    router.post(`/admin/providers/${provider.uuid}/reject`);
};

const handleProviderSuspend = (provider: Provider) => {
    router.post(`/admin/providers/${provider.uuid}/suspend`);
};

const handleProviderView = (provider: Provider) => {
    router.get(`/admin/providers/${provider.uuid}`);
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'completed': return 'status-completed';
        case 'pending': return 'status-pending';
        case 'confirmed': return 'status-confirmed';
        case 'cancelled': return 'status-cancelled';
        default: return 'status-default';
    }
};
</script>

<template>
    <AdminLayout title="Dashboard">
        <div class="dashboard">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <StatCard
                    title="Monthly Revenue"
                    :value="formatCurrency(stats.mrr || stats.total_revenue)"
                    icon="pi pi-chart-line"
                    icon-bg="#106B4F"
                    :trend="{ value: 8.2, direction: 'up', label: 'vs last month' }"
                />
                <StatCard
                    title="Active Providers"
                    :value="stats.active_providers"
                    icon="pi pi-briefcase"
                    icon-bg="#3B82F6"
                    :trend="providerTrend"
                    href="/admin/providers"
                />
                <StatCard
                    title="Pending Payouts"
                    :value="formatCurrency(stats.pending_payouts_amount || 0)"
                    icon="pi pi-money-bill"
                    icon-bg="#F59E0B"
                    href="/admin/payouts"
                />
                <StatCard
                    title="Waitlist"
                    :value="stats.waitlist_count || 0"
                    icon="pi pi-users"
                    icon-bg="#8B5CF6"
                    href="/admin/waitlist"
                />
            </div>

            <!-- Main Content Grid -->
            <div class="main-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <ProviderStatusTable
                        :providers="tableProviders"
                        @approve="handleProviderApprove"
                        @reject="handleProviderReject"
                        @suspend="handleProviderSuspend"
                        @view="handleProviderView"
                    />

                    <!-- Recent Bookings -->
                    <div class="panel recent-bookings">
                        <div class="panel-header">
                            <h3>Recent Bookings</h3>
                            <AppLink href="/admin/bookings" class="view-all-link">
                                View all <i class="pi pi-arrow-right"></i>
                            </AppLink>
                        </div>
                        <div class="bookings-list">
                            <div
                                v-for="booking in recentBookings.slice(0, 5)"
                                :key="booking.id"
                                class="booking-item"
                            >
                                <div class="booking-info">
                                    <span class="booking-client">{{ booking.client_name }}</span>
                                    <span class="booking-service">{{ booking.service_name }}</span>
                                </div>
                                <div class="booking-meta">
                                    <span class="booking-date">{{ booking.booking_date }}</span>
                                    <span class="booking-status" :class="getStatusClass(booking.status)">
                                        {{ booking.status_label }}
                                    </span>
                                </div>
                                <span class="booking-amount">{{ booking.total_amount }}</span>
                            </div>
                            <div v-if="recentBookings.length === 0" class="empty-state">
                                <i class="pi pi-calendar"></i>
                                <p>No recent bookings</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <FinancialLedgerChart :data="chartData" />

                    <!-- Top Providers -->
                    <div class="panel top-providers">
                        <div class="panel-header">
                            <h3>Top Providers</h3>
                        </div>
                        <div class="providers-list">
                            <div
                                v-for="(provider, index) in topProviders"
                                :key="provider.id"
                                class="provider-item"
                            >
                                <span class="provider-rank">{{ index + 1 }}</span>
                                <Avatar
                                    :image="provider.avatar"
                                    :label="provider.business_name?.charAt(0).toUpperCase()"
                                    shape="circle"
                                    class="provider-avatar"
                                />
                                <div class="provider-info">
                                    <span class="provider-name">{{ provider.business_name }}</span>
                                    <div class="provider-stats">
                                        <span class="provider-bookings">
                                            <i class="pi pi-calendar"></i>
                                            {{ provider.total_bookings }} bookings
                                        </span>
                                        <span class="provider-rating">
                                            <i class="pi pi-star-fill"></i>
                                            {{ provider.rating_avg?.toFixed(1) || 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="topProviders.length === 0" class="empty-state">
                                <i class="pi pi-trophy"></i>
                                <p>No provider data yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="panel activity-feed">
                <div class="panel-header">
                    <h3>Recent Payments</h3>
                    <AppLink href="/admin/payments" class="view-all-link">
                        View all <i class="pi pi-arrow-right"></i>
                    </AppLink>
                </div>
                <div class="payments-grid">
                    <div
                        v-for="payment in recentPayments.slice(0, 6)"
                        :key="payment.id"
                        class="payment-item"
                    >
                        <div class="payment-icon">
                            <i class="pi pi-check-circle"></i>
                        </div>
                        <div class="payment-details">
                            <span class="payment-client">{{ payment.client_name }}</span>
                            <span class="payment-time">{{ payment.paid_at }}</span>
                        </div>
                        <span class="payment-amount">{{ payment.amount }}</span>
                    </div>
                    <div v-if="recentPayments.length === 0" class="empty-state col-span-full">
                        <i class="pi pi-wallet"></i>
                        <p>No recent payments</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.dashboard {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
}

@media (max-width: 1280px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Main Grid */
.main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

@media (max-width: 1024px) {
    .main-grid {
        grid-template-columns: 1fr;
    }
}

.left-column,
.right-column {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

/* Panel Styles */
.panel {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #E2E8F0;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.panel-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #0F172A;
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: #106B4F;
    text-decoration: none;
    font-weight: 500;
}

.view-all-link:hover {
    text-decoration: underline;
}

.view-all-link i {
    font-size: 0.75rem;
}

/* Recent Bookings */
.bookings-list {
    display: flex;
    flex-direction: column;
}

.booking-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #F1F5F9;
}

.booking-item:last-child {
    border-bottom: none;
}

.booking-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.booking-client {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0F172A;
}

.booking-service {
    font-size: 0.75rem;
    color: #64748B;
}

.booking-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.booking-date {
    font-size: 0.75rem;
    color: #64748B;
}

.booking-status {
    font-size: 0.6875rem;
    font-weight: 500;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
}

.status-completed {
    background-color: #DCFCE7;
    color: #16A34A;
}

.status-pending {
    background-color: #FEF3C7;
    color: #D97706;
}

.status-confirmed {
    background-color: #DBEAFE;
    color: #2563EB;
}

.status-cancelled {
    background-color: #FEE2E2;
    color: #DC2626;
}

.status-default {
    background-color: #F3F4F6;
    color: #6B7280;
}

.booking-amount {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0F172A;
    min-width: 80px;
    text-align: right;
}

/* Top Providers */
.providers-list {
    display: flex;
    flex-direction: column;
}

.provider-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #F1F5F9;
}

.provider-item:last-child {
    border-bottom: none;
}

.provider-rank {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #F1F5F9;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748B;
}

.provider-item:nth-child(1) .provider-rank {
    background-color: #FEF3C7;
    color: #D97706;
}

.provider-item:nth-child(2) .provider-rank {
    background-color: #E2E8F0;
    color: #475569;
}

.provider-item:nth-child(3) .provider-rank {
    background-color: #FFEDD5;
    color: #C2410C;
}

.provider-avatar {
    background-color: #106B4F;
    color: white;
}

.provider-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.provider-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0F172A;
}

.provider-stats {
    display: flex;
    gap: 1rem;
}

.provider-bookings,
.provider-rating {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #64748B;
}

.provider-rating i {
    color: #F59E0B;
}

/* Payments Grid */
.payments-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

@media (max-width: 1024px) {
    .payments-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .payments-grid {
        grid-template-columns: 1fr;
    }
}

.payment-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background-color: #F8FAFC;
    border-radius: 8px;
}

.payment-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #DCFCE7;
    border-radius: 50%;
    color: #16A34A;
}

.payment-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.payment-client {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #0F172A;
}

.payment-time {
    font-size: 0.75rem;
    color: #64748B;
}

.payment-amount {
    font-size: 0.875rem;
    font-weight: 600;
    color: #16A34A;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #94A3B8;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.empty-state p {
    margin: 0;
    font-size: 0.875rem;
}

.col-span-full {
    grid-column: 1 / -1;
}
</style>

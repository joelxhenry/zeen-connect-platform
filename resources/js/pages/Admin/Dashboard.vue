<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

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
}

interface RevenueData {
    date: string;
    total: number;
    count: number;
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
    completed_at: string;
}

interface PendingProvider {
    id: number;
    uuid: string;
    business_name: string;
    user_name: string;
    user_email: string;
    created_at: string;
}

interface TopProvider {
    id: number;
    business_name: string;
    avatar: string | null;
    total_bookings: number;
    rating_avg: number;
    rating_count: number;
}

const props = defineProps<{
    stats: Stats;
    revenueByDay: RevenueData[];
    bookingsByStatus: Record<string, number>;
    recentBookings: RecentBooking[];
    recentPayments: RecentPayment[];
    pendingProviders: PendingProvider[];
    topProviders: TopProvider[];
}>();

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-JM', {
        style: 'currency',
        currency: 'JMD',
        minimumFractionDigits: 0,
    }).format(value);
};

const getStatusSeverity = (status: string) => {
    const severities: Record<string, string> = {
        pending: 'warning',
        confirmed: 'info',
        completed: 'success',
        cancelled: 'danger',
        no_show: 'secondary',
    };
    return severities[status] || 'secondary';
};

const statCards = [
    { label: 'Total Users', value: props.stats.total_users, icon: 'pi-users', color: 'blue' },
    { label: 'Active Providers', value: props.stats.active_providers, icon: 'pi-briefcase', color: 'green' },
    { label: 'Pending Providers', value: props.stats.pending_providers, icon: 'pi-clock', color: 'orange' },
    { label: 'Total Bookings', value: props.stats.total_bookings, icon: 'pi-calendar', color: 'purple' },
    { label: 'Completed Bookings', value: props.stats.completed_bookings, icon: 'pi-check-circle', color: 'teal' },
    { label: 'Total Revenue', value: formatCurrency(props.stats.total_revenue), icon: 'pi-wallet', color: 'green', isRevenue: true },
    { label: 'Total Reviews', value: props.stats.total_reviews, icon: 'pi-star', color: 'yellow' },
    { label: 'Pending Bookings', value: props.stats.pending_bookings, icon: 'pi-hourglass', color: 'orange' },
];
</script>

<template>
    <AdminLayout title="Dashboard">
        <Head title="Admin Dashboard" />

        <div class="admin-dashboard">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div
                    v-for="stat in statCards"
                    :key="stat.label"
                    :class="['stat-card', `stat-${stat.color}`]"
                >
                    <div class="stat-icon">
                        <i :class="['pi', stat.icon]"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value">{{ stat.value }}</span>
                        <span class="stat-label">{{ stat.label }}</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Recent Bookings -->
                <Card class="bookings-card">
                    <template #title>
                        <div class="card-header">
                            <span>Recent Bookings</span>
                            <Link :href="route('admin.providers.index')">
                                <Button label="View All" text size="small" />
                            </Link>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="recentBookings" :rows="5" class="p-datatable-sm">
                            <Column field="client_name" header="Client" />
                            <Column field="provider_name" header="Provider" />
                            <Column field="service_name" header="Service" />
                            <Column field="booking_date" header="Date" />
                            <Column header="Status">
                                <template #body="{ data }">
                                    <Tag :severity="getStatusSeverity(data.status)" :value="data.status_label" />
                                </template>
                            </Column>
                            <Column field="total_amount" header="Amount" />
                        </DataTable>
                    </template>
                </Card>

                <!-- Pending Providers -->
                <Card class="pending-card">
                    <template #title>
                        <div class="card-header">
                            <span>Pending Verifications</span>
                            <Tag :value="pendingProviders.length" severity="warning" />
                        </div>
                    </template>
                    <template #content>
                        <div v-if="pendingProviders.length === 0" class="empty-state">
                            <i class="pi pi-check-circle"></i>
                            <p>No pending verifications</p>
                        </div>
                        <div v-else class="pending-list">
                            <div
                                v-for="provider in pendingProviders"
                                :key="provider.id"
                                class="pending-item"
                            >
                                <div class="pending-info">
                                    <span class="pending-name">{{ provider.business_name }}</span>
                                    <span class="pending-email">{{ provider.user_email }}</span>
                                </div>
                                <div class="pending-actions">
                                    <span class="pending-time">{{ provider.created_at }}</span>
                                    <Link :href="route('admin.providers.show', provider.uuid)">
                                        <Button icon="pi pi-eye" text size="small" rounded />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Recent Payments -->
                <Card class="payments-card">
                    <template #title>
                        <div class="card-header">
                            <span>Recent Payments</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="payments-list">
                            <div
                                v-for="payment in recentPayments"
                                :key="payment.id"
                                class="payment-item"
                            >
                                <div class="payment-info">
                                    <span class="payment-client">{{ payment.client_name }}</span>
                                    <span class="payment-time">{{ payment.completed_at }}</span>
                                </div>
                                <span class="payment-amount">{{ payment.amount }}</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Top Providers -->
                <Card class="top-providers-card">
                    <template #title>
                        <div class="card-header">
                            <span>Top Providers</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="providers-list">
                            <div
                                v-for="(provider, index) in topProviders"
                                :key="provider.id"
                                class="provider-item"
                            >
                                <span class="provider-rank">#{{ index + 1 }}</span>
                                <div class="provider-info">
                                    <span class="provider-name">{{ provider.business_name }}</span>
                                    <div class="provider-stats">
                                        <span class="provider-bookings">
                                            <i class="pi pi-calendar"></i>
                                            {{ provider.total_bookings }} bookings
                                        </span>
                                        <span class="provider-rating" v-if="provider.rating_count > 0">
                                            <i class="pi pi-star-fill"></i>
                                            {{ provider.rating_avg.toFixed(1) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.admin-dashboard {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.stat-blue .stat-icon {
    background: var(--p-blue-100);
    color: var(--p-blue-600);
}

.stat-green .stat-icon {
    background: var(--p-green-100);
    color: var(--p-green-600);
}

.stat-orange .stat-icon {
    background: var(--p-orange-100);
    color: var(--p-orange-600);
}

.stat-purple .stat-icon {
    background: var(--p-purple-100);
    color: var(--p-purple-600);
}

.stat-teal .stat-icon {
    background: var(--p-teal-100);
    color: var(--p-teal-600);
}

.stat-yellow .stat-icon {
    background: var(--p-yellow-100);
    color: var(--p-yellow-600);
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
}

.stat-label {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bookings-card {
    grid-column: 1 / 2;
}

.pending-card,
.payments-card,
.top-providers-card {
    grid-column: 2 / 3;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem;
    color: var(--p-surface-400);
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.pending-list,
.payments-list,
.providers-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.pending-item,
.payment-item,
.provider-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--p-surface-50);
    border-radius: 8px;
}

.pending-info,
.payment-info,
.provider-info {
    display: flex;
    flex-direction: column;
}

.pending-name,
.payment-client,
.provider-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.pending-email,
.pending-time,
.payment-time {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.pending-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.payment-amount {
    font-weight: 600;
    color: var(--p-green-600);
}

.provider-item {
    gap: 0.75rem;
}

.provider-rank {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--p-surface-400);
    min-width: 24px;
}

.provider-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.provider-stats i {
    margin-right: 0.25rem;
}

.provider-rating i {
    color: var(--p-yellow-500);
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .bookings-card,
    .pending-card,
    .payments-card,
    .top-providers-card {
        grid-column: 1;
    }
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

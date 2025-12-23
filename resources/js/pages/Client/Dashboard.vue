<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/components/layout/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

interface UpcomingBooking {
    uuid: string;
    provider_name: string;
    provider_slug: string;
    service_name: string;
    date: string;
    time: string;
    status: string;
    status_label: string;
    status_color: string;
}

interface RecentBooking {
    uuid: string;
    provider_name: string;
    provider_slug: string;
    service_name: string;
    date: string;
    total: string;
    status: string;
    status_label: string;
    status_color: string;
    has_review: boolean;
    can_review: boolean;
}

interface PendingReview {
    uuid: string;
    provider_name: string;
    service_name: string;
    date: string;
}

interface Stats {
    upcoming: number;
    completed: number;
    reviews: number;
}

defineProps<{
    stats: Stats;
    upcomingBookings: UpcomingBooking[];
    recentBookings: RecentBooking[];
    pendingReviews: PendingReview[];
}>();

const getStatusSeverity = (status: string): "success" | "info" | "warn" | "danger" | "secondary" | "contrast" | undefined => {
    const severities: Record<string, "success" | "info" | "warn" | "danger" | "secondary"> = {
        pending: 'warn',
        confirmed: 'success',
        completed: 'info',
        cancelled: 'danger',
        no_show: 'secondary',
    };
    return severities[status] || 'secondary';
};
</script>

<template>
    <DashboardLayout title="Dashboard">
        <div class="dashboard-page">
            <!-- Header -->
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <Link :href="route('explore')">
                    <Button label="Find Services" icon="pi pi-search" />
                </Link>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <Card class="stat-card upcoming">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon">
                                <i class="pi pi-calendar"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.upcoming }}</span>
                                <span class="stat-label">Upcoming Bookings</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card completed">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon">
                                <i class="pi pi-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.completed }}</span>
                                <span class="stat-label">Completed</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card reviews">
                    <template #content>
                        <div class="stat-content">
                            <div class="stat-icon">
                                <i class="pi pi-star"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.reviews }}</span>
                                <span class="stat-label">Reviews Given</span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Pending Reviews Alert -->
            <Card v-if="pendingReviews.length > 0" class="reviews-alert">
                <template #content>
                    <div class="alert-content">
                        <div class="alert-header">
                            <i class="pi pi-star"></i>
                            <span>Share your experience!</span>
                        </div>
                        <p class="alert-text">
                            You have {{ pendingReviews.length }} completed {{ pendingReviews.length === 1 ? 'booking' : 'bookings' }} waiting for a review.
                        </p>
                        <div class="pending-reviews-list">
                            <div v-for="review in pendingReviews" :key="review.uuid" class="pending-review-item">
                                <div class="review-info">
                                    <span class="review-service">{{ review.service_name }}</span>
                                    <span class="review-provider">with {{ review.provider_name }}</span>
                                </div>
                                <Link :href="route('client.reviews.create', review.uuid)">
                                    <Button label="Review" size="small" outlined />
                                </Link>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Main Content Grid -->
            <div class="content-grid">
                <!-- Upcoming Bookings -->
                <Card class="section-card">
                    <template #title>
                        <div class="section-header">
                            <div class="section-title">
                                <i class="pi pi-calendar"></i>
                                <span>Upcoming Bookings</span>
                            </div>
                            <Link :href="route('client.bookings.index')">
                                <Button label="View All" size="small" text />
                            </Link>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="upcomingBookings.length === 0" class="empty-state">
                            <i class="pi pi-calendar"></i>
                            <h3>No upcoming bookings</h3>
                            <p>Find a service provider to book your next appointment</p>
                            <Link :href="route('explore')">
                                <Button label="Explore Services" icon="pi pi-search" size="small" />
                            </Link>
                        </div>

                        <div v-else class="bookings-list">
                            <div v-for="booking in upcomingBookings" :key="booking.uuid" class="booking-item">
                                <div class="booking-date-badge">
                                    <span class="day">{{ booking.date.split(',')[0] }}</span>
                                    <span class="date">{{ booking.date.split(',')[1] }}</span>
                                </div>
                                <div class="booking-details">
                                    <div class="booking-main">
                                        <span class="service-name">{{ booking.service_name }}</span>
                                        <span class="provider-name">{{ booking.provider_name }}</span>
                                    </div>
                                    <div class="booking-meta">
                                        <span class="time">
                                            <i class="pi pi-clock"></i>
                                            {{ booking.time }}
                                        </span>
                                        <Tag :severity="getStatusSeverity(booking.status)" :value="booking.status_label" />
                                    </div>
                                </div>
                                <Link :href="route('client.bookings.show', booking.uuid)" class="booking-link">
                                    <i class="pi pi-chevron-right"></i>
                                </Link>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Recent Activity -->
                <Card class="section-card">
                    <template #title>
                        <div class="section-header">
                            <div class="section-title">
                                <i class="pi pi-history"></i>
                                <span>Recent Activity</span>
                            </div>
                            <Link :href="route('client.bookings.index')">
                                <Button label="View All" size="small" text />
                            </Link>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="recentBookings.length === 0" class="empty-state">
                            <i class="pi pi-history"></i>
                            <h3>No recent activity</h3>
                            <p>Your completed bookings will appear here</p>
                        </div>

                        <div v-else class="activity-list">
                            <div v-for="booking in recentBookings" :key="booking.uuid" class="activity-item">
                                <div class="activity-icon" :class="booking.status">
                                    <i :class="booking.status === 'completed' ? 'pi pi-check' : 'pi pi-times'"></i>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-main">
                                        <span class="service-name">{{ booking.service_name }}</span>
                                        <span class="activity-date">{{ booking.date }}</span>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="provider-name">{{ booking.provider_name }}</span>
                                        <span class="amount">{{ booking.total }}</span>
                                    </div>
                                </div>
                                <div class="activity-actions">
                                    <Link v-if="booking.can_review" :href="route('client.reviews.create', booking.uuid)">
                                        <Button icon="pi pi-star" size="small" outlined rounded />
                                    </Link>
                                    <Link :href="route('client.bookings.show', booking.uuid)">
                                        <Button icon="pi pi-eye" size="small" text rounded />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card class="quick-actions-card">
                <template #content>
                    <div class="quick-actions">
                        <Link :href="route('explore')" class="quick-action">
                            <div class="action-icon explore">
                                <i class="pi pi-search"></i>
                            </div>
                            <span class="action-label">Find Services</span>
                        </Link>
                        <Link :href="route('client.bookings.index')" class="quick-action">
                            <div class="action-icon bookings">
                                <i class="pi pi-calendar"></i>
                            </div>
                            <span class="action-label">My Bookings</span>
                        </Link>
                        <Link :href="route('client.reviews.index')" class="quick-action">
                            <div class="action-icon reviews">
                                <i class="pi pi-star"></i>
                            </div>
                            <span class="action-label">My Reviews</span>
                        </Link>
                        <Link :href="route('client.profile.edit')" class="quick-action">
                            <div class="action-icon profile">
                                <i class="pi pi-user"></i>
                            </div>
                            <span class="action-label">My Profile</span>
                        </Link>
                    </div>
                </template>
            </Card>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.dashboard-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.stat-card {
    border-radius: 12px;
}

.stat-card.upcoming .stat-icon {
    background: var(--p-blue-100);
    color: var(--p-blue-600);
}

.stat-card.completed .stat-icon {
    background: var(--p-green-100);
    color: var(--p-green-600);
}

.stat-card.reviews .stat-icon {
    background: var(--p-orange-100);
    color: var(--p-orange-600);
}

.stat-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon i {
    font-size: 1.25rem;
}

.stat-info {
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

.reviews-alert {
    background: linear-gradient(135deg, var(--p-orange-50), var(--p-yellow-50));
    border: 1px solid var(--p-orange-200);
    border-radius: 12px;
}

.alert-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.alert-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--p-orange-700);
}

.alert-header i {
    color: var(--p-orange-500);
}

.alert-text {
    color: var(--p-orange-600);
    font-size: 0.875rem;
    margin: 0;
}

.pending-reviews-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.pending-review-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: white;
    border-radius: 8px;
}

.review-info {
    display: flex;
    flex-direction: column;
}

.review-service {
    font-weight: 500;
    color: var(--p-surface-900);
    font-size: 0.875rem;
}

.review-provider {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.section-card {
    border-radius: 12px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
}

.section-title i {
    color: var(--p-primary-color);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
}

.empty-state i {
    font-size: 2.5rem;
    color: var(--p-surface-300);
    margin-bottom: 0.75rem;
}

.empty-state h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--p-surface-700);
    margin: 0 0 0.25rem 0;
}

.empty-state p {
    font-size: 0.875rem;
    color: var(--p-surface-500);
    margin: 0 0 1rem 0;
}

.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.booking-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: var(--p-surface-50);
    border-radius: 10px;
    transition: background 0.2s;
}

.booking-item:hover {
    background: var(--p-surface-100);
}

.booking-date-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 0.75rem;
    background: var(--p-primary-color);
    color: white;
    border-radius: 8px;
    min-width: 60px;
}

.booking-date-badge .day {
    font-size: 0.6875rem;
    text-transform: uppercase;
}

.booking-date-badge .date {
    font-size: 0.875rem;
    font-weight: 600;
}

.booking-details {
    flex: 1;
    min-width: 0;
}

.booking-main {
    display: flex;
    flex-direction: column;
}

.booking-main .service-name {
    font-weight: 600;
    color: var(--p-surface-900);
    font-size: 0.9375rem;
}

.booking-main .provider-name {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.booking-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.25rem;
}

.time {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.time i {
    font-size: 0.6875rem;
}

.booking-link {
    color: var(--p-surface-400);
    transition: color 0.2s;
}

.booking-link:hover {
    color: var(--p-primary-color);
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--p-surface-50);
    border-radius: 10px;
}

.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.activity-icon.completed {
    background: var(--p-green-100);
    color: var(--p-green-600);
}

.activity-icon.cancelled {
    background: var(--p-red-100);
    color: var(--p-red-600);
}

.activity-details {
    flex: 1;
    min-width: 0;
}

.activity-main {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.activity-main .service-name {
    font-weight: 500;
    color: var(--p-surface-900);
    font-size: 0.875rem;
}

.activity-date {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.activity-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.125rem;
}

.activity-meta .provider-name {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.amount {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--p-surface-700);
}

.activity-actions {
    display: flex;
    gap: 0.25rem;
}

.quick-actions-card {
    border-radius: 12px;
}

.quick-actions {
    display: flex;
    justify-content: space-around;
    gap: 1rem;
}

.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    text-decoration: none;
    transition: transform 0.2s;
}

.quick-action:hover {
    transform: translateY(-2px);
}

.action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-icon i {
    font-size: 1.25rem;
}

.action-icon.explore {
    background: var(--p-blue-100);
    color: var(--p-blue-600);
}

.action-icon.bookings {
    background: var(--p-purple-100);
    color: var(--p-purple-600);
}

.action-icon.reviews {
    background: var(--p-orange-100);
    color: var(--p-orange-600);
}

.action-icon.profile {
    background: var(--p-green-100);
    color: var(--p-green-600);
}

.action-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--p-surface-700);
}

@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .quick-actions {
        flex-wrap: wrap;
    }

    .quick-action {
        flex: 1;
        min-width: 80px;
    }
}
</style>

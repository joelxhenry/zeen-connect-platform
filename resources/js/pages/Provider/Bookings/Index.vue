<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Paginator from 'primevue/paginator';
import Calendar from 'primevue/calendar';
import Message from 'primevue/message';

interface BookingData {
    id: number;
    uuid: string;
    client: {
        name: string;
        email: string;
        phone?: string;
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
    client_notes?: string;
    is_past: boolean;
    is_today: boolean;
    can_confirm: boolean;
    can_complete: boolean;
    can_cancel: boolean;
}

interface PaginatedBookings {
    data: BookingData[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    bookings: PaginatedBookings;
    currentStatus: string;
    currentDate?: string;
    counts: Record<string, number>;
    statusOptions: Array<{ value: string; label: string; color: string }>;
}>();

const page = usePage();

const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const changeStatus = (status: string) => {
    router.get(route('provider.bookings.index'), { status }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const onPageChange = (event: { page: number }) => {
    router.get(route('provider.bookings.index'), {
        status: props.currentStatus,
        page: event.page + 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const confirmBooking = (uuid: string) => {
    router.post(route('provider.bookings.confirm', uuid), {}, {
        preserveScroll: true,
    });
};

const completeBooking = (uuid: string) => {
    router.post(route('provider.bookings.complete', uuid), {}, {
        preserveScroll: true,
    });
};

const getInitials = (name: string): string => {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
};

const getTagSeverity = (color: string) => {
    const map: Record<string, 'success' | 'info' | 'warn' | 'danger' | 'secondary'> = {
        success: 'success',
        info: 'info',
        warning: 'warn',
        danger: 'danger',
        secondary: 'secondary',
    };
    return map[color] || 'secondary';
};
</script>

<template>
    <ConsoleLayout title="Bookings">
        <div class="bookings-page">
            <div class="page-header">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="pi pi-calendar"></i>
                    </div>
                    <div>
                        <h1 class="header-title">Bookings</h1>
                        <p class="header-subtitle">Manage your appointments and schedule</p>
                    </div>
                </div>
            </div>

            <Message v-if="page.props.flash?.success" severity="success" :closable="true" class="flash-message">
                {{ page.props.flash.success }}
            </Message>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card pending">
                    <span class="stat-value">{{ counts.pending }}</span>
                    <span class="stat-label">Pending</span>
                </div>
                <div class="stat-card confirmed">
                    <span class="stat-value">{{ counts.confirmed }}</span>
                    <span class="stat-label">Confirmed</span>
                </div>
                <div class="stat-card completed">
                    <span class="stat-value">{{ counts.completed }}</span>
                    <span class="stat-label">Completed</span>
                </div>
            </div>

            <!-- Status Tabs -->
            <div class="status-tabs">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    type="button"
                    class="status-tab"
                    :class="{ active: currentStatus === tab.value }"
                    @click="changeStatus(tab.value)"
                >
                    {{ tab.label }}
                    <span v-if="counts[tab.value]" class="tab-count">{{ counts[tab.value] }}</span>
                </button>
            </div>

            <!-- Bookings List -->
            <div v-if="bookings.data.length > 0" class="bookings-list">
                <div
                    v-for="booking in bookings.data"
                    :key="booking.id"
                    class="booking-card"
                >
                    <div class="booking-main">
                        <Link :href="route('provider.bookings.show', booking.uuid)" class="booking-link">
                            <div class="client-info">
                                <img
                                    v-if="booking.client.avatar"
                                    :src="booking.client.avatar"
                                    :alt="booking.client.name"
                                    class="client-avatar"
                                />
                                <div v-else class="client-avatar-placeholder">
                                    {{ getInitials(booking.client.name) }}
                                </div>
                                <div class="client-details">
                                    <h3 class="client-name">{{ booking.client.name }}</h3>
                                    <p class="service-name">{{ booking.service.name }}</p>
                                </div>
                            </div>

                            <div class="booking-info">
                                <div class="booking-datetime">
                                    <span class="date">
                                        {{ booking.formatted_date }}
                                        <Tag v-if="booking.is_today" severity="info" value="Today" class="today-tag" />
                                    </span>
                                    <span class="time">{{ booking.formatted_time }}</span>
                                </div>
                                <div class="booking-meta">
                                    <Tag :severity="getTagSeverity(booking.status_color)" :value="booking.status_label" />
                                    <span class="booking-total">{{ booking.total_display }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-if="booking.client_notes" class="booking-notes">
                        <i class="pi pi-comment"></i>
                        <span>{{ booking.client_notes }}</span>
                    </div>

                    <div v-if="booking.can_confirm || booking.can_complete" class="booking-actions">
                        <Button
                            v-if="booking.can_confirm"
                            label="Confirm"
                            icon="pi pi-check"
                            size="small"
                            @click.stop="confirmBooking(booking.uuid)"
                        />
                        <Button
                            v-if="booking.can_complete"
                            label="Complete"
                            icon="pi pi-check-circle"
                            size="small"
                            severity="success"
                            @click.stop="completeBooking(booking.uuid)"
                        />
                        <Link :href="route('provider.bookings.show', booking.uuid)">
                            <Button label="View Details" icon="pi pi-eye" size="small" severity="secondary" outlined />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="empty-state">
                <div class="empty-icon">
                    <i class="pi pi-calendar"></i>
                </div>
                <h3 class="empty-title">No bookings found</h3>
                <p class="empty-text">
                    {{ currentStatus === 'pending' ? "You don't have any pending bookings." :
                       currentStatus === 'confirmed' ? "You don't have any confirmed bookings." :
                       "No bookings match your current filter." }}
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="bookings.last_page > 1" class="pagination-wrapper">
                <Paginator
                    :rows="bookings.per_page"
                    :totalRecords="bookings.total"
                    :first="(bookings.current_page - 1) * bookings.per_page"
                    @page="onPageChange"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                />
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.bookings-page {
    max-width: 900px;
    margin: 0 auto;
}

/* Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--p-surface-200);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--p-primary-color), var(--p-primary-400));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.header-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.header-subtitle {
    font-size: 0.875rem;
    color: var(--p-surface-500);
    margin: 0.25rem 0 0 0;
}

.flash-message {
    margin-bottom: 1.5rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
}

.stat-card.pending {
    border-left: 3px solid #f59e0b;
}

.stat-card.confirmed {
    border-left: 3px solid #3b82f6;
}

.stat-card.completed {
    border-left: 3px solid #22c55e;
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

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        flex-direction: row;
        justify-content: space-between;
    }
}

/* Status Tabs */
.status-tabs {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 1.5rem;
    padding: 0.25rem;
    background: var(--p-surface-100);
    border-radius: 10px;
    overflow-x: auto;
}

.status-tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-600);
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.status-tab:hover {
    color: var(--p-surface-900);
}

.status-tab.active {
    background: white;
    color: var(--p-primary-color);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tab-count {
    background: var(--p-surface-200);
    padding: 0.125rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
}

.status-tab.active .tab-count {
    background: var(--p-primary-100);
    color: var(--p-primary-700);
}

/* Bookings List */
.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.booking-card {
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    overflow: hidden;
    transition: border-color 0.2s;
}

.booking-card:hover {
    border-color: var(--p-primary-200);
}

.booking-main {
    padding: 1rem 1.25rem;
}

.booking-link {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    text-decoration: none;
    color: inherit;
}

.client-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.client-avatar {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    object-fit: cover;
}

.client-avatar-placeholder {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--p-surface-100), var(--p-surface-200));
    color: var(--p-surface-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.8125rem;
}

.client-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.125rem 0;
}

.service-name {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
    margin: 0;
}

.booking-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.booking-datetime {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.date {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--p-surface-900);
}

.time {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.today-tag {
    font-size: 0.625rem;
}

.booking-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.booking-total {
    font-weight: 600;
    color: var(--p-surface-900);
}

.booking-notes {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: var(--p-surface-50);
    border-top: 1px solid var(--p-surface-100);
    font-size: 0.8125rem;
    color: var(--p-surface-600);
}

.booking-notes i {
    color: var(--p-surface-400);
    margin-top: 0.125rem;
}

.booking-actions {
    display: flex;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: var(--p-surface-50);
    border-top: 1px solid var(--p-surface-100);
}

@media (max-width: 640px) {
    .booking-link {
        flex-direction: column;
    }

    .booking-info {
        align-items: flex-start;
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
        padding-top: 0.75rem;
        border-top: 1px solid var(--p-surface-100);
    }

    .booking-datetime {
        align-items: flex-start;
    }
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 16px;
    text-align: center;
}

.empty-icon {
    width: 64px;
    height: 64px;
    background-color: var(--p-surface-100);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.empty-icon i {
    font-size: 1.5rem;
    color: var(--p-surface-400);
}

.empty-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.375rem 0;
}

.empty-text {
    font-size: 0.9375rem;
    color: var(--p-surface-500);
    margin: 0;
}

/* Pagination */
.pagination-wrapper {
    margin-top: 1.5rem;
    display: flex;
    justify-content: center;
}
</style>

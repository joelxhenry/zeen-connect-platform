<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/components/layout/DashboardLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Paginator from 'primevue/paginator';

interface BookingData {
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
}>();

const statusTabs = [
    { value: 'all', label: 'All Bookings' },
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'past', label: 'Past' },
];

const changeStatus = (status: string) => {
    router.get(route('client.bookings.index'), { status }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const onPageChange = (event: { page: number }) => {
    router.get(route('client.bookings.index'), {
        status: props.currentStatus,
        page: event.page + 1,
    }, {
        preserveState: true,
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
    <DashboardLayout title="My Bookings">
        <div class="bookings-page">
            <div class="page-header">
                <h1 class="page-title">My Bookings</h1>
                <Link :href="route('explore')" class="explore-link">
                    <Button label="Book New Service" icon="pi pi-plus" />
                </Link>
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
                </button>
            </div>

            <!-- Bookings List -->
            <div v-if="bookings.data.length > 0" class="bookings-list">
                <Link
                    v-for="booking in bookings.data"
                    :key="booking.id"
                    :href="route('client.bookings.show', booking.uuid)"
                    class="booking-card"
                >
                    <div class="booking-provider">
                        <img
                            v-if="booking.provider.avatar"
                            :src="booking.provider.avatar"
                            :alt="booking.provider.business_name"
                            class="provider-avatar"
                        />
                        <div v-else class="provider-avatar-placeholder">
                            {{ getInitials(booking.provider.business_name) }}
                        </div>
                        <div class="provider-info">
                            <h3 class="provider-name">{{ booking.provider.business_name }}</h3>
                            <p class="service-name">{{ booking.service.name }}</p>
                        </div>
                    </div>

                    <div class="booking-details">
                        <div class="booking-datetime">
                            <div class="date">
                                <i class="pi pi-calendar"></i>
                                {{ booking.formatted_date }}
                                <Tag v-if="booking.is_today" severity="info" value="Today" class="today-tag" />
                            </div>
                            <div class="time">
                                <i class="pi pi-clock"></i>
                                {{ booking.formatted_time }}
                            </div>
                        </div>
                        <div class="booking-status">
                            <Tag :severity="getTagSeverity(booking.status_color)" :value="booking.status_label" />
                            <span class="booking-total">{{ booking.total_display }}</span>
                        </div>
                    </div>

                    <i class="pi pi-chevron-right card-arrow"></i>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="empty-state">
                <div class="empty-icon">
                    <i class="pi pi-calendar"></i>
                </div>
                <h3 class="empty-title">No bookings found</h3>
                <p class="empty-text">
                    {{ currentStatus === 'upcoming' ? "You don't have any upcoming bookings." : "You haven't made any bookings yet." }}
                </p>
                <Link :href="route('explore')">
                    <Button label="Explore Providers" icon="pi pi-search" />
                </Link>
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
    </DashboardLayout>
</template>

<style scoped>
.bookings-page {
    max-width: 800px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

/* Status Tabs */
.status-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    padding: 0.25rem;
    background: var(--p-surface-100);
    border-radius: 10px;
}

.status-tab {
    flex: 1;
    padding: 0.625rem 1rem;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-600);
    cursor: pointer;
    transition: all 0.2s;
}

.status-tab:hover {
    color: var(--p-surface-900);
}

.status-tab.active {
    background: white;
    color: var(--p-primary-color);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Bookings List */
.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.booking-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}

.booking-card:hover {
    border-color: var(--p-primary-200);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.booking-provider {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.provider-avatar {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    object-fit: cover;
}

.provider-avatar-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--p-primary-100), var(--p-primary-200));
    color: var(--p-primary-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.provider-info {
    min-width: 0;
}

.provider-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.125rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.service-name {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.booking-details {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.booking-datetime {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.125rem;
}

.date, .time {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--p-surface-600);
}

.date i, .time i {
    font-size: 0.75rem;
    color: var(--p-surface-400);
}

.today-tag {
    font-size: 0.625rem;
    padding: 0.125rem 0.375rem;
}

.booking-status {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.booking-total {
    font-weight: 600;
    color: var(--p-surface-900);
}

.card-arrow {
    color: var(--p-surface-300);
    font-size: 0.875rem;
}

@media (max-width: 640px) {
    .booking-card {
        flex-direction: column;
        align-items: stretch;
    }

    .booking-details {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding-top: 0.75rem;
        border-top: 1px solid var(--p-surface-100);
    }

    .booking-datetime {
        align-items: flex-start;
    }

    .card-arrow {
        display: none;
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
    margin: 0 0 1.25rem 0;
}

/* Pagination */
.pagination-wrapper {
    margin-top: 1.5rem;
    display: flex;
    justify-content: center;
}
</style>

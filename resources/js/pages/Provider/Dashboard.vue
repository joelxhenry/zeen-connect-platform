<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsoleEmptyState,
    ConsoleButton,
} from '@/components/console';
import BookingDrawer from '@/components/booking/BookingDrawer.vue';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import InputText from 'primevue/inputtext';
import Drawer from 'primevue/drawer';
import providerRoutes from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface Booking {
    uuid: string;
    client: {
        name: string;
        email?: string;
        phone?: string;
        avatar?: string;
    };
    service: {
        id: number;
        name: string;
        duration?: number;
        category?: string;
        category_id?: number;
    };
    team_member?: { id: number; name: string } | null;
    booking_date: string;
    date: string;
    date_short: string;
    time: string;
    end_time: string;
    total_amount: string;
    service_price?: number;
    deposit_amount?: number;
    deposit_paid?: boolean;
    status: string;
    status_label: string;
    status_color: string;
    provider_notes?: string;
    client_notes?: string;
    created_at: string;
}

interface GroupedBooking {
    date: string;
    label: string;
    is_today: boolean;
    is_tomorrow: boolean;
    bookings: Booking[];
}

interface Props {
    provider: {
        business_name: string;
        slug: string;
    };
    bookings: {
        data: Booking[];
        current_page: number;
        last_page: number;
        total: number;
    };
    grouped_bookings: GroupedBooking[];
    status_counts: {
        all: number;
        pending: number;
        confirmed: number;
        completed: number;
        cancelled: number;
    };
    filters: {
        status: string | null;
        service_id: number | null;
        category_id: number | null;
        team_member_id: number | null;
        search: string | null;
        date_range: string;
    };
    services: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
    team_members?: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

// View toggle (bookings vs events)
const activeView = ref<'bookings' | 'events'>('bookings');

// Current date for display
const currentDate = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
});

// Filters
const statusFilter = ref<string | null>(props.filters.status);
const searchQuery = ref<string>(props.filters.search || '');
const dateRangeFilter = ref<string>(props.filters.date_range || 'upcoming');
const teamMemberFilter = ref<number | null>(props.filters.team_member_id);

// Mobile filter drawer
const filterDrawerVisible = ref(false);

// Date range options
const dateRangeOptions = [
    { label: 'Upcoming', value: 'upcoming' },
    { label: 'Today', value: 'today' },
    { label: 'This Week', value: 'week' },
    { label: 'This Month', value: 'month' },
];

// Status pills
const statusPills = computed(() => [
    { label: 'All', value: null, count: props.status_counts.all },
    { label: 'Pending', value: 'pending', count: props.status_counts.pending },
    { label: 'Confirmed', value: 'confirmed', count: props.status_counts.confirmed },
    { label: 'Completed', value: 'completed', count: props.status_counts.completed },
    { label: 'Cancelled', value: 'cancelled', count: props.status_counts.cancelled },
]);

// Team member options with "All" option or owner if no team
const teamMemberOptions = computed(() => {
    if (props.team_members && props.team_members.length > 0) {
        return [
            { id: null, name: 'All Team Members' },
            ...(props.team_members || [])
        ];
    }
    // No team members - show owner only
    return [
        { id: null, name: props.provider.business_name }
    ];
});

// Check if team filter is active
const hasActiveTeamFilter = computed(() => teamMemberFilter.value !== null);

// Check if team members exist (for different UI states)
const hasTeamMembers = computed(() => props.team_members && props.team_members.length > 0);

// Selected booking for drawer
const selectedBooking = ref<Booking | null>(null);
const drawerVisible = ref(false);

const openBookingDrawer = (booking: Booking) => {
    selectedBooking.value = booking;
    drawerVisible.value = true;
};

// Apply filters
const applyFilters = () => {
    router.get(
        resolveUrl(providerRoutes.dashboard.url()),
        {
            status: statusFilter.value || undefined,
            search: searchQuery.value || undefined,
            date_range: dateRangeFilter.value,
            team_member_id: teamMemberFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

// Watch filters and apply changes
let searchTimeout: ReturnType<typeof setTimeout>;
watch([statusFilter, dateRangeFilter, teamMemberFilter], () => {
    applyFilters();
});

watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

const clearSearch = () => {
    searchQuery.value = '';
};

const setTeamMemberFilter = (id: number | null) => {
    teamMemberFilter.value = id;
    filterDrawerVisible.value = false;
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getStatusSeverity = (status: string): "success" | "info" | "warn" | "danger" | "secondary" | "contrast" | undefined => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'success';
        case 'completed': return 'info';
        case 'cancelled': return 'danger';
        case 'no_show': return 'secondary';
        default: return 'secondary';
    }
};

// Load more bookings
const loadMore = () => {
    if (props.bookings.current_page < props.bookings.last_page) {
        router.get(
            resolveUrl(providerRoutes.dashboard.url()),
            {
                status: statusFilter.value || undefined,
                search: searchQuery.value || undefined,
                date_range: dateRangeFilter.value,
                team_member_id: teamMemberFilter.value || undefined,
                page: props.bookings.current_page + 1,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }
};

const hasMorePages = computed(() => props.bookings.current_page < props.bookings.last_page);
</script>

<template>
    <ConsoleLayout title="Bookings">
        <div class="dashboard-wrapper">
            <!-- Main Content -->
            <div class="dashboard-main">
                <!-- Header with View Toggle -->
                <div class="dashboard-header">
                    <p class="date-display">{{ currentDate }}</p>
                    <div class="view-toggle">
                        <button
                            class="toggle-btn"
                            :class="{ active: activeView === 'bookings' }"
                            @click="activeView = 'bookings'"
                        >
                            <i class="pi pi-calendar"></i>
                            Bookings
                        </button>
                        <button
                            class="toggle-btn"
                            :class="{ active: activeView === 'events' }"
                            @click="activeView = 'events'"
                        >
                            <i class="pi pi-calendar-plus"></i>
                            Events
                        </button>
                    </div>
                </div>

                <!-- Bookings View -->
                <template v-if="activeView === 'bookings'">
                    <!-- Search Bar with Filter Button -->
                    <div class="search-section">
                        <div class="search-wrapper">
                            <i class="pi pi-search search-icon"></i>
                            <InputText
                                v-model="searchQuery"
                                placeholder="Search client, service, or category..."
                                class="search-input"
                            />
                            <button
                                v-if="searchQuery"
                                class="search-clear"
                                @click="clearSearch"
                            >
                                <i class="pi pi-times"></i>
                            </button>
                        </div>
                        <!-- Mobile Filter Button -->
                        <button
                            class="filter-btn mobile-only"
                            :class="{ 'has-filter': hasActiveTeamFilter }"
                            @click="filterDrawerVisible = true"
                        >
                            <i class="pi pi-filter"></i>
                            <span v-if="hasActiveTeamFilter" class="filter-indicator"></span>
                        </button>
                    </div>

                    <!-- Date Range Pills -->
                    <div class="filter-pills-section">
                        <div class="pills-row">
                            <button
                                v-for="option in dateRangeOptions"
                                :key="option.value"
                                class="filter-pill"
                                :class="{ active: dateRangeFilter === option.value }"
                                @click="dateRangeFilter = option.value"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Status Pills -->
                    <div class="status-pills-section">
                        <div class="pills-row">
                            <button
                                v-for="pill in statusPills"
                                :key="pill.label"
                                class="status-pill"
                                :class="{
                                    active: statusFilter === pill.value,
                                    [`status-${pill.value || 'all'}`]: true
                                }"
                                @click="statusFilter = pill.value"
                            >
                                {{ pill.label }}
                                <span class="pill-count">{{ pill.count }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bookings List -->
                    <div class="bookings-list">
                        <ConsoleEmptyState
                            v-if="grouped_bookings.length === 0"
                            icon="pi pi-calendar"
                            title="No bookings found"
                            :description="searchQuery ? 'Try a different search term' : 'No bookings match your filters'"
                            size="compact"
                        />

                        <div v-else class="booking-groups">
                            <div
                                v-for="group in grouped_bookings"
                                :key="group.date"
                                class="booking-group"
                            >
                                <div class="date-header">
                                    <span class="date-label" :class="{ 'is-today': group.is_today }">
                                        {{ group.label }}
                                    </span>
                                    <span class="booking-count">{{ group.bookings.length }}</span>
                                </div>

                                <div class="booking-cards">
                                    <button
                                        v-for="booking in group.bookings"
                                        :key="booking.uuid"
                                        class="booking-card"
                                        @click="openBookingDrawer(booking)"
                                    >
                                        <div class="card-main">
                                            <Avatar
                                                v-if="booking.client.avatar"
                                                :image="booking.client.avatar"
                                                shape="circle"
                                                class="booking-avatar"
                                            />
                                            <Avatar
                                                v-else
                                                :label="getInitials(booking.client.name)"
                                                shape="circle"
                                                class="booking-avatar booking-avatar-fallback"
                                            />
                                            <div class="card-info">
                                                <span class="client-name">{{ booking.client.name }}</span>
                                                <span class="service-name">{{ booking.service.name }}</span>
                                            </div>
                                        </div>
                                        <div class="card-meta">
                                            <div class="time-badge">
                                                <i class="pi pi-clock"></i>
                                                {{ booking.time }}
                                            </div>
                                            <Tag
                                                :severity="getStatusSeverity(booking.status)"
                                                :value="booking.status_label"
                                                rounded
                                                class="status-tag"
                                            />
                                        </div>
                                        <div class="card-footer">
                                            <span class="amount">{{ booking.total_amount }}</span>
                                            <i class="pi pi-chevron-right card-arrow"></i>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Load More -->
                        <div v-if="hasMorePages" class="load-more-wrapper">
                            <ConsoleButton
                                label="Load More"
                                icon="pi pi-refresh"
                                variant="secondary"
                                @click="loadMore"
                            />
                        </div>
                    </div>
                </template>

                <!-- Events View (Coming Soon) -->
                <template v-else>
                    <div class="events-placeholder">
                        <ConsoleEmptyState
                            icon="pi pi-calendar-plus"
                            title="Events Coming Soon"
                            description="The events feature is currently under development. Check back soon!"
                        />
                    </div>
                </template>
            </div>

            <!-- Team Filter Sidebar (Desktop Only) -->
            <aside v-if="activeView === 'bookings'" class="team-sidebar desktop-only">
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <i class="pi pi-users"></i>
                        <span>{{ hasTeamMembers ? 'Team Members' : 'Provider' }}</span>
                    </div>
                    <div class="sidebar-content">
                        <button
                            v-for="member in teamMemberOptions"
                            :key="member.id ?? 'all'"
                            class="team-filter-item"
                            :class="{ active: teamMemberFilter === member.id }"
                            @click="teamMemberFilter = member.id"
                        >
                            <span class="team-name">{{ member.name }}</span>
                            <i v-if="teamMemberFilter === member.id" class="pi pi-check"></i>
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Booking Details Drawer -->
        <BookingDrawer
            v-model:visible="drawerVisible"
            :booking="selectedBooking"
        />

        <!-- Mobile Filter Drawer -->
        <Drawer
            v-model:visible="filterDrawerVisible"
            position="bottom"
            header="Filter by Team Member"
            class="filter-drawer"
            :style="{ height: 'auto', maxHeight: '70vh' }"
        >
            <div class="filter-drawer-content">
                <button
                    v-for="member in teamMemberOptions"
                    :key="member.id ?? 'all'"
                    class="filter-drawer-item"
                    :class="{ active: teamMemberFilter === member.id }"
                    @click="setTeamMemberFilter(member.id)"
                >
                    <span class="filter-drawer-name">{{ member.name }}</span>
                    <i v-if="teamMemberFilter === member.id" class="pi pi-check"></i>
                </button>
            </div>
        </Drawer>
    </ConsoleLayout>
</template>

<style scoped>
/* Dashboard Wrapper - Two Column Layout */
.dashboard-wrapper {
    display: flex;
    gap: 1.5rem;
    max-width: 1200px;
    margin: 0 auto;
}

.dashboard-main {
    flex: 1;
    min-width: 0;
    max-width: 700px;
}

/* Team Sidebar */
.team-sidebar {
    width: 220px;
    flex-shrink: 0;
}

.sidebar-card {
    background-color: white;
    border: 1px solid var(--color-slate-100, #f1f5f9);
    border-radius: 0.75rem;
    overflow: hidden;
    position: sticky;
    top: 5rem; /* Account for topbar height (~64px) + padding */
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--color-slate-100, #f1f5f9);
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-slate-700, #334155);
}

.sidebar-header i {
    color: #106B4F;
    font-size: 0.875rem;
}

.sidebar-content {
    padding: 0.5rem;
}

.team-filter-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.625rem 0.75rem;
    background: none;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
}

.team-filter-item:hover {
    background-color: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-900, #0f172a);
}

.team-filter-item.active {
    background-color: rgba(16, 107, 79, 0.08);
    color: #106B4F;
    font-weight: 500;
}

.team-filter-item i {
    color: #106B4F;
    font-size: 0.75rem;
}

.team-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Header */
.dashboard-header {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

@media (min-width: 640px) {
    .dashboard-header {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.date-display {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
}

/* View Toggle */
.view-toggle {
    display: flex;
    background-color: var(--color-slate-100, #f1f5f9);
    border-radius: 0.5rem;
    padding: 0.25rem;
}

.toggle-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    background: none;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
}

.toggle-btn i {
    font-size: 0.875rem;
}

.toggle-btn:hover:not(.active) {
    color: var(--color-slate-900, #0f172a);
}

.toggle-btn.active {
    background-color: white;
    color: #106B4F;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* Search Section */
.search-section {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.search-wrapper {
    position: relative;
    flex: 1;
}

.search-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-slate-400, #94a3b8);
    font-size: 0.875rem;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding-left: 2.5rem;
    padding-right: 2.5rem;
    height: 44px;
    font-size: 0.9375rem;
    border-radius: 0.625rem;
}

.search-clear {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-slate-100, #f1f5f9);
    border: none;
    border-radius: 50%;
    color: var(--color-slate-500, #64748b);
    cursor: pointer;
    transition: all 0.15s ease;
}

.search-clear:hover {
    background: var(--color-slate-200, #e2e8f0);
    color: var(--color-slate-700, #334155);
}

/* Filter Button (Mobile) */
.filter-btn {
    position: relative;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.625rem;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
}

.filter-btn:hover {
    border-color: var(--color-slate-300, #cbd5e1);
    color: var(--color-slate-900, #0f172a);
}

.filter-btn.has-filter {
    border-color: #106B4F;
    color: #106B4F;
}

.filter-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    background-color: #106B4F;
    border-radius: 50%;
}

/* Mobile/Desktop visibility */
.mobile-only {
    display: flex;
}

.desktop-only {
    display: none;
}

@media (min-width: 1024px) {
    .mobile-only {
        display: none;
    }

    .desktop-only {
        display: block;
    }
}

/* Filter Pills Section */
.filter-pills-section {
    margin-bottom: 0.75rem;
}

.pills-row {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 0.25rem;
}

.pills-row::-webkit-scrollbar {
    display: none;
}

.filter-pill {
    flex-shrink: 0;
    padding: 0.5rem 0.875rem;
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.filter-pill:hover {
    background-color: var(--color-slate-50, #f8fafc);
    border-color: var(--color-slate-300, #cbd5e1);
}

.filter-pill.active {
    background-color: #106B4F;
    border-color: #106B4F;
    color: white;
}

/* Status Pills Section */
.status-pills-section {
    margin-bottom: 1rem;
}

.status-pill {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.4375rem 0.75rem;
    background-color: var(--color-slate-50, #f8fafc);
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.status-pill:hover {
    background-color: var(--color-slate-100, #f1f5f9);
}

.status-pill.active {
    background-color: var(--color-slate-900, #0f172a);
    border-color: var(--color-slate-900, #0f172a);
    color: white;
}

.status-pill.active.status-pending {
    background-color: #f59e0b;
    border-color: #f59e0b;
}

.status-pill.active.status-confirmed {
    background-color: #10b981;
    border-color: #10b981;
}

.status-pill.active.status-completed {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.status-pill.active.status-cancelled {
    background-color: #ef4444;
    border-color: #ef4444;
}

.pill-count {
    padding: 0.0625rem 0.375rem;
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
}

.status-pill:not(.active) .pill-count {
    background-color: var(--color-slate-200, #e2e8f0);
}

/* Booking Groups */
.booking-groups {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.booking-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.date-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 0.25rem;
}

.date-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-slate-700, #334155);
}

.date-label.is-today {
    color: #106B4F;
}

.booking-count {
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--color-slate-100, #f1f5f9);
    border-radius: 50%;
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--color-slate-600, #475569);
}

/* Booking Cards */
.booking-cards {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.booking-card {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.875rem;
    background-color: white;
    border: 1px solid var(--color-slate-100, #f1f5f9);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
    width: 100%;
}

.booking-card:hover {
    border-color: var(--color-slate-200, #e2e8f0);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.booking-card:active {
    transform: scale(0.99);
}

.card-main {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.booking-avatar {
    width: 40px !important;
    height: 40px !important;
    flex-shrink: 0;
}

.booking-avatar-fallback {
    background-color: #106B4F !important;
    color: white !important;
    font-size: 0.75rem !important;
}

.card-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.client-name {
    font-weight: 500;
    font-size: 0.9375rem;
    color: var(--color-slate-900, #0f172a);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.service-name {
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.card-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.time-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    background-color: var(--color-slate-100, #f1f5f9);
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.time-badge i {
    font-size: 0.6875rem;
    color: var(--color-slate-500, #64748b);
}

.status-tag {
    font-size: 0.6875rem;
}

.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 0.5rem;
    border-top: 1px solid var(--color-slate-100, #f1f5f9);
}

.amount {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.card-arrow {
    color: var(--color-slate-400, #94a3b8);
    font-size: 0.75rem;
}

/* Load More */
.load-more-wrapper {
    display: flex;
    justify-content: center;
    padding: 1rem 0;
}

/* Events Placeholder */
.events-placeholder {
    padding: 3rem 1rem;
}

/* Filter Drawer Content */
.filter-drawer-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.filter-drawer-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem;
    background: none;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.9375rem;
    color: var(--color-slate-700, #334155);
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
    width: 100%;
}

.filter-drawer-item:hover {
    background-color: var(--color-slate-50, #f8fafc);
}

.filter-drawer-item.active {
    background-color: rgba(16, 107, 79, 0.08);
    color: #106B4F;
}

.filter-drawer-item i {
    color: #106B4F;
}

/* Mobile optimizations */
@media (max-width: 639px) {
    .dashboard-main {
        padding: 0;
    }

    .search-input {
        height: 42px;
        font-size: 1rem;
    }

    .booking-card {
        padding: 0.75rem;
    }

    .booking-avatar {
        width: 36px !important;
        height: 36px !important;
    }

    .client-name {
        font-size: 0.875rem;
    }

    .service-name {
        font-size: 0.75rem;
    }
}

/* Responsive adjustments */
@media (max-width: 1023px) {
    .dashboard-wrapper {
        flex-direction: column;
    }

    .dashboard-main {
        max-width: none;
    }
}
</style>

<script setup lang="ts">
import { computed, ref } from 'vue';
import MinimalLayout from './components/MinimalLayout.vue';
import Button from 'primevue/button';

interface ServiceBooking {
    type: 'service';
    id: number;
    uuid: string;
    status: string;
    status_label: string;
    date: string;
    formatted_date: string;
    formatted_time: string;
    service: {
        id: number;
        uuid: string;
        name: string;
        duration_minutes: number;
        price: number;
    };
    team_member?: {
        id: number;
        name: string;
        avatar?: string;
    } | null;
    total_amount: number;
    total_amount_display: string;
    deposit_paid: boolean;
    requires_deposit: boolean;
    can_cancel: boolean;
    created_at: string;
}

interface EventBooking {
    type: 'event';
    id: number;
    uuid: string;
    status: string;
    status_label: string;
    date: string;
    formatted_date: string;
    formatted_time: string;
    event: {
        id: number;
        uuid: string;
        name: string;
        slug: string;
        duration_minutes: number;
        price: number;
        location_type: 'virtual' | 'in_person';
        location?: string;
    };
    spots_booked: number;
    total_amount: number;
    total_amount_display: string;
    deposit_paid: boolean;
    requires_deposit: boolean;
    can_cancel: boolean;
    created_at: string;
}

type BookingItem = ServiceBooking | EventBooking;

interface Props {
    provider: {
        id: number;
        business_name: string;
        slug: string;
        domain: string;
    };
    upcomingBookings: BookingItem[];
    pastBookings: BookingItem[];
    user: {
        name: string;
        email: string;
    };
}

const props = defineProps<Props>();

const activeTab = ref<'upcoming' | 'past'>('upcoming');

const getBookingUrl = (booking: BookingItem) => {
    if (booking.type === 'service') {
        return `/book/${booking.uuid}/confirmation`;
    }
    return `/book/event/${booking.uuid}/confirmation`;
};

const isServiceBooking = (booking: BookingItem): booking is ServiceBooking => {
    return booking.type === 'service';
};

const isEventBooking = (booking: BookingItem): booking is EventBooking => {
    return booking.type === 'event';
};

const hasBookings = computed(() => {
    return props.upcomingBookings.length > 0 || props.pastBookings.length > 0;
});

const currentBookings = computed(() => {
    return activeTab.value === 'upcoming' ? props.upcomingBookings : props.pastBookings;
});
</script>

<template>
    <MinimalLayout title="My Bookings">
        <div class="my-bookings-page">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>My Bookings</h1>
                </div>

                <!-- Tabs -->
                <div v-if="hasBookings" class="tabs">
                    <button
                        class="tab"
                        :class="{ active: activeTab === 'upcoming' }"
                        @click="activeTab = 'upcoming'"
                    >
                        Upcoming
                        <span v-if="upcomingBookings.length" class="count">{{ upcomingBookings.length }}</span>
                    </button>
                    <button
                        class="tab"
                        :class="{ active: activeTab === 'past' }"
                        @click="activeTab = 'past'"
                    >
                        Past
                        <span v-if="pastBookings.length" class="count">{{ pastBookings.length }}</span>
                    </button>
                </div>

                <!-- Bookings List -->
                <div v-if="hasBookings && currentBookings.length > 0" class="bookings-list">
                    <AppLink
                        v-for="booking in currentBookings"
                        :key="`${booking.type}-${booking.id}`"
                        :href="getBookingUrl(booking)"
                        class="booking-row"
                    >
                        <div class="booking-info">
                            <span class="booking-name">
                                <template v-if="isServiceBooking(booking)">{{ booking.service.name }}</template>
                                <template v-else-if="isEventBooking(booking)">{{ booking.event.name }}</template>
                            </span>
                            <span class="booking-date">
                                {{ booking.formatted_date }} · {{ booking.formatted_time }}
                            </span>
                        </div>
                        <div class="booking-right">
                            <span class="booking-status" :class="booking.status">{{ booking.status_label }}</span>
                            <i class="pi pi-chevron-right"></i>
                        </div>
                    </AppLink>
                </div>

                <!-- Empty Tab State -->
                <div v-else-if="hasBookings && currentBookings.length === 0" class="empty-tab">
                    <p>No {{ activeTab }} bookings</p>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <p>No bookings yet</p>
                    <div class="empty-actions">
                        <AppLink href="/services">
                            <Button label="Browse Services" class="btn-primary" />
                        </AppLink>
                    </div>
                </div>
            </div>
        </div>
    </MinimalLayout>
</template>

<style scoped>
.my-bookings-page {
    padding: 3rem 0;
}

.page-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--provider-text);
}

.tabs {
    display: flex;
    gap: 0;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.tab {
    flex: 1;
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    font-size: 0.9375rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: all 0.15s;
}

.tab:hover {
    color: var(--provider-text);
}

.tab.active {
    color: var(--provider-text);
    border-bottom-color: var(--provider-text);
}

.tab .count {
    margin-left: 0.375rem;
    font-size: 0.75rem;
    color: #9ca3af;
}

.bookings-list {
    display: flex;
    flex-direction: column;
    border-top: 1px solid #e5e7eb;
}

.booking-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #e5e7eb;
    text-decoration: none;
    transition: background-color 0.15s;
}

.booking-row:hover {
    background-color: #f9fafb;
    margin: 0 -0.5rem;
    padding: 1rem 0.5rem;
}

.booking-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.booking-name {
    font-weight: 500;
    color: var(--provider-text);
}

.booking-date {
    font-size: 0.875rem;
    color: #6b7280;
}

.booking-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.booking-status {
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
}

.booking-status.pending { color: #d97706; }
.booking-status.confirmed { color: #059669; }
.booking-status.completed, .booking-status.attended { color: #3b82f6; }
.booking-status.cancelled, .booking-status.no_show { color: #dc2626; }

.booking-right i {
    color: #9ca3af;
    font-size: 0.875rem;
}

.empty-tab {
    text-align: center;
    padding: 3rem 0;
    color: #6b7280;
}

.empty-state {
    text-align: center;
    padding: 3rem 0;
    color: #6b7280;
}

.empty-state p {
    margin: 0 0 1rem 0;
}

.empty-actions {
    display: flex;
    justify-content: center;
}

:deep(.btn-primary) {
    background-color: var(--provider-text) !important;
    border-color: var(--provider-text) !important;
    border-radius: 0.5rem;
}
</style>

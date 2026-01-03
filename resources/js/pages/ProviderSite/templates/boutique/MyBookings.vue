<script setup lang="ts">
import { computed, ref } from 'vue';
import BoutiqueLayout from './components/BoutiqueLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

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

const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'success';
        case 'completed': return 'info';
        case 'attended': return 'success';
        case 'cancelled': return 'danger';
        case 'no_show': return 'danger';
        default: return 'secondary';
    }
};

const getBookingUrl = (booking: BookingItem) => {
    if (booking.type === 'service') {
        return `/book/${booking.uuid}/confirmation`;
    }
    return `/book/event/${booking.uuid}/confirmation`;
};

const getEventUrl = (booking: EventBooking) => {
    return `/events/${booking.event.slug}`;
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
    <BoutiqueLayout title="My Bookings">
        <div class="my-bookings-page">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <span class="header-eyebrow">Your Account</span>
                    <h1>My Bookings</h1>
                    <p>View and manage your appointments and events</p>
                </div>

                <!-- Tabs -->
                <div v-if="hasBookings" class="tabs-container">
                    <button
                        class="tab-btn"
                        :class="{ active: activeTab === 'upcoming' }"
                        @click="activeTab = 'upcoming'"
                    >
                        Upcoming
                        <span v-if="upcomingBookings.length" class="tab-count">{{ upcomingBookings.length }}</span>
                    </button>
                    <button
                        class="tab-btn"
                        :class="{ active: activeTab === 'past' }"
                        @click="activeTab = 'past'"
                    >
                        Past
                        <span v-if="pastBookings.length" class="tab-count">{{ pastBookings.length }}</span>
                    </button>
                </div>

                <!-- Bookings List -->
                <div v-if="hasBookings && currentBookings.length > 0" class="bookings-list">
                    <div
                        v-for="booking in currentBookings"
                        :key="`${booking.type}-${booking.id}`"
                        class="booking-card"
                        :class="{ 'booking-card--past': activeTab === 'past' }"
                    >
                        <div class="booking-header">
                            <div class="booking-tags">
                                <Tag
                                    :value="booking.type === 'event' ? 'Event' : 'Appointment'"
                                    :severity="booking.type === 'event' ? 'info' : 'secondary'"
                                    class="type-tag"
                                />
                                <Tag
                                    :value="booking.status_label"
                                    :severity="getStatusSeverity(booking.status)"
                                />
                            </div>
                            <span class="booking-amount">{{ booking.total_amount_display }}</span>
                        </div>

                        <h3 class="booking-title">
                            <template v-if="isServiceBooking(booking)">{{ booking.service.name }}</template>
                            <template v-else-if="isEventBooking(booking)">{{ booking.event.name }}</template>
                        </h3>

                        <div class="booking-details">
                            <div class="detail-item">
                                <i class="pi pi-calendar"></i>
                                <span>{{ booking.formatted_date }}</span>
                            </div>
                            <div class="detail-item">
                                <i class="pi pi-clock"></i>
                                <span>{{ booking.formatted_time }}</span>
                            </div>
                            <template v-if="isEventBooking(booking)">
                                <div class="detail-item">
                                    <i class="pi pi-users"></i>
                                    <span>{{ booking.spots_booked }} {{ booking.spots_booked === 1 ? 'spot' : 'spots' }}</span>
                                </div>
                            </template>
                            <template v-else-if="isServiceBooking(booking) && booking.team_member">
                                <div class="detail-item">
                                    <i class="pi pi-user"></i>
                                    <span>with {{ booking.team_member.name }}</span>
                                </div>
                            </template>
                        </div>

                        <div v-if="booking.requires_deposit && !booking.deposit_paid" class="deposit-warning">
                            <i class="pi pi-exclamation-circle"></i>
                            <span>Deposit payment required</span>
                        </div>

                        <div class="booking-actions">
                            <AppLink :href="getBookingUrl(booking)">
                                <Button label="View Details" severity="secondary" outlined size="small" />
                            </AppLink>
                            <template v-if="isEventBooking(booking)">
                                <AppLink :href="getEventUrl(booking)">
                                    <Button label="Event Page" class="btn-book" size="small" />
                                </AppLink>
                            </template>
                            <template v-else-if="isServiceBooking(booking) && activeTab === 'past'">
                                <AppLink :href="`/book?service=${booking.service.id}`">
                                    <Button label="Book Again" class="btn-book" size="small" />
                                </AppLink>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Empty Tab -->
                <div v-else-if="hasBookings && currentBookings.length === 0" class="empty-tab">
                    <i class="pi pi-calendar"></i>
                    <p>No {{ activeTab }} bookings</p>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <i class="pi pi-calendar"></i>
                    <h3>No Bookings Yet</h3>
                    <p>You haven't made any bookings with us yet. Browse our services and events to get started.</p>
                    <div class="empty-actions">
                        <AppLink href="/services">
                            <Button label="Browse Services" class="btn-book" />
                        </AppLink>
                        <AppLink href="/events">
                            <Button label="Browse Events" severity="secondary" outlined />
                        </AppLink>
                    </div>
                </div>
            </div>
        </div>
    </BoutiqueLayout>
</template>

<style scoped>
.my-bookings-page {
    padding: 4rem 0;
}

.page-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 2rem;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.header-eyebrow {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--provider-primary);
    text-transform: uppercase;
    letter-spacing: 0.15em;
    margin-bottom: 0.5rem;
}

.page-header h1 {
    margin: 0 0 0.75rem 0;
    font-size: 2.5rem;
    font-weight: 500;
    color: var(--provider-text);
}

.page-header p {
    margin: 0;
    color: var(--provider-secondary);
    font-weight: 300;
}

.tabs-container {
    display: flex;
    justify-content: center;
    gap: 0;
    margin-bottom: 2rem;
    border-bottom: 1px solid var(--provider-border);
}

.tab-btn {
    padding: 1rem 2rem;
    background: none;
    border: none;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--provider-secondary);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: all 0.2s;
}

.tab-btn:hover {
    color: var(--provider-text);
}

.tab-btn.active {
    color: var(--provider-text);
    border-bottom-color: var(--provider-primary);
}

.tab-count {
    margin-left: 0.5rem;
    padding: 0.125rem 0.5rem;
    background: var(--provider-primary-10);
    color: var(--provider-primary);
    border-radius: 2rem;
    font-size: 0.75rem;
}

.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.booking-card {
    background: var(--provider-surface);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.booking-card--past {
    opacity: 0.7;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.booking-tags {
    display: flex;
    gap: 0.5rem;
}

.type-tag {
    font-size: 0.6875rem;
}

.booking-amount {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text);
}

.booking-title {
    margin: 0 0 0.75rem 0;
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 1.375rem;
    font-weight: 500;
    color: var(--provider-text);
}

.booking-details {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--provider-secondary);
}

.detail-item i {
    color: var(--provider-primary);
    font-size: 0.875rem;
}

.deposit-warning {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: #fef3c7;
    color: #92400e;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    margin-bottom: 1rem;
}

.booking-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--provider-border);
}

:deep(.btn-book) {
    font-weight: 500;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 2rem !important;
}

:deep(.btn-book:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.empty-tab {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--provider-surface);
    border-radius: 1rem;
}

.empty-tab i {
    font-size: 2.5rem;
    color: var(--provider-border);
    margin-bottom: 1rem;
}

.empty-tab p {
    margin: 0;
    color: var(--provider-secondary);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--provider-surface);
    border-radius: 1rem;
}

.empty-state i {
    font-size: 3rem;
    color: var(--provider-border);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 500;
    color: var(--provider-text);
}

.empty-state p {
    margin: 0 0 1.5rem 0;
    color: var(--provider-secondary);
    font-weight: 300;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.empty-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }

    .booking-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .booking-actions {
        flex-wrap: wrap;
    }
}
</style>

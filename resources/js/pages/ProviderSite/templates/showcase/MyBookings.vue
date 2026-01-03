<script setup lang="ts">
import { computed, ref } from 'vue';
import ShowcaseLayout from './components/ShowcaseLayout.vue';
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
    <ShowcaseLayout title="My Bookings">
        <!-- Hero Section -->
        <section class="page-hero">
            <div class="hero-content">
                <h1>MY BOOKINGS</h1>
                <p class="hero-subtitle mono-text">MANAGE YOUR RESERVATIONS</p>
            </div>
        </section>

        <!-- Content -->
        <div class="bookings-content">
            <div class="content-container">
                <!-- Tabs -->
                <div v-if="hasBookings" class="tabs-container">
                    <button
                        class="tab-btn mono-text"
                        :class="{ active: activeTab === 'upcoming' }"
                        @click="activeTab = 'upcoming'"
                    >
                        UPCOMING
                        <span v-if="upcomingBookings.length" class="tab-count">{{ upcomingBookings.length }}</span>
                    </button>
                    <button
                        class="tab-btn mono-text"
                        :class="{ active: activeTab === 'past' }"
                        @click="activeTab = 'past'"
                    >
                        PAST
                        <span v-if="pastBookings.length" class="tab-count">{{ pastBookings.length }}</span>
                    </button>
                </div>

                <!-- Bookings List -->
                <div v-if="hasBookings && currentBookings.length > 0" class="bookings-grid">
                    <div
                        v-for="booking in currentBookings"
                        :key="`${booking.type}-${booking.id}`"
                        class="booking-card"
                        :class="{ 'booking-card--past': activeTab === 'past' }"
                    >
                        <div class="booking-header">
                            <div class="booking-tags">
                                <span class="type-tag mono-text">
                                    {{ booking.type === 'event' ? 'EVENT' : 'APPOINTMENT' }}
                                </span>
                                <Tag
                                    :value="booking.status_label.toUpperCase()"
                                    :severity="getStatusSeverity(booking.status)"
                                />
                            </div>
                            <span class="booking-amount mono-text">{{ booking.total_amount_display }}</span>
                        </div>

                        <h3 class="booking-title">
                            <template v-if="isServiceBooking(booking)">{{ booking.service.name }}</template>
                            <template v-else-if="isEventBooking(booking)">{{ booking.event.name }}</template>
                        </h3>

                        <div class="booking-meta mono-text">
                            <span>{{ booking.formatted_date }}</span>
                            <span class="meta-separator">|</span>
                            <span>{{ booking.formatted_time }}</span>
                            <template v-if="isEventBooking(booking)">
                                <span class="meta-separator">|</span>
                                <span>{{ booking.spots_booked }} {{ booking.spots_booked === 1 ? 'SPOT' : 'SPOTS' }}</span>
                            </template>
                        </div>

                        <div v-if="booking.requires_deposit && !booking.deposit_paid" class="deposit-warning mono-text">
                            DEPOSIT REQUIRED
                        </div>

                        <div class="booking-actions">
                            <AppLink :href="getBookingUrl(booking)">
                                <Button label="VIEW" severity="secondary" outlined size="small" class="btn-view" />
                            </AppLink>
                            <template v-if="isEventBooking(booking)">
                                <AppLink :href="getEventUrl(booking)">
                                    <Button label="EVENT" class="btn-book" size="small" />
                                </AppLink>
                            </template>
                            <template v-else-if="isServiceBooking(booking) && activeTab === 'past'">
                                <AppLink :href="`/book?service=${booking.service.id}`">
                                    <Button label="REBOOK" class="btn-book" size="small" />
                                </AppLink>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Empty Tab -->
                <div v-else-if="hasBookings && currentBookings.length === 0" class="empty-tab">
                    <p class="mono-text">NO {{ activeTab.toUpperCase() }} BOOKINGS</p>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <h3>NO BOOKINGS</h3>
                    <p>Start exploring our services and events.</p>
                    <div class="empty-actions">
                        <AppLink href="/services">
                            <Button label="SERVICES" class="btn-book" />
                        </AppLink>
                        <AppLink href="/events">
                            <Button label="EVENTS" severity="secondary" outlined class="btn-view" />
                        </AppLink>
                    </div>
                </div>
            </div>
        </div>
    </ShowcaseLayout>
</template>

<style scoped>
.page-hero {
    background: var(--provider-text);
    padding: 6rem 2rem;
    text-align: center;
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.page-hero h1 {
    margin: 0;
    font-size: 4rem;
    font-weight: 700;
    color: white;
    letter-spacing: 0.1em;
}

.hero-subtitle {
    margin: 1rem 0 0 0;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.6);
    letter-spacing: 0.2em;
}

.bookings-content {
    padding: 4rem 0;
}

.content-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 2rem;
}

.tabs-container {
    display: flex;
    gap: 0;
    margin-bottom: 2rem;
    border-bottom: 2px solid var(--provider-text);
}

.tab-btn {
    padding: 1rem 2rem;
    background: none;
    border: none;
    font-size: 0.6875rem;
    letter-spacing: 0.15em;
    color: var(--provider-secondary);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.15s;
}

.tab-btn:hover {
    color: var(--provider-text);
}

.tab-btn.active {
    color: var(--provider-text);
    border-bottom-color: var(--provider-text);
}

.tab-count {
    margin-left: 0.5rem;
    padding: 0.125rem 0.5rem;
    background: var(--provider-text);
    color: white;
    font-size: 0.625rem;
}

.bookings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.booking-card {
    background: white;
    border: 1px solid var(--provider-border);
    padding: 1.5rem;
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
    align-items: center;
    gap: 0.75rem;
}

.type-tag {
    font-size: 0.625rem;
    letter-spacing: 0.15em;
    color: var(--provider-secondary);
}

.booking-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-text);
    letter-spacing: 0.05em;
}

.booking-title {
    margin: 0 0 0.75rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: var(--provider-text);
}

.booking-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.6875rem;
    letter-spacing: 0.15em;
    color: var(--provider-secondary);
    margin-bottom: 1rem;
}

.meta-separator {
    opacity: 0.5;
}

.deposit-warning {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    background: #fef3c7;
    color: #92400e;
    font-size: 0.625rem;
    letter-spacing: 0.15em;
    margin-bottom: 1rem;
}

.booking-actions {
    display: flex;
    gap: 0.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--provider-border);
}

:deep(.btn-book) {
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.6875rem;
    letter-spacing: 0.1em;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 0 !important;
}

:deep(.btn-book:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

:deep(.btn-view) {
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.6875rem;
    letter-spacing: 0.1em;
    border-radius: 0 !important;
}

.empty-tab {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-tab p {
    margin: 0;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    color: var(--provider-secondary);
}

.empty-state {
    text-align: center;
    padding: 5rem 2rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    letter-spacing: 0.1em;
}

.empty-state p {
    margin: 0 0 2rem 0;
    color: var(--provider-secondary);
}

.empty-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

@media (max-width: 768px) {
    .page-hero h1 {
        font-size: 2.5rem;
    }

    .bookings-grid {
        grid-template-columns: 1fr;
    }

    .booking-actions {
        flex-wrap: wrap;
    }
}
</style>

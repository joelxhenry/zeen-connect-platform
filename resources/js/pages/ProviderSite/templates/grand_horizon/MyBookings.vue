<script setup lang="ts">
import { computed, ref } from 'vue';
import GrandHorizonLayout from './components/GrandHorizonLayout.vue';
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
    <GrandHorizonLayout title="My Bookings">
        <!-- Hero Section -->
        <section class="page-hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h4 class="hero-eyebrow">YOUR ACCOUNT</h4>
                <h1>My Reservations</h1>
                <p>View and manage your upcoming appointments and events</p>
            </div>
        </section>

        <!-- Content -->
        <div class="bookings-content">
            <div class="content-container">
                <!-- Tabs -->
                <div v-if="hasBookings" class="tabs-container">
                    <button
                        class="tab-btn"
                        :class="{ active: activeTab === 'upcoming' }"
                        @click="activeTab = 'upcoming'"
                    >
                        UPCOMING
                        <span v-if="upcomingBookings.length" class="tab-count">{{ upcomingBookings.length }}</span>
                    </button>
                    <button
                        class="tab-btn"
                        :class="{ active: activeTab === 'past' }"
                        @click="activeTab = 'past'"
                    >
                        PAST
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
                                <span class="type-label">{{ booking.type === 'event' ? 'EVENT' : 'APPOINTMENT' }}</span>
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
                                <div class="detail-icon">
                                    <i class="pi pi-calendar"></i>
                                </div>
                                <div class="detail-text">
                                    <span class="detail-label">Date</span>
                                    <span class="detail-value">{{ booking.formatted_date }}</span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="pi pi-clock"></i>
                                </div>
                                <div class="detail-text">
                                    <span class="detail-label">Time</span>
                                    <span class="detail-value">{{ booking.formatted_time }}</span>
                                </div>
                            </div>
                            <template v-if="isEventBooking(booking)">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="pi pi-users"></i>
                                    </div>
                                    <div class="detail-text">
                                        <span class="detail-label">Spots</span>
                                        <span class="detail-value">{{ booking.spots_booked }}</span>
                                    </div>
                                </div>
                            </template>
                            <template v-else-if="isServiceBooking(booking) && booking.team_member">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="pi pi-user"></i>
                                    </div>
                                    <div class="detail-text">
                                        <span class="detail-label">With</span>
                                        <span class="detail-value">{{ booking.team_member.name }}</span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div v-if="booking.requires_deposit && !booking.deposit_paid" class="deposit-warning">
                            <i class="pi pi-exclamation-circle"></i>
                            <span>Deposit payment required</span>
                        </div>

                        <div class="booking-actions">
                            <AppLink :href="getBookingUrl(booking)">
                                <Button label="View Details" severity="secondary" outlined size="small" class="btn-details" />
                            </AppLink>
                            <template v-if="isEventBooking(booking)">
                                <AppLink :href="getEventUrl(booking)">
                                    <Button label="Event Page" class="btn-reserve" size="small" />
                                </AppLink>
                            </template>
                            <template v-else-if="isServiceBooking(booking) && activeTab === 'past'">
                                <AppLink :href="`/book?service=${booking.service.id}`">
                                    <Button label="Book Again" class="btn-reserve" size="small" />
                                </AppLink>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Empty Tab -->
                <div v-else-if="hasBookings && currentBookings.length === 0" class="empty-tab">
                    <div class="empty-icon">
                        <i class="pi pi-calendar"></i>
                    </div>
                    <p>No {{ activeTab }} reservations</p>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <div class="empty-icon">
                        <i class="pi pi-calendar"></i>
                    </div>
                    <h3>No Reservations Yet</h3>
                    <p>Begin your journey with us by exploring our services and exclusive events.</p>
                    <div class="empty-actions">
                        <AppLink href="/services">
                            <Button label="Explore Services" class="btn-reserve" />
                        </AppLink>
                        <AppLink href="/events">
                            <Button label="View Events" severity="secondary" outlined class="btn-details" />
                        </AppLink>
                    </div>
                </div>
            </div>
        </div>
    </GrandHorizonLayout>
</template>

<style scoped>
.page-hero {
    position: relative;
    background: var(--provider-dark, #1a1a1a);
    padding: 8rem 2rem;
    text-align: center;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(201, 168, 124, 0.1) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
}

.hero-eyebrow {
    margin: 0 0 1rem 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--provider-primary);
    letter-spacing: 0.2em;
}

.page-hero h1 {
    margin: 0 0 1rem 0;
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 3.5rem;
    font-weight: 500;
    color: white;
}

.page-hero p {
    margin: 0;
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 300;
    letter-spacing: 0.02em;
}

.bookings-content {
    padding: 5rem 0;
}

.content-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 3rem;
}

.tabs-container {
    display: flex;
    justify-content: center;
    gap: 0;
    margin-bottom: 3rem;
    border-bottom: 1px solid var(--provider-border);
}

.tab-btn {
    padding: 1.25rem 2.5rem;
    background: none;
    border: none;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.15em;
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
    background: var(--provider-primary);
    color: white;
    font-size: 0.625rem;
}

.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.booking-card {
    background: var(--provider-surface);
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.booking-card--past {
    opacity: 0.7;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}

.booking-tags {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.type-label {
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    color: var(--provider-secondary);
}

.booking-amount {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--provider-primary);
}

.booking-title {
    margin: 0 0 1.5rem 0;
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.75rem;
    font-weight: 500;
    color: var(--provider-text);
}

.booking-details {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.detail-icon {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--provider-primary-10);
}

.detail-icon i {
    color: var(--provider-primary);
    font-size: 1rem;
}

.detail-text {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.detail-label {
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: var(--provider-secondary);
    text-transform: uppercase;
}

.detail-value {
    font-weight: 500;
    color: var(--provider-text);
}

.deposit-warning {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #fef3c7;
    color: #92400e;
    font-size: 0.8125rem;
    margin-bottom: 1.5rem;
}

.deposit-warning i {
    color: #f59e0b;
}

.booking-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--provider-border);
}

:deep(.btn-reserve) {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 0 !important;
}

:deep(.btn-reserve:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

:deep(.btn-details) {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    border-radius: 0 !important;
}

.empty-tab {
    text-align: center;
    padding: 5rem 2rem;
    background: var(--provider-surface);
}

.empty-icon {
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--provider-background);
}

.empty-icon i {
    font-size: 2rem;
    color: var(--provider-border);
}

.empty-tab p {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-secondary);
}

.empty-state {
    text-align: center;
    padding: 6rem 2rem;
    background: var(--provider-surface);
}

.empty-state h3 {
    margin: 0 0 0.75rem 0;
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.75rem;
    font-weight: 500;
    color: var(--provider-text);
}

.empty-state p {
    margin: 0 0 2rem 0;
    font-size: 1rem;
    color: var(--provider-secondary);
    letter-spacing: 0.02em;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.empty-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

@media (max-width: 768px) {
    .page-hero {
        padding: 5rem 1.5rem;
    }

    .page-hero h1 {
        font-size: 2.5rem;
    }

    .content-container {
        padding: 0 1.5rem;
    }

    .booking-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .booking-details {
        gap: 1rem;
    }

    .booking-actions {
        flex-wrap: wrap;
    }
}
</style>

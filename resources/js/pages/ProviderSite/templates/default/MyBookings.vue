<script setup lang="ts">
import { computed } from 'vue';
import DefaultLayout from './components/DefaultLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';

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
</script>

<template>
    <DefaultLayout title="My Bookings">
        <div class="my-bookings-page">
            <div class="page-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>My Bookings</h1>
                    <p>View and manage your bookings with {{ provider.business_name }}</p>
                </div>

                <!-- Bookings Content -->
                <div v-if="hasBookings" class="bookings-content">
                    <TabView>
                        <!-- Upcoming Tab -->
                        <TabPanel>
                            <template #header>
                                <span class="tab-header">
                                    <i class="pi pi-calendar"></i>
                                    Upcoming
                                    <span v-if="upcomingBookings.length > 0" class="tab-count">{{ upcomingBookings.length }}</span>
                                </span>
                            </template>

                            <div v-if="upcomingBookings.length > 0" class="bookings-list">
                                <div v-for="booking in upcomingBookings" :key="`${booking.type}-${booking.id}`" class="booking-card">
                                    <div class="booking-header">
                                        <div class="booking-type">
                                            <Tag
                                                :value="booking.type === 'event' ? 'Event' : 'Appointment'"
                                                :severity="booking.type === 'event' ? 'info' : 'secondary'"
                                            />
                                            <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                                        </div>
                                        <span class="booking-amount">{{ booking.total_amount_display }}</span>
                                    </div>

                                    <div class="booking-body">
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
                                    </div>

                                    <div class="booking-actions">
                                        <AppLink :href="getBookingUrl(booking)">
                                            <Button label="View Details" severity="secondary" outlined size="small" />
                                        </AppLink>
                                        <template v-if="isEventBooking(booking)">
                                            <AppLink :href="getEventUrl(booking)">
                                                <Button label="Event Page" severity="secondary" size="small" />
                                            </AppLink>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="empty-tab">
                                <i class="pi pi-calendar"></i>
                                <p>No upcoming bookings</p>
                                <div class="empty-actions">
                                    <AppLink href="/services">
                                        <Button label="Browse Services" size="small" class="btn-primary" />
                                    </AppLink>
                                    <AppLink href="/events">
                                        <Button label="Browse Events" size="small" severity="secondary" outlined />
                                    </AppLink>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Past Tab -->
                        <TabPanel>
                            <template #header>
                                <span class="tab-header">
                                    <i class="pi pi-history"></i>
                                    Past
                                    <span v-if="pastBookings.length > 0" class="tab-count">{{ pastBookings.length }}</span>
                                </span>
                            </template>

                            <div v-if="pastBookings.length > 0" class="bookings-list">
                                <div v-for="booking in pastBookings" :key="`${booking.type}-${booking.id}`" class="booking-card booking-card--past">
                                    <div class="booking-header">
                                        <div class="booking-type">
                                            <Tag
                                                :value="booking.type === 'event' ? 'Event' : 'Appointment'"
                                                :severity="booking.type === 'event' ? 'info' : 'secondary'"
                                            />
                                            <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                                        </div>
                                        <span class="booking-amount">{{ booking.total_amount_display }}</span>
                                    </div>

                                    <div class="booking-body">
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
                                        </div>
                                    </div>

                                    <div class="booking-actions">
                                        <AppLink :href="getBookingUrl(booking)">
                                            <Button label="View Details" severity="secondary" outlined size="small" />
                                        </AppLink>
                                        <template v-if="isServiceBooking(booking)">
                                            <AppLink :href="`/book?service=${booking.service.id}`">
                                                <Button label="Book Again" size="small" class="btn-primary" />
                                            </AppLink>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="empty-tab">
                                <i class="pi pi-history"></i>
                                <p>No past bookings</p>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <div class="empty-icon">
                        <i class="pi pi-calendar"></i>
                    </div>
                    <h2>No Bookings Yet</h2>
                    <p>You haven't made any bookings with {{ provider.business_name }} yet.</p>
                    <div class="empty-actions">
                        <AppLink href="/services">
                            <Button label="Browse Services" class="btn-primary" />
                        </AppLink>
                        <AppLink href="/events">
                            <Button label="Browse Events" severity="secondary" outlined />
                        </AppLink>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

<style scoped>
.my-bookings-page {
    padding: 2rem 0 4rem;
    background-color: #f9fafb;
    min-height: 100%;
}

.page-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--provider-text);
}

.page-header p {
    margin: 0;
    color: #6b7280;
}

.bookings-content {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.tab-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tab-count {
    padding: 0.125rem 0.5rem;
    background: var(--provider-primary-10);
    color: var(--provider-primary);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
}

.booking-card {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
}

.booking-card--past {
    opacity: 0.8;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.booking-type {
    display: flex;
    gap: 0.5rem;
}

.booking-amount {
    font-weight: 600;
    color: var(--provider-primary);
}

.booking-body {
    margin-bottom: 1rem;
}

.booking-title {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--provider-text);
}

.booking-details {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.detail-item i {
    color: #9ca3af;
}

.deposit-warning {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding: 0.5rem 0.75rem;
    background: #fef3c7;
    color: #92400e;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
}

.booking-actions {
    display: flex;
    gap: 0.5rem;
}

.empty-tab {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.empty-tab i {
    font-size: 2.5rem;
    color: #d1d5db;
    margin-bottom: 0.75rem;
}

.empty-tab p {
    margin: 0 0 1rem 0;
}

.empty-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-icon {
    width: 4rem;
    height: 4rem;
    border-radius: 9999px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.empty-icon i {
    font-size: 1.5rem;
    color: #9ca3af;
}

.empty-state h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--provider-text);
}

.empty-state p {
    margin: 0 0 1.5rem 0;
    color: #6b7280;
}

:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

@media (max-width: 640px) {
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

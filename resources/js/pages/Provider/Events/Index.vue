<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import { ConsoleEmptyState, ConsoleButton } from '@/components/console';
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import providerRoutes from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
}

interface Occurrence {
    id: number;
    uuid: string;
    start_datetime: string;
    date_display: string;
    time_display: string;
    spots_remaining: number;
    is_full: boolean;
    status: string;
}

interface Event {
    id: number;
    uuid: string;
    name: string;
    description: string;
    event_type: string;
    event_type_label: string;
    location_type: string;
    location_type_label: string;
    location_display: string;
    duration_minutes: number;
    duration_display: string;
    capacity: number | null;
    capacity_display: string;
    price: number;
    price_display: string;
    status: string;
    status_label: string;
    status_color: string;
    is_active: boolean;
    is_recurring: boolean;
    is_published: boolean;
    occurrences?: Occurrence[];
    next_occurrence?: Occurrence | null;
    occurrences_count: number;
    bookings_count: number;
    cover_url?: string;
    cover_thumbnail?: string;
}

interface Props {
    events: Event[];
    stats: {
        total: number;
        published: number;
        draft: number;
    };
    categories: Category[];
}

const props = defineProps<Props>();
const confirm = useConfirm();

// Filters
const searchQuery = ref('');
const statusFilter = ref<'all' | 'published' | 'draft'>('all');
const typeFilter = ref<'all' | 'one_time' | 'recurring'>('all');

// Status pills
const statusPills = computed(() => [
    { label: 'All', value: 'all' as const, count: props.stats.total },
    { label: 'Published', value: 'published' as const, count: props.stats.published },
    { label: 'Draft', value: 'draft' as const, count: props.stats.draft },
]);

// Type pills
const typePills = computed(() => [
    { label: 'All Types', value: 'all' as const },
    { label: 'One-time', value: 'one_time' as const },
    { label: 'Recurring', value: 'recurring' as const },
]);

// Filtered events
const filteredEvents = computed(() => {
    let result = [...props.events];

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (e) =>
                e.name.toLowerCase().includes(query) ||
                e.description?.toLowerCase().includes(query) ||
                e.location_display?.toLowerCase().includes(query)
        );
    }

    // Apply status filter
    if (statusFilter.value === 'published') {
        result = result.filter((e) => e.status === 'published');
    } else if (statusFilter.value === 'draft') {
        result = result.filter((e) => e.status === 'draft');
    }

    // Apply type filter
    if (typeFilter.value !== 'all') {
        result = result.filter((e) => e.event_type === typeFilter.value);
    }

    return result;
});

const clearSearch = () => {
    searchQuery.value = '';
};

const toggleEventActive = (event: Event) => {
    router.post(
        resolveUrl(providerRoutes.events.toggleActive.url(event.uuid)),
        {},
        { preserveScroll: true }
    );
};

const editEvent = (event: Event) => {
    router.get(resolveUrl(providerRoutes.events.edit.url(event.uuid)));
};

const publishEvent = (event: Event) => {
    router.post(resolveUrl(providerRoutes.events.publish.url(event.uuid)), {}, {
        preserveScroll: true,
    });
};

const cancelEvent = (event: Event) => {
    confirm.require({
        message: `Are you sure you want to cancel "${event.name}"? This will cancel all upcoming occurrences and notify attendees.`,
        header: 'Cancel Event',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Keep Event',
        acceptLabel: 'Cancel Event',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(resolveUrl(providerRoutes.events.cancel.url(event.uuid)), {}, {
                preserveScroll: true,
            });
        },
    });
};

const deleteEvent = (event: Event) => {
    confirm.require({
        message: `Are you sure you want to delete "${event.name}"? This action cannot be undone.`,
        header: 'Delete Event',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(resolveUrl(providerRoutes.events.destroy.url(event.uuid)), {
                preserveScroll: true,
            });
        },
    });
};

const createEvent = () => {
    router.get(resolveUrl(providerRoutes.events.create.url()));
};

const formatNextOccurrence = (event: Event) => {
    if (!event.next_occurrence) return 'No upcoming dates';
    return event.next_occurrence.datetime_display || event.next_occurrence.date_display;
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'published':
            return 'status-published';
        case 'draft':
            return 'status-draft';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return '';
    }
};
</script>

<template>
    <ConsoleLayout title="Events">
        <ConfirmDialog />

        <div class="events-page">
            <!-- Header -->
            <div class="page-header">
                <div class="header-info">
                    <h1 class="page-title">Events</h1>
                    <p class="event-count">{{ stats.total }} events</p>
                </div>
                <ConsoleButton
                    label="Add Event"
                    icon="pi pi-plus"
                    variant="primary"
                    @click="createEvent"
                />
            </div>

            <!-- Search -->
            <div class="search-section">
                <div class="search-wrapper">
                    <i class="pi pi-search search-icon"></i>
                    <InputText
                        v-model="searchQuery"
                        placeholder="Search events..."
                        class="search-input"
                    />
                    <button v-if="searchQuery" class="search-clear" @click="clearSearch">
                        <i class="pi pi-times"></i>
                    </button>
                </div>
            </div>

            <!-- Type Filter Pills -->
            <div class="filter-pills-section">
                <div class="pills-row">
                    <button
                        v-for="pill in typePills"
                        :key="pill.value"
                        class="filter-pill"
                        :class="{ active: typeFilter === pill.value }"
                        @click="typeFilter = pill.value"
                    >
                        <i v-if="pill.value === 'recurring'" class="pi pi-replay"></i>
                        <i v-else-if="pill.value === 'one_time'" class="pi pi-calendar"></i>
                        {{ pill.label }}
                    </button>
                </div>
            </div>

            <!-- Status Pills -->
            <div class="status-pills-section">
                <div class="pills-row">
                    <button
                        v-for="pill in statusPills"
                        :key="pill.value"
                        class="status-pill"
                        :class="{
                            active: statusFilter === pill.value,
                            [`status-${pill.value}`]: true,
                        }"
                        @click="statusFilter = pill.value"
                    >
                        {{ pill.label }}
                        <span class="pill-count">{{ pill.count }}</span>
                    </button>
                </div>
            </div>

            <!-- Events List -->
            <div class="events-list">
                <ConsoleEmptyState
                    v-if="filteredEvents.length === 0"
                    icon="pi pi-calendar"
                    :title="searchQuery ? 'No events found' : 'No events yet'"
                    :description="
                        searchQuery
                            ? 'Try a different search term'
                            : 'Create your first event to start accepting registrations'
                    "
                    :action-label="!searchQuery ? 'Add Event' : undefined"
                    :action-href="!searchQuery ? resolveUrl(providerRoutes.events.create.url()) : undefined"
                    action-icon="pi pi-plus"
                />

                <div v-else class="event-cards">
                    <div
                        v-for="event in filteredEvents"
                        :key="event.uuid"
                        class="event-card"
                        :class="{ inactive: !event.is_active, cancelled: event.status === 'cancelled' }"
                    >
                        <div class="card-main" @click="editEvent(event)">
                            <div
                                v-if="event.cover_thumbnail"
                                class="event-image"
                                :style="{ backgroundImage: `url(${event.cover_thumbnail})` }"
                            ></div>
                            <div v-else class="event-image-placeholder">
                                <i class="pi pi-calendar"></i>
                            </div>
                            <div class="card-info">
                                <div class="card-header">
                                    <span class="event-name">{{ event.name }}</span>
                                    <span class="event-status" :class="getStatusClass(event.status)">
                                        {{ event.status_label }}
                                    </span>
                                </div>
                                <div class="event-meta">
                                    <span class="event-type">
                                        <i :class="event.is_recurring ? 'pi pi-replay' : 'pi pi-calendar'"></i>
                                        {{ event.event_type_label }}
                                    </span>
                                    <span class="event-location">
                                        <i :class="event.location_type === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker'"></i>
                                        {{ event.location_display }}
                                    </span>
                                </div>
                                <div class="event-next">
                                    <i class="pi pi-clock"></i>
                                    {{ formatNextOccurrence(event) }}
                                </div>
                            </div>
                        </div>

                        <div class="card-actions">
                            <div class="card-stats">
                                <span class="event-price">{{ event.price_display }}</span>
                                <span class="event-capacity">
                                    <i class="pi pi-users"></i>
                                    {{ event.capacity_display }}
                                </span>
                                <span class="event-bookings">
                                    {{ event.bookings_count }} registered
                                </span>
                            </div>
                            <div class="action-buttons">
                                <ToggleSwitch
                                    :modelValue="event.is_active"
                                    @update:modelValue="toggleEventActive(event)"
                                    class="active-switch"
                                />
                                <button
                                    v-if="event.status === 'draft'"
                                    class="action-btn publish-btn"
                                    @click="publishEvent(event)"
                                    title="Publish event"
                                >
                                    <i class="pi pi-check-circle"></i>
                                </button>
                                <button
                                    class="action-btn edit-btn"
                                    @click="editEvent(event)"
                                    title="Edit event"
                                >
                                    <i class="pi pi-pencil"></i>
                                </button>
                                <button
                                    v-if="event.status === 'published'"
                                    class="action-btn cancel-btn"
                                    @click="cancelEvent(event)"
                                    title="Cancel event"
                                >
                                    <i class="pi pi-times-circle"></i>
                                </button>
                                <button
                                    v-else
                                    class="action-btn delete-btn"
                                    @click="deleteEvent(event)"
                                    title="Delete event"
                                >
                                    <i class="pi pi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.events-page {
    max-width: 800px;
    margin: 0 auto;
}

/* Header */
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.header-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.page-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.event-count {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

/* Search Section */
.search-section {
    margin-bottom: 1rem;
}

.search-wrapper {
    position: relative;
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
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
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
    background-color: #106b4f;
    border-color: #106b4f;
    color: white;
}

.filter-pill i {
    font-size: 0.75rem;
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

.status-pill.active.status-published {
    background-color: #10b981;
    border-color: #10b981;
}

.status-pill.active.status-draft {
    background-color: #6b7280;
    border-color: #6b7280;
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

/* Event Cards */
.event-cards {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.event-card {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
    background-color: white;
    border: 1px solid var(--color-slate-100, #f1f5f9);
    border-radius: 0.75rem;
    transition: all 0.15s ease;
}

.event-card:hover {
    border-color: var(--color-slate-200, #e2e8f0);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.event-card.inactive {
    opacity: 0.7;
}

.event-card.cancelled {
    opacity: 0.6;
}

.event-card.cancelled .event-name {
    text-decoration: line-through;
}

.card-main {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    cursor: pointer;
}

.event-image {
    width: 64px;
    height: 64px;
    border-radius: 0.5rem;
    background-size: cover;
    background-position: center;
    flex-shrink: 0;
}

.event-image-placeholder {
    width: 64px;
    height: 64px;
    border-radius: 0.5rem;
    background-color: var(--color-slate-100, #f1f5f9);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.event-image-placeholder i {
    font-size: 1.5rem;
    color: var(--color-slate-400, #94a3b8);
}

.card-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.event-name {
    font-weight: 500;
    font-size: 1rem;
    color: var(--color-slate-900, #0f172a);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.event-status {
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.event-status.status-published {
    background-color: #d1fae5;
    color: #065f46;
}

.event-status.status-draft {
    background-color: #f1f5f9;
    color: #475569;
}

.event-status.status-cancelled {
    background-color: #fee2e2;
    color: #991b1b;
}

.event-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.event-type,
.event-location {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.event-type i,
.event-location i {
    font-size: 0.6875rem;
}

.event-next {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #106b4f;
    font-weight: 500;
}

.event-next i {
    font-size: 0.6875rem;
}

.card-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 0.75rem;
    border-top: 1px solid var(--color-slate-100, #f1f5f9);
}

.card-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.event-price {
    font-size: 1rem;
    font-weight: 600;
    color: #106b4f;
}

.event-capacity,
.event-bookings {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.event-capacity i {
    font-size: 0.6875rem;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.active-switch {
    margin-right: 0.5rem;
}

.action-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.375rem;
    color: var(--color-slate-500, #64748b);
    cursor: pointer;
    transition: all 0.15s ease;
}

.action-btn:hover {
    background-color: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-700, #334155);
}

.action-btn.publish-btn:hover {
    border-color: #10b981;
    color: #10b981;
}

.action-btn.edit-btn:hover {
    border-color: #106b4f;
    color: #106b4f;
}

.action-btn.cancel-btn:hover,
.action-btn.delete-btn:hover {
    border-color: #ef4444;
    color: #ef4444;
}

/* Mobile optimizations */
@media (max-width: 639px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }

    .event-image,
    .event-image-placeholder {
        width: 56px;
        height: 56px;
    }

    .event-name {
        font-size: 0.9375rem;
    }

    .card-actions {
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .card-stats {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
}
</style>

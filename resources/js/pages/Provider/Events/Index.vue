<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useWindowSize } from '@vueuse/core';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsoleEmptyState,
    FloatingActionButton,
    EventListItem,
    InlinePageTitle,
} from '@/components/console';
import InputText from 'primevue/inputtext';
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
// Mobile detection
const { width } = useWindowSize();
const isMobile = computed(() => width.value < 640);
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
            <InlinePageTitle
                title="Events"
                :count="stats.total"
                count-label="events"
            />
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

                <div v-else class="event-list">
                    <EventListItem
                        v-for="event in filteredEvents"
                        :key="event.uuid"
                        :event="event"
                        :compact="isMobile"
                        @edit="editEvent"
                        @publish="publishEvent"
                        @cancel="cancelEvent"
                        @delete="deleteEvent"
                        @toggle="toggleEventActive"
                    />
                </div>
            </div>

            <!-- Floating Action Button -->
            <FloatingActionButton
                icon="pi pi-plus"
                label="Add Event"
                @click="createEvent"
            />
        </div>
    </ConsoleLayout>
</template>
<style scoped>
.events-page {
    max-width: 800px;
    margin: 0 auto;
    padding-bottom: 5rem; /* Space for FAB */
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
/* Event List */
.event-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
</style>

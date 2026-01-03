<script setup lang="ts">
import MinimalLayout from './components/MinimalLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

interface EventOccurrence {
    id: number;
    start_datetime: string;
    end_datetime: string;
    formatted_date: string;
    formatted_time: string;
    spots_remaining: number;
}

interface EventItem {
    id: number;
    uuid: string;
    slug: string;
    name: string;
    description?: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    capacity: number;
    event_type: 'one_time' | 'recurring';
    location_type: 'virtual' | 'in_person';
    location?: string;
    display_image?: string;
    categories: Array<{ id: number; name: string; slug: string }>;
    next_occurrence?: EventOccurrence;
    occurrences_count: number;
}

interface EventCategory {
    category: {
        id: number;
        name: string;
        slug: string;
    } | null;
    events: EventItem[];
}

interface Props {
    provider: {
        id: number;
        business_name: string;
        slug: string;
        domain: string;
    };
    eventsByCategory: EventCategory[];
    hasEvents: boolean;
}

const props = defineProps<Props>();

const getEventUrl = (slug: string) => `/events/${slug}`;
const getBookEventUrl = (eventId: number) => `/book?event=${eventId}`;

const getLocationLabel = (locationType: 'virtual' | 'in_person', location?: string) => {
    if (locationType === 'virtual') return 'Virtual';
    return location || 'In Person';
};
</script>

<template>
    <MinimalLayout title="Events">
        <div class="events-page">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>Events</h1>
                </div>

                <!-- Events List -->
                <div v-if="hasEvents" class="events-list">
                    <template v-for="(categoryGroup, index) in eventsByCategory" :key="categoryGroup.category?.id ?? `uncategorized-${index}`">
                        <AppLink
                            v-for="event in categoryGroup.events"
                            :key="event.id"
                            :href="getEventUrl(event.slug)"
                            class="event-row"
                        >
                            <div class="event-info">
                                <span class="event-name">{{ event.name }}</span>
                                <span v-if="event.next_occurrence" class="event-date">
                                    {{ event.next_occurrence.formatted_date }} · {{ event.next_occurrence.formatted_time }}
                                </span>
                                <span v-else class="event-date text-muted">No upcoming dates</span>
                            </div>
                            <div class="event-right">
                                <span class="event-price">{{ event.price_display }}</span>
                                <i class="pi pi-chevron-right"></i>
                            </div>
                        </AppLink>
                    </template>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <p>No upcoming events.</p>
                </div>
            </div>
        </div>
    </MinimalLayout>
</template>

<style scoped>
.events-page {
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

.events-list {
    display: flex;
    flex-direction: column;
    border-top: 1px solid #e5e7eb;
}

.event-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #e5e7eb;
    text-decoration: none;
    transition: background-color 0.15s;
}

.event-row:hover {
    background-color: #f9fafb;
    margin: 0 -0.5rem;
    padding: 1rem 0.5rem;
}

.event-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.event-name {
    font-size: 1rem;
    font-weight: 500;
    color: var(--provider-text);
}

.event-date {
    font-size: 0.875rem;
    color: #6b7280;
}

.text-muted {
    color: #9ca3af;
}

.event-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.event-price {
    font-size: 1rem;
    font-weight: 500;
    color: var(--provider-text);
}

.event-right i {
    color: #9ca3af;
    font-size: 0.875rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 0;
    color: #6b7280;
}
</style>

<script setup lang="ts">
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
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

const getLocationIcon = (locationType: 'virtual' | 'in_person') => {
    return locationType === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker';
};

const getLocationLabel = (locationType: 'virtual' | 'in_person', location?: string) => {
    if (locationType === 'virtual') return 'Virtual Event';
    return location || 'In Person';
};
</script>

<template>
    <ProviderSiteLayout title="Events">
        <div class="events-page">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <h1>Upcoming Events</h1>
                    <p>Browse and book our upcoming events</p>
                </div>

                <!-- Events by Category -->
                <div class="categories-list">
                    <div v-for="categoryGroup in eventsByCategory" :key="categoryGroup.category?.id ?? 'uncategorized'" class="category-section">
                        <div class="category-header">
                            <h2>{{ categoryGroup.category?.name ?? 'Other Events' }}</h2>
                            <span class="event-count">{{ categoryGroup.events.length }} {{ categoryGroup.events.length === 1 ? 'event' : 'events' }}</span>
                        </div>
                        <div class="events-list">
                            <div v-for="event in categoryGroup.events" :key="event.id" class="event-item">
                                <div v-if="event.display_image" class="event-image">
                                    <img :src="event.display_image" :alt="event.name" />
                                    <Tag
                                        v-if="event.event_type === 'recurring'"
                                        value="Recurring"
                                        severity="info"
                                        class="event-type-tag"
                                    />
                                </div>
                                <div class="event-content">
                                    <div class="event-info">
                                        <AppLink :href="getEventUrl(event.slug)" class="event-title-link">
                                            <h3>{{ event.name }}</h3>
                                        </AppLink>
                                        <p v-if="event.description" class="description">{{ event.description }}</p>

                                        <div class="event-meta">
                                            <span class="meta-item">
                                                <i class="pi pi-clock"></i>
                                                {{ event.duration_display }}
                                            </span>
                                            <span class="meta-item">
                                                <i :class="getLocationIcon(event.location_type)"></i>
                                                {{ getLocationLabel(event.location_type, event.location) }}
                                            </span>
                                            <span v-if="event.capacity" class="meta-item">
                                                <i class="pi pi-users"></i>
                                                {{ event.capacity }} spots
                                            </span>
                                        </div>

                                        <!-- Next occurrence info -->
                                        <div v-if="event.next_occurrence" class="next-occurrence">
                                            <i class="pi pi-calendar"></i>
                                            <span class="occurrence-date">{{ event.next_occurrence.formatted_date }}</span>
                                            <span class="occurrence-time">at {{ event.next_occurrence.formatted_time }}</span>
                                            <Tag
                                                v-if="event.next_occurrence.spots_remaining <= 5 && event.next_occurrence.spots_remaining > 0"
                                                :value="`${event.next_occurrence.spots_remaining} spots left`"
                                                severity="warn"
                                                class="spots-tag"
                                            />
                                            <Tag
                                                v-else-if="event.next_occurrence.spots_remaining <= 0"
                                                value="Sold Out"
                                                severity="danger"
                                                class="spots-tag"
                                            />
                                        </div>
                                        <div v-else class="no-upcoming">
                                            <i class="pi pi-info-circle"></i>
                                            <span>No upcoming dates scheduled</span>
                                        </div>
                                    </div>

                                    <div class="event-actions">
                                        <span class="price">{{ event.price_display }}</span>
                                        <div class="action-buttons">
                                            <AppLink :href="getEventUrl(event.slug)">
                                                <Button label="View Details" severity="secondary" outlined size="small" />
                                            </AppLink>
                                            <AppLink v-if="event.next_occurrence && event.next_occurrence.spots_remaining > 0" :href="getBookEventUrl(event.id)">
                                                <Button label="Book Now" size="small" class="btn-primary" />
                                            </AppLink>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!hasEvents" class="empty-state">
                    <i class="pi pi-calendar"></i>
                    <h3>No upcoming events</h3>
                    <p>Check back later for new events from {{ provider.business_name }}.</p>
                </div>
            </div>
        </div>
    </ProviderSiteLayout>
</template>

<style scoped>
.events-page {
    padding: 2rem 0 4rem;
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

.categories-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.category-section {
    background: white;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.category-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.category-header h2 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text);
    flex: 1;
}

.event-count {
    font-size: 0.75rem;
    color: #9ca3af;
}

.events-list {
    display: flex;
    flex-direction: column;
}

.event-item {
    display: flex;
    gap: 1.5rem;
    padding: 1.25rem;
    border-bottom: 1px solid #f3f4f6;
}

.event-item:last-child {
    border-bottom: none;
}

.event-image {
    position: relative;
    width: 160px;
    height: 120px;
    flex-shrink: 0;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #f3f4f6;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-type-tag {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
}

.event-content {
    flex: 1;
    display: flex;
    gap: 1.5rem;
    min-width: 0;
}

.event-info {
    flex: 1;
    min-width: 0;
}

.event-title-link {
    text-decoration: none;
}

.event-title-link:hover h3 {
    color: var(--provider-primary);
}

.event-info h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text);
    transition: color 0.15s;
}

.event-info .description {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: #6b7280;
}

.meta-item i {
    font-size: 0.875rem;
    color: #9ca3af;
}

.next-occurrence {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.next-occurrence i {
    color: var(--provider-primary);
}

.occurrence-date {
    font-weight: 500;
    color: var(--provider-text);
}

.occurrence-time {
    color: #6b7280;
}

.spots-tag {
    margin-left: auto;
}

.no-upcoming {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #9ca3af;
}

.event-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
    flex-shrink: 0;
}

.event-actions .price {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-primary);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

/* Primary button styling */
:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-state i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--provider-text);
}

.empty-state p {
    margin: 0;
    color: #6b7280;
}

@media (max-width: 768px) {
    .event-item {
        flex-direction: column;
    }

    .event-image {
        width: 100%;
        height: 180px;
    }

    .event-content {
        flex-direction: column;
        gap: 1rem;
    }

    .event-actions {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .action-buttons {
        flex-wrap: wrap;
    }
}
</style>

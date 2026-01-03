<script setup lang="ts">
import ArchitectBoldLayout from './components/ArchitectBoldLayout.vue';
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
    if (locationType === 'virtual') return 'VIRTUAL';
    return location?.toUpperCase() || 'IN PERSON';
};
</script>

<template>
    <ArchitectBoldLayout title="Events">
        <!-- Hero Section -->
        <section class="page-hero">
            <div class="hero-container">
                <h1>UPCOMING EVENTS</h1>
                <p>Join our exclusive events and experiences</p>
            </div>
        </section>

        <!-- Events Content -->
        <div class="events-content">
            <div class="content-container">
                <template v-if="hasEvents">
                    <div
                        v-for="(categoryGroup, index) in eventsByCategory"
                        :key="categoryGroup.category?.id ?? `uncategorized-${index}`"
                        class="category-section"
                    >
                        <div class="category-header">
                            <h2>{{ categoryGroup.category?.name?.toUpperCase() ?? 'OTHER EVENTS' }}</h2>
                            <span class="event-count">{{ categoryGroup.events.length }} events</span>
                        </div>

                        <div class="events-grid">
                            <div
                                v-for="event in categoryGroup.events"
                                :key="event.id"
                                class="event-card"
                            >
                                <div v-if="event.display_image" class="event-image">
                                    <img :src="event.display_image" :alt="event.name" />
                                    <Tag
                                        v-if="event.event_type === 'recurring'"
                                        value="RECURRING"
                                        class="event-badge"
                                    />
                                </div>
                                <div class="event-body">
                                    <AppLink :href="getEventUrl(event.slug)" class="event-title">
                                        {{ event.name }}
                                    </AppLink>

                                    <div class="event-meta">
                                        <span>{{ event.duration_display }}</span>
                                        <span>{{ getLocationLabel(event.location_type, event.location) }}</span>
                                    </div>

                                    <div v-if="event.next_occurrence" class="event-schedule">
                                        {{ event.next_occurrence.formatted_date }} · {{ event.next_occurrence.formatted_time }}
                                    </div>
                                    <div v-else class="event-schedule text-muted">
                                        No upcoming dates
                                    </div>

                                    <div class="event-footer">
                                        <span class="event-price">{{ event.price_display }}</span>
                                        <AppLink v-if="event.next_occurrence && event.next_occurrence.spots_remaining > 0" :href="getBookEventUrl(event.id)">
                                            <Button label="BOOK" class="btn-primary" size="small" />
                                        </AppLink>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <h3>NO UPCOMING EVENTS</h3>
                    <p>Check back later for new events.</p>
                </div>
            </div>
        </div>
    </ArchitectBoldLayout>
</template>

<style scoped>
.page-hero {
    background: var(--provider-text);
    padding: 4rem 2rem;
    text-align: center;
}

.hero-container {
    max-width: 800px;
    margin: 0 auto;
}

.page-hero h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    letter-spacing: 0.1em;
}

.page-hero p {
    margin: 0;
    color: rgba(255, 255, 255, 0.7);
    font-size: 1rem;
}

.events-content {
    padding: 3rem 0;
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.category-section {
    margin-bottom: 3rem;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--provider-text);
}

.category-header h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 0.1em;
}

.event-count {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.event-card {
    background: white;
    border: 1px solid var(--provider-border);
}

.event-image {
    position: relative;
    height: 180px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    background: var(--provider-text) !important;
    color: white !important;
    font-size: 0.6875rem;
    letter-spacing: 0.05em;
}

.event-body {
    padding: 1.25rem;
}

.event-title {
    display: block;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text);
    text-decoration: none;
    margin-bottom: 0.5rem;
}

.event-title:hover {
    color: var(--provider-primary);
}

.event-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.75rem;
}

.event-schedule {
    font-size: 0.875rem;
    color: var(--provider-text);
    margin-bottom: 1rem;
}

.text-muted {
    color: #9ca3af;
}

.event-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--provider-border);
}

.event-price {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--provider-text);
}

:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 0 !important;
    font-weight: 600;
    letter-spacing: 0.05em;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    letter-spacing: 0.1em;
}

.empty-state p {
    margin: 0;
    color: #6b7280;
}

@media (max-width: 768px) {
    .page-hero h1 {
        font-size: 1.75rem;
    }

    .content-container {
        padding: 0 1rem;
    }

    .category-header {
        flex-direction: column;
        gap: 0.25rem;
        align-items: flex-start;
    }
}
</style>

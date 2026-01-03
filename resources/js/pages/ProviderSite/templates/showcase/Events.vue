<script setup lang="ts">
import ShowcaseLayout from './components/ShowcaseLayout.vue';
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
    <ShowcaseLayout title="Events">
        <!-- Hero Section -->
        <section class="page-hero">
            <div class="hero-content">
                <h1>EVENTS</h1>
                <p class="hero-subtitle">EXCLUSIVE EXPERIENCES & WORKSHOPS</p>
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
                            <span class="event-count">{{ categoryGroup.events.length }}</span>
                        </div>

                        <div class="events-grid">
                            <div
                                v-for="event in categoryGroup.events"
                                :key="event.id"
                                class="event-card"
                            >
                                <div v-if="event.display_image" class="event-image">
                                    <img :src="event.display_image" :alt="event.name" />
                                    <div class="image-overlay">
                                        <Tag
                                            v-if="event.event_type === 'recurring'"
                                            value="RECURRING"
                                            class="event-badge"
                                        />
                                    </div>
                                </div>
                                <div class="event-body">
                                    <div class="event-meta-top">
                                        <span class="meta-item">{{ event.duration_display }}</span>
                                        <span class="meta-separator">|</span>
                                        <span class="meta-item">{{ getLocationLabel(event.location_type, event.location) }}</span>
                                    </div>

                                    <AppLink :href="getEventUrl(event.slug)" class="event-title">
                                        {{ event.name }}
                                    </AppLink>

                                    <div v-if="event.next_occurrence" class="event-schedule">
                                        <span class="schedule-date">{{ event.next_occurrence.formatted_date }}</span>
                                        <span class="schedule-time">{{ event.next_occurrence.formatted_time }}</span>
                                    </div>
                                    <div v-else class="event-schedule no-dates">
                                        NO UPCOMING DATES
                                    </div>

                                    <div class="event-footer">
                                        <span class="event-price mono-text">{{ event.price_display }}</span>
                                        <AppLink v-if="event.next_occurrence && event.next_occurrence.spots_remaining > 0" :href="getBookEventUrl(event.id)">
                                            <Button label="BOOK" class="btn-book" size="small" />
                                        </AppLink>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <h3>NO EVENTS</h3>
                    <p>Check back later for upcoming events.</p>
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
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.6);
    letter-spacing: 0.2em;
}

.events-content {
    padding: 4rem 0;
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.category-section {
    margin-bottom: 4rem;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--provider-text);
}

.category-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: 0.1em;
}

.event-count {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.875rem;
    color: var(--provider-secondary);
    letter-spacing: 0.1em;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.event-card {
    background: white;
    border: 1px solid var(--provider-border);
}

.event-image {
    position: relative;
    height: 220px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.event-card:hover .event-image img {
    transform: scale(1.05);
}

.image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 60%, rgba(0, 0, 0, 0.4));
}

.event-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: var(--provider-text) !important;
    color: white !important;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.625rem;
    letter-spacing: 0.1em;
    border-radius: 0;
}

.event-body {
    padding: 1.5rem;
}

.event-meta-top {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.6875rem;
    color: var(--provider-secondary);
    letter-spacing: 0.1em;
}

.meta-separator {
    opacity: 0.5;
}

.event-title {
    display: block;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--provider-text);
    text-decoration: none;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.event-title:hover {
    color: var(--provider-primary);
}

.event-schedule {
    font-size: 0.875rem;
    color: var(--provider-text);
    margin-bottom: 1.5rem;
}

.schedule-date {
    font-weight: 500;
}

.schedule-time {
    color: var(--provider-secondary);
    margin-left: 0.5rem;
}

.event-schedule.no-dates {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    color: var(--provider-secondary);
    letter-spacing: 0.1em;
}

.event-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.5rem;
    border-top: 1px solid var(--provider-border);
}

.event-price {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-text);
    letter-spacing: 0.05em;
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
    margin: 0;
    color: var(--provider-secondary);
}

@media (max-width: 768px) {
    .page-hero h1 {
        font-size: 2.5rem;
    }

    .page-hero {
        padding: 4rem 1.5rem;
    }

    .content-container {
        padding: 0 1rem;
    }

    .events-grid {
        grid-template-columns: 1fr;
    }

    .category-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

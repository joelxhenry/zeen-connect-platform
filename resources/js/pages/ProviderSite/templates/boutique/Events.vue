<script setup lang="ts">
import BoutiqueLayout from './components/BoutiqueLayout.vue';
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
    <BoutiqueLayout title="Events">
        <div class="events-page">
            <div class="page-container">
                <!-- Header -->
                <div class="page-header">
                    <span class="header-eyebrow">Experience</span>
                    <h1>Upcoming Events</h1>
                    <p>Join us for curated experiences and intimate gatherings</p>
                </div>

                <!-- Events List -->
                <template v-if="hasEvents">
                    <div
                        v-for="(categoryGroup, index) in eventsByCategory"
                        :key="categoryGroup.category?.id ?? `uncategorized-${index}`"
                        class="category-section"
                    >
                        <div class="category-header">
                            <h2>{{ categoryGroup.category?.name ?? 'Other Events' }}</h2>
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
                                        value="Recurring"
                                        class="event-badge"
                                    />
                                </div>
                                <div class="event-body">
                                    <div class="event-meta">
                                        <span>{{ event.duration_display }}</span>
                                        <span class="meta-dot">·</span>
                                        <span>{{ getLocationLabel(event.location_type, event.location) }}</span>
                                    </div>

                                    <AppLink :href="getEventUrl(event.slug)" class="event-title">
                                        {{ event.name }}
                                    </AppLink>

                                    <p v-if="event.description" class="event-description">
                                        {{ event.description }}
                                    </p>

                                    <div v-if="event.next_occurrence" class="event-schedule">
                                        <i class="pi pi-calendar"></i>
                                        <span>{{ event.next_occurrence.formatted_date }} at {{ event.next_occurrence.formatted_time }}</span>
                                    </div>
                                    <div v-else class="event-schedule no-dates">
                                        <i class="pi pi-calendar"></i>
                                        <span>No upcoming dates</span>
                                    </div>

                                    <div class="event-footer">
                                        <span class="event-price">{{ event.price_display }}</span>
                                        <AppLink v-if="event.next_occurrence && event.next_occurrence.spots_remaining > 0" :href="getBookEventUrl(event.id)">
                                            <Button label="Book Now" class="btn-book" size="small" />
                                        </AppLink>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <i class="pi pi-calendar"></i>
                    <h3>No Upcoming Events</h3>
                    <p>We're planning something special. Check back soon for new events.</p>
                </div>
            </div>
        </div>
    </BoutiqueLayout>
</template>

<style scoped>
.events-page {
    padding: 4rem 0;
}

.page-container {
    max-width: 1000px;
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
    font-size: 1rem;
    font-weight: 300;
}

.category-section {
    margin-bottom: 3rem;
}

.category-header {
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--provider-border);
}

.category-header h2 {
    margin: 0;
    font-size: 1.375rem;
    font-weight: 500;
    color: var(--provider-text);
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.event-card {
    background: var(--provider-surface);
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.event-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.event-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: var(--provider-primary) !important;
    color: white !important;
    font-size: 0.6875rem;
    font-weight: 500;
    border-radius: 2rem;
}

.event-body {
    padding: 1.5rem;
}

.event-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--provider-secondary);
    margin-bottom: 0.5rem;
}

.meta-dot {
    opacity: 0.5;
}

.event-title {
    display: block;
    font-family: var(--font-heading, 'Cormorant Garamond', serif);
    font-size: 1.375rem;
    font-weight: 500;
    color: var(--provider-text);
    text-decoration: none;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.event-title:hover {
    color: var(--provider-primary);
}

.event-description {
    font-size: 0.875rem;
    color: var(--provider-secondary);
    line-height: 1.6;
    margin: 0 0 1rem 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.event-schedule {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--provider-text);
    margin-bottom: 1.25rem;
}

.event-schedule i {
    color: var(--provider-primary);
    font-size: 0.875rem;
}

.event-schedule.no-dates {
    color: var(--provider-secondary);
}

.event-schedule.no-dates i {
    color: var(--provider-secondary);
}

.event-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.25rem;
    border-top: 1px solid var(--provider-border);
}

.event-price {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text);
}

:deep(.btn-book) {
    font-family: var(--font-body, 'Nunito Sans', sans-serif) !important;
    font-weight: 500;
    font-size: 0.8125rem;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 2rem !important;
    padding: 0.5rem 1.25rem;
}

:deep(.btn-book:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--provider-surface);
    border-radius: 1rem;
}

.empty-state i {
    font-size: 2.5rem;
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
    margin: 0;
    color: var(--provider-secondary);
    font-weight: 300;
}

@media (max-width: 768px) {
    .events-page {
        padding: 3rem 0;
    }

    .page-container {
        padding: 0 1.5rem;
    }

    .page-header h1 {
        font-size: 2rem;
    }

    .events-grid {
        grid-template-columns: 1fr;
    }
}
</style>

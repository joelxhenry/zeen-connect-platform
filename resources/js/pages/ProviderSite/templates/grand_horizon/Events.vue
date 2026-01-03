<script setup lang="ts">
import GrandHorizonLayout from './components/GrandHorizonLayout.vue';
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
    <GrandHorizonLayout title="Events">
        <!-- Hero Section -->
        <section class="page-hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h4 class="hero-eyebrow">EXPERIENCE</h4>
                <h1>Upcoming Events</h1>
                <p>Discover our exclusive events and curated experiences</p>
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
                            <h4 class="category-eyebrow">CATEGORY</h4>
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
                                    <div class="image-gradient"></div>
                                    <Tag
                                        v-if="event.event_type === 'recurring'"
                                        value="RECURRING"
                                        class="event-badge"
                                    />
                                </div>
                                <div class="event-body">
                                    <div class="event-meta">
                                        <span>{{ event.duration_display }}</span>
                                        <span class="meta-separator">·</span>
                                        <span>{{ getLocationLabel(event.location_type, event.location) }}</span>
                                    </div>

                                    <AppLink :href="getEventUrl(event.slug)" class="event-title">
                                        {{ event.name }}
                                    </AppLink>

                                    <p v-if="event.description" class="event-description">
                                        {{ event.description }}
                                    </p>

                                    <div v-if="event.next_occurrence" class="event-schedule">
                                        <div class="schedule-icon">
                                            <i class="pi pi-calendar"></i>
                                        </div>
                                        <div class="schedule-info">
                                            <span class="schedule-date">{{ event.next_occurrence.formatted_date }}</span>
                                            <span class="schedule-time">{{ event.next_occurrence.formatted_time }}</span>
                                        </div>
                                        <Tag
                                            v-if="event.next_occurrence.spots_remaining <= 5 && event.next_occurrence.spots_remaining > 0"
                                            :value="`${event.next_occurrence.spots_remaining} spots left`"
                                            severity="warn"
                                            class="spots-tag"
                                        />
                                    </div>
                                    <div v-else class="event-schedule no-dates">
                                        <div class="schedule-icon">
                                            <i class="pi pi-calendar"></i>
                                        </div>
                                        <span>No upcoming dates scheduled</span>
                                    </div>

                                    <div class="event-footer">
                                        <span class="event-price">{{ event.price_display }}</span>
                                        <div class="action-buttons">
                                            <AppLink :href="getEventUrl(event.slug)">
                                                <Button label="Details" severity="secondary" outlined size="small" class="btn-details" />
                                            </AppLink>
                                            <AppLink v-if="event.next_occurrence && event.next_occurrence.spots_remaining > 0" :href="getBookEventUrl(event.id)">
                                                <Button label="Reserve" class="btn-reserve" size="small" />
                                            </AppLink>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <div class="empty-icon">
                        <i class="pi pi-calendar"></i>
                    </div>
                    <h3>No Upcoming Events</h3>
                    <p>We're planning something extraordinary. Stay tuned for announcements.</p>
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
    overflow: hidden;
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

.events-content {
    padding: 5rem 0;
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 3rem;
}

.category-section {
    margin-bottom: 4rem;
}

.category-header {
    margin-bottom: 2rem;
}

.category-eyebrow {
    margin: 0 0 0.5rem 0;
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--provider-primary);
    letter-spacing: 0.15em;
}

.category-header h2 {
    margin: 0;
    font-size: 2rem;
    font-weight: 500;
    color: var(--provider-text);
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 2.5rem;
}

.event-card {
    background: var(--provider-surface);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.event-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
    transform: translateY(-4px);
}

.event-image {
    position: relative;
    height: 240px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.event-card:hover .event-image img {
    transform: scale(1.05);
}

.image-gradient {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.3) 0%, transparent 50%);
}

.event-badge {
    position: absolute;
    top: 1.25rem;
    left: 1.25rem;
    background: var(--provider-primary) !important;
    color: white !important;
    font-size: 0.625rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    border-radius: 0;
}

.event-body {
    padding: 2rem;
}

.event-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--provider-secondary);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.75rem;
}

.meta-separator {
    opacity: 0.5;
}

.event-title {
    display: block;
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.5rem;
    font-weight: 500;
    color: var(--provider-text);
    text-decoration: none;
    margin-bottom: 0.75rem;
    line-height: 1.3;
}

.event-title:hover {
    color: var(--provider-primary);
}

.event-description {
    font-size: 0.9375rem;
    color: var(--provider-secondary);
    line-height: 1.7;
    margin: 0 0 1.25rem 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.event-schedule {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--provider-primary-05, rgba(201, 168, 124, 0.05));
    margin-bottom: 1.5rem;
}

.schedule-icon {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--provider-primary-10, rgba(201, 168, 124, 0.1));
    color: var(--provider-primary);
}

.schedule-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    flex: 1;
}

.schedule-date {
    font-weight: 600;
    color: var(--provider-text);
    font-size: 0.9375rem;
}

.schedule-time {
    font-size: 0.8125rem;
    color: var(--provider-secondary);
}

.spots-tag {
    margin-left: auto;
}

.event-schedule.no-dates {
    color: var(--provider-secondary);
    font-size: 0.875rem;
}

.event-schedule.no-dates .schedule-icon {
    background: var(--provider-border);
    color: var(--provider-secondary);
}

.event-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.5rem;
    border-top: 1px solid var(--provider-border);
}

.event-price {
    font-size: 1.375rem;
    font-weight: 600;
    color: var(--provider-primary);
}

.action-buttons {
    display: flex;
    gap: 0.75rem;
}

:deep(.btn-details) {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    border-radius: 0 !important;
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

.empty-state {
    text-align: center;
    padding: 6rem 2rem;
    background: var(--provider-surface);
}

.empty-icon {
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--provider-primary-05);
}

.empty-icon i {
    font-size: 2rem;
    color: var(--provider-border);
}

.empty-state h3 {
    margin: 0 0 0.75rem 0;
    font-size: 1.75rem;
    font-weight: 500;
    color: var(--provider-text);
}

.empty-state p {
    margin: 0;
    color: var(--provider-secondary);
    font-size: 1rem;
    letter-spacing: 0.02em;
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

    .events-grid {
        grid-template-columns: 1fr;
    }

    .event-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .event-price {
        text-align: center;
    }

    .action-buttons {
        justify-content: center;
    }
}
</style>

<script setup lang="ts">
import { ref, computed } from 'vue';
import MinimalLayout from './components/MinimalLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

interface TeamMember {
    id: number;
    uuid: string;
    name: string;
    avatar?: string;
}

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface GalleryImage {
    url: string;
    thumbnail: string;
}

interface EventOccurrence {
    id: number;
    uuid: string;
    start_datetime: string;
    end_datetime: string;
    formatted_date: string;
    formatted_time: string;
    capacity: number;
    spots_remaining: number;
    is_sold_out: boolean;
    status: string;
}

interface EventData {
    id: number;
    uuid: string;
    slug: string;
    name: string;
    description?: string;
    price: number;
    price_display: string;
    duration_minutes: number;
    duration_display: string;
    capacity: number;
    event_type: 'one_time' | 'recurring';
    location_type: 'virtual' | 'in_person';
    location?: string;
    virtual_meeting_url?: string;
    display_image?: string;
    gallery: GalleryImage[];
    categories: Category[];
    team_members: TeamMember[];
}

interface Props {
    provider: {
        id: number;
        business_name: string;
        slug: string;
        domain: string;
    };
    event: EventData;
    occurrences: EventOccurrence[];
}

const props = defineProps<Props>();

const selectedOccurrence = ref<EventOccurrence | null>(
    props.occurrences.find(o => !o.is_sold_out) || props.occurrences[0] || null
);

const getBookEventUrl = () => {
    if (!selectedOccurrence.value) return '#';
    return `/book?event=${props.event.id}&occurrence=${selectedOccurrence.value.id}`;
};

const getLocationLabel = computed(() => {
    if (props.event.location_type === 'virtual') return 'Virtual';
    return props.event.location || 'In Person';
});

const selectOccurrence = (occurrence: EventOccurrence) => {
    if (!occurrence.is_sold_out) {
        selectedOccurrence.value = occurrence;
    }
};

const hasUpcomingOccurrences = computed(() => props.occurrences.length > 0);
</script>

<template>
    <MinimalLayout :title="event.name">
        <div class="event-detail-page">
            <div class="page-container">
                <!-- Back Link -->
                <AppLink href="/events" class="back-link">
                    <i class="pi pi-arrow-left"></i>
                    Events
                </AppLink>

                <!-- Event Header -->
                <div class="event-header">
                    <h1>{{ event.name }}</h1>
                    <div class="event-meta">
                        <span>{{ event.duration_display }}</span>
                        <span class="meta-dot">·</span>
                        <span>{{ getLocationLabel }}</span>
                        <span v-if="event.event_type === 'recurring'" class="meta-dot">·</span>
                        <span v-if="event.event_type === 'recurring'">Recurring</span>
                    </div>
                </div>

                <!-- Hero Image -->
                <div v-if="event.display_image" class="event-image">
                    <img :src="event.display_image" :alt="event.name" />
                </div>

                <!-- Price & Booking -->
                <div class="booking-section">
                    <div class="price-row">
                        <span class="price">{{ event.price_display }}</span>
                        <span class="per-person">per person</span>
                    </div>

                    <!-- Date Selection -->
                    <div v-if="hasUpcomingOccurrences" class="date-selection">
                        <div
                            v-for="occurrence in occurrences"
                            :key="occurrence.id"
                            class="date-option"
                            :class="{
                                selected: selectedOccurrence?.id === occurrence.id,
                                disabled: occurrence.is_sold_out
                            }"
                            @click="selectOccurrence(occurrence)"
                        >
                            <div class="date-info">
                                <span class="date">{{ occurrence.formatted_date }}</span>
                                <span class="time">{{ occurrence.formatted_time }}</span>
                            </div>
                            <div class="spots-info">
                                <span v-if="occurrence.is_sold_out" class="sold-out">Sold out</span>
                                <span v-else-if="occurrence.spots_remaining <= 5" class="low-spots">
                                    {{ occurrence.spots_remaining }} left
                                </span>
                                <span v-else class="spots">{{ occurrence.spots_remaining }} spots</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="no-dates">
                        No upcoming dates
                    </div>

                    <AppLink v-if="selectedOccurrence && !selectedOccurrence.is_sold_out" :href="getBookEventUrl()">
                        <Button label="Book" class="btn-book" />
                    </AppLink>
                </div>

                <!-- Description -->
                <div v-if="event.description" class="content-section">
                    <h2>About</h2>
                    <div class="description" v-html="event.description"></div>
                </div>

                <!-- Team Members -->
                <div v-if="event.team_members.length > 0" class="content-section">
                    <h2>Hosted by</h2>
                    <div class="hosts">
                        <span v-for="(member, index) in event.team_members" :key="member.id">
                            {{ member.name }}<span v-if="index < event.team_members.length - 1">, </span>
                        </span>
                    </div>
                </div>

                <!-- Location -->
                <div class="content-section">
                    <h2>Location</h2>
                    <p class="location">
                        {{ event.location_type === 'virtual' ? 'Virtual event - link provided after booking' : event.location || 'In-person event' }}
                    </p>
                </div>
            </div>
        </div>
    </MinimalLayout>
</template>

<style scoped>
.event-detail-page {
    padding: 3rem 0;
}

.page-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    margin-bottom: 2rem;
}

.back-link:hover {
    color: var(--provider-text);
}

.event-header {
    margin-bottom: 1.5rem;
}

.event-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--provider-text);
}

.event-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.meta-dot {
    opacity: 0.5;
}

.event-image {
    margin-bottom: 2rem;
    border-radius: 0.5rem;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: auto;
    display: block;
}

.booking-section {
    padding: 1.5rem 0;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.price-row {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}

.price {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--provider-text);
}

.per-person {
    font-size: 0.875rem;
    color: #6b7280;
}

.date-selection {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}

.date-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s;
}

.date-option:hover:not(.disabled) {
    border-color: var(--provider-text);
}

.date-option.selected {
    border-color: var(--provider-text);
    background: #f9fafb;
}

.date-option.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.date-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.date {
    font-weight: 500;
    color: var(--provider-text);
}

.time {
    font-size: 0.875rem;
    color: #6b7280;
}

.spots-info {
    font-size: 0.75rem;
}

.spots {
    color: #6b7280;
}

.low-spots {
    color: #d97706;
}

.sold-out {
    color: #dc2626;
}

.no-dates {
    text-align: center;
    padding: 1rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

:deep(.btn-book) {
    width: 100%;
    background-color: var(--provider-text) !important;
    border-color: var(--provider-text) !important;
    border-radius: 0.5rem;
}

:deep(.btn-book:hover) {
    opacity: 0.9;
}

.content-section {
    margin-bottom: 2rem;
}

.content-section h2 {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.description {
    font-size: 1rem;
    line-height: 1.6;
    color: var(--provider-text);
}

.hosts {
    color: var(--provider-text);
}

.location {
    margin: 0;
    color: var(--provider-text);
}
</style>

<script setup lang="ts">
import { ref, computed } from 'vue';
import ShowcaseLayout from './components/ShowcaseLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';

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
    if (props.event.location_type === 'virtual') return 'VIRTUAL';
    return props.event.location?.toUpperCase() || 'IN PERSON';
});

const selectOccurrence = (occurrence: EventOccurrence) => {
    if (!occurrence.is_sold_out) {
        selectedOccurrence.value = occurrence;
    }
};

const hasUpcomingOccurrences = computed(() => props.occurrences.length > 0);
</script>

<template>
    <ShowcaseLayout :title="event.name">
        <!-- Hero Section -->
        <section class="event-hero">
            <div v-if="event.display_image" class="hero-image">
                <img :src="event.display_image" :alt="event.name" />
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-content">
                <AppLink href="/events" class="back-link">
                    <i class="pi pi-arrow-left"></i>
                    EVENTS
                </AppLink>
                <div class="hero-info">
                    <div class="category-tags">
                        <span
                            v-for="category in event.categories"
                            :key="category.id"
                            class="category-tag"
                        >
                            {{ category.name.toUpperCase() }}
                        </span>
                    </div>
                    <h1>{{ event.name }}</h1>
                    <div class="event-meta mono-text">
                        <span>{{ event.duration_display }}</span>
                        <span class="meta-separator">|</span>
                        <span>{{ getLocationLabel }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Content -->
        <div class="event-content">
            <div class="content-container">
                <div class="content-grid">
                    <!-- Main Column -->
                    <div class="main-column">
                        <!-- Description -->
                        <div v-if="event.description" class="content-section">
                            <h2>ABOUT</h2>
                            <div class="description" v-html="event.description"></div>
                        </div>

                        <!-- Gallery -->
                        <div v-if="event.gallery.length > 0" class="content-section">
                            <h2>GALLERY</h2>
                            <div class="gallery-grid">
                                <div v-for="(image, index) in event.gallery" :key="index" class="gallery-item">
                                    <img :src="image.thumbnail" :alt="`${event.name} - Image ${index + 1}`" />
                                </div>
                            </div>
                        </div>

                        <!-- Team -->
                        <div v-if="event.team_members.length > 0" class="content-section">
                            <h2>HOSTED BY</h2>
                            <div class="team-list">
                                <div v-for="member in event.team_members" :key="member.id" class="team-member">
                                    <Avatar
                                        v-if="member.avatar"
                                        :image="member.avatar"
                                        shape="circle"
                                        class="member-avatar"
                                    />
                                    <Avatar
                                        v-else
                                        :label="member.name.charAt(0)"
                                        shape="circle"
                                        class="member-avatar"
                                    />
                                    <span class="member-name">{{ member.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="sidebar-column">
                        <div class="booking-card">
                            <div class="booking-header">
                                <span class="price mono-text">{{ event.price_display }}</span>
                                <span class="per-person">PER PERSON</span>
                            </div>

                            <!-- Date Selection -->
                            <div v-if="hasUpcomingOccurrences" class="occurrence-selection">
                                <label class="selection-label mono-text">SELECT DATE</label>
                                <div class="occurrence-list">
                                    <button
                                        v-for="occurrence in occurrences"
                                        :key="occurrence.id"
                                        type="button"
                                        class="occurrence-option"
                                        :class="{
                                            selected: selectedOccurrence?.id === occurrence.id,
                                            'sold-out': occurrence.is_sold_out
                                        }"
                                        :disabled="occurrence.is_sold_out"
                                        @click="selectOccurrence(occurrence)"
                                    >
                                        <div class="occurrence-info">
                                            <span class="occurrence-date">{{ occurrence.formatted_date }}</span>
                                            <span class="occurrence-time mono-text">{{ occurrence.formatted_time }}</span>
                                        </div>
                                        <div class="occurrence-status">
                                            <span v-if="occurrence.is_sold_out" class="status-sold-out">SOLD OUT</span>
                                            <span v-else-if="occurrence.spots_remaining <= 5" class="status-low">{{ occurrence.spots_remaining }} LEFT</span>
                                            <span v-else class="status-available mono-text">{{ occurrence.spots_remaining }}</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div v-else class="no-dates">
                                <p>NO UPCOMING DATES</p>
                            </div>

                            <AppLink v-if="selectedOccurrence && !selectedOccurrence.is_sold_out" :href="getBookEventUrl()">
                                <Button label="BOOK" class="btn-book" />
                            </AppLink>
                            <Button v-else label="UNAVAILABLE" class="btn-book" disabled />

                            <div class="location-info">
                                <i :class="event.location_type === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker'"></i>
                                <div class="location-text">
                                    <span class="location-type">{{ event.location_type === 'virtual' ? 'VIRTUAL' : 'IN-PERSON' }}</span>
                                    <span v-if="event.location" class="location-address">{{ event.location }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ShowcaseLayout>
</template>

<style scoped>
.event-hero {
    position: relative;
    min-height: 500px;
    background: var(--provider-text);
    display: flex;
    align-items: flex-end;
}

.hero-image {
    position: absolute;
    inset: 0;
}

.hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.2) 100%);
}

.hero-content {
    position: relative;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 4rem 2rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.6875rem;
    letter-spacing: 0.15em;
    margin-bottom: 2rem;
    transition: color 0.2s;
}

.back-link:hover {
    color: white;
}

.category-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.category-tag {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.625rem;
    letter-spacing: 0.15em;
    color: rgba(255, 255, 255, 0.6);
    padding: 0.25rem 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.hero-content h1 {
    margin: 0 0 1rem 0;
    font-size: 3.5rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.event-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
    letter-spacing: 0.15em;
}

.meta-separator {
    opacity: 0.5;
}

.event-content {
    padding: 4rem 0;
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
    align-items: start;
}

.main-column {
    display: flex;
    flex-direction: column;
    gap: 3rem;
}

.content-section h2 {
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    color: var(--provider-text);
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--provider-text);
}

.description {
    font-size: 1rem;
    line-height: 1.8;
    color: var(--provider-secondary);
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.gallery-item {
    aspect-ratio: 1;
    overflow: hidden;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

.team-list {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.team-member {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    background: #f9fafb;
}

.member-avatar {
    width: 40px !important;
    height: 40px !important;
}

.member-name {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Sidebar */
.sidebar-column {
    position: sticky;
    top: 90px;
}

.booking-card {
    background: white;
    border: 1px solid var(--provider-border);
    padding: 2rem;
}

.booking-header {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid var(--provider-text);
}

.price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-text);
    letter-spacing: 0.05em;
}

.per-person {
    font-size: 0.6875rem;
    color: var(--provider-secondary);
    letter-spacing: 0.1em;
}

.occurrence-selection {
    margin-bottom: 1.5rem;
}

.selection-label {
    display: block;
    font-size: 0.6875rem;
    letter-spacing: 0.15em;
    color: var(--provider-secondary);
    margin-bottom: 0.75rem;
}

.occurrence-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
}

.occurrence-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f9fafb;
    border: 2px solid transparent;
    text-align: left;
    cursor: pointer;
    transition: all 0.15s;
}

.occurrence-option:hover:not(:disabled) {
    border-color: var(--provider-text);
}

.occurrence-option.selected {
    border-color: var(--provider-text);
    background: white;
}

.occurrence-option.sold-out {
    opacity: 0.5;
    cursor: not-allowed;
}

.occurrence-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.occurrence-date {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--provider-text);
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.occurrence-time {
    font-size: 0.6875rem;
    color: var(--provider-secondary);
    letter-spacing: 0.1em;
}

.status-sold-out {
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    color: #dc2626;
}

.status-low {
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    color: #d97706;
}

.status-available {
    font-size: 0.6875rem;
    color: var(--provider-secondary);
}

.no-dates {
    text-align: center;
    padding: 2rem 1rem;
    margin-bottom: 1rem;
}

.no-dates p {
    margin: 0;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    color: var(--provider-secondary);
}

:deep(.btn-book) {
    width: 100%;
    margin-bottom: 1.5rem;
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 0 !important;
}

:deep(.btn-book:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.location-info {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
}

.location-info i {
    font-size: 1.25rem;
    color: var(--provider-text);
}

.location-text {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.location-type {
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    color: var(--provider-text);
}

.location-address {
    font-size: 0.75rem;
    color: var(--provider-secondary);
}

@media (max-width: 900px) {
    .hero-content h1 {
        font-size: 2.5rem;
    }

    .content-grid {
        grid-template-columns: 1fr;
    }

    .sidebar-column {
        position: static;
        order: -1;
    }

    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

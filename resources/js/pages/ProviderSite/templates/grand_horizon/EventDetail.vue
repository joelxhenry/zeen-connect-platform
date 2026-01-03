<script setup lang="ts">
import { ref, computed } from 'vue';
import GrandHorizonLayout from './components/GrandHorizonLayout.vue';
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
    <GrandHorizonLayout :title="event.name">
        <!-- Hero Section -->
        <section class="event-hero" :style="event.display_image ? { backgroundImage: `url(${event.display_image})` } : {}">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <AppLink href="/events" class="back-link">
                    <i class="pi pi-arrow-left"></i>
                    BACK TO EVENTS
                </AppLink>
                <div class="hero-info">
                    <h4 v-if="event.categories.length > 0" class="category-label">{{ event.categories[0].name }}</h4>
                    <h1>{{ event.name }}</h1>
                    <div class="event-meta">
                        <span><i class="pi pi-clock"></i> {{ event.duration_display }}</span>
                        <span><i :class="event.location_type === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker'"></i> {{ getLocationLabel }}</span>
                        <span v-if="event.capacity"><i class="pi pi-users"></i> {{ event.capacity }} spots</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Content -->
        <div class="event-content">
            <div class="content-container">
                <div class="content-layout">
                    <!-- Main Content -->
                    <div class="main-column">
                        <!-- Description -->
                        <div v-if="event.description" class="content-section">
                            <h4 class="section-eyebrow">ABOUT</h4>
                            <h2>Event Details</h2>
                            <div class="description" v-html="event.description"></div>
                        </div>

                        <!-- Gallery -->
                        <div v-if="event.gallery.length > 0" class="content-section">
                            <h4 class="section-eyebrow">GALLERY</h4>
                            <h2>Event Photos</h2>
                            <div class="gallery-grid">
                                <div v-for="(image, index) in event.gallery" :key="index" class="gallery-item">
                                    <img :src="image.thumbnail" :alt="`${event.name} - Image ${index + 1}`" />
                                </div>
                            </div>
                        </div>

                        <!-- Team Members -->
                        <div v-if="event.team_members.length > 0" class="content-section">
                            <h4 class="section-eyebrow">YOUR HOSTS</h4>
                            <h2>Presented By</h2>
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
                                <span class="price">{{ event.price_display }}</span>
                                <span class="per-person">per person</span>
                            </div>

                            <!-- Date Selection -->
                            <div v-if="hasUpcomingOccurrences" class="occurrence-selection">
                                <label class="selection-label">SELECT A DATE</label>
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
                                            <span class="occurrence-time">{{ occurrence.formatted_time }}</span>
                                        </div>
                                        <div class="occurrence-status">
                                            <Tag v-if="occurrence.is_sold_out" value="Sold Out" severity="danger" />
                                            <Tag v-else-if="occurrence.spots_remaining <= 5" :value="`${occurrence.spots_remaining} left`" severity="warn" />
                                            <span v-else class="spots-count">{{ occurrence.spots_remaining }} spots</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div v-else class="no-dates">
                                <div class="no-dates-icon">
                                    <i class="pi pi-calendar-times"></i>
                                </div>
                                <p>No Upcoming Dates</p>
                                <small>Check back later for new dates</small>
                            </div>

                            <AppLink v-if="selectedOccurrence && !selectedOccurrence.is_sold_out" :href="getBookEventUrl()">
                                <Button label="Reserve Your Spot" class="btn-reserve" />
                            </AppLink>
                            <Button v-else label="No Dates Available" class="btn-reserve" disabled />

                            <div class="location-info">
                                <div class="location-icon">
                                    <i :class="event.location_type === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker'"></i>
                                </div>
                                <div class="location-details">
                                    <span class="location-type">{{ event.location_type === 'virtual' ? 'Virtual Event' : 'In-Person Event' }}</span>
                                    <span v-if="event.location" class="location-address">{{ event.location }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GrandHorizonLayout>
</template>

<style scoped>
.event-hero {
    position: relative;
    min-height: 500px;
    background: var(--provider-dark, #1a1a1a);
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: flex-end;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.3) 50%, rgba(0, 0, 0, 0.4) 100%);
}

.hero-content {
    position: relative;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 4rem 3rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    margin-bottom: 2rem;
    transition: color 0.2s;
}

.back-link:hover {
    color: white;
}

.category-label {
    margin: 0 0 0.75rem 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--provider-primary);
    text-transform: uppercase;
    letter-spacing: 0.2em;
}

.hero-content h1 {
    margin: 0 0 1.5rem 0;
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 3.5rem;
    font-weight: 500;
    color: white;
    line-height: 1.1;
}

.event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
    letter-spacing: 0.05em;
}

.event-meta i {
    color: var(--provider-primary);
    margin-right: 0.5rem;
}

.event-content {
    padding: 5rem 0;
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 3rem;
}

.content-layout {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 4rem;
    align-items: start;
}

.main-column {
    display: flex;
    flex-direction: column;
    gap: 3.5rem;
}

.content-section {
    background: var(--provider-surface);
    padding: 2.5rem;
}

.section-eyebrow {
    margin: 0 0 0.5rem 0;
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--provider-primary);
    letter-spacing: 0.2em;
}

.content-section h2 {
    margin: 0 0 1.5rem 0;
    font-family: var(--font-heading, 'Playfair Display', serif);
    font-size: 1.75rem;
    font-weight: 500;
    color: var(--provider-text);
}

.description {
    font-size: 1rem;
    line-height: 1.9;
    color: var(--provider-secondary);
    letter-spacing: 0.02em;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.gallery-item {
    aspect-ratio: 1;
    overflow: hidden;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

.team-list {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
}

.team-member {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: var(--provider-background);
}

.member-avatar {
    width: 48px !important;
    height: 48px !important;
}

.member-name {
    font-weight: 500;
    font-size: 1rem;
    color: var(--provider-text);
    letter-spacing: 0.02em;
}

/* Sidebar */
.sidebar-column {
    position: sticky;
    top: 110px;
}

.booking-card {
    background: var(--provider-surface);
    padding: 2.5rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}

.booking-header {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 2rem;
    padding-bottom: 1.75rem;
    border-bottom: 1px solid var(--provider-border);
}

.price {
    font-size: 2.25rem;
    font-weight: 600;
    color: var(--provider-primary);
}

.per-person {
    font-size: 0.875rem;
    color: var(--provider-secondary);
    letter-spacing: 0.05em;
}

.occurrence-selection {
    margin-bottom: 2rem;
}

.selection-label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    color: var(--provider-text);
    margin-bottom: 1rem;
}

.occurrence-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 320px;
    overflow-y: auto;
}

.occurrence-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem;
    background: var(--provider-background);
    border: 2px solid transparent;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s;
}

.occurrence-option:hover:not(:disabled) {
    border-color: var(--provider-primary);
}

.occurrence-option.selected {
    border-color: var(--provider-primary);
    background: var(--provider-primary-05);
}

.occurrence-option.sold-out {
    opacity: 0.5;
    cursor: not-allowed;
}

.occurrence-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.occurrence-date {
    font-weight: 600;
    font-size: 0.9375rem;
    color: var(--provider-text);
}

.occurrence-time {
    font-size: 0.8125rem;
    color: var(--provider-secondary);
}

.spots-count {
    font-size: 0.75rem;
    color: var(--provider-secondary);
}

.no-dates {
    text-align: center;
    padding: 2.5rem 1rem;
    margin-bottom: 1.5rem;
}

.no-dates-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--provider-background);
}

.no-dates-icon i {
    font-size: 1.5rem;
    color: var(--provider-border);
}

.no-dates p {
    margin: 0 0 0.25rem 0;
    font-weight: 500;
    color: var(--provider-text);
}

.no-dates small {
    color: var(--provider-secondary);
    font-size: 0.8125rem;
}

:deep(.btn-reserve) {
    width: 100%;
    margin-bottom: 1.75rem;
    font-weight: 600;
    font-size: 0.8125rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 0 !important;
    padding: 1rem 1.5rem;
}

:deep(.btn-reserve:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.location-info {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--provider-background);
}

.location-icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--provider-primary-10);
}

.location-icon i {
    font-size: 1.25rem;
    color: var(--provider-primary);
}

.location-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.location-type {
    font-weight: 600;
    font-size: 0.9375rem;
    color: var(--provider-text);
}

.location-address {
    font-size: 0.8125rem;
    color: var(--provider-secondary);
}

@media (max-width: 1024px) {
    .hero-content h1 {
        font-size: 2.5rem;
    }

    .content-container {
        padding: 0 2rem;
    }

    .content-layout {
        grid-template-columns: 1fr;
    }

    .sidebar-column {
        position: static;
        order: -1;
    }
}

@media (max-width: 768px) {
    .hero-content {
        padding: 3rem 1.5rem;
    }

    .hero-content h1 {
        font-size: 2rem;
    }

    .content-container {
        padding: 0 1.5rem;
    }

    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script setup lang="ts">
import { ref, computed } from 'vue';
import ArchitectBoldLayout from './components/ArchitectBoldLayout.vue';
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
    <ArchitectBoldLayout :title="event.name">
        <!-- Hero Section -->
        <section class="event-hero" :style="event.display_image ? { backgroundImage: `url(${event.display_image})` } : {}">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <AppLink href="/events" class="back-link">
                    <i class="pi pi-arrow-left"></i>
                    BACK TO EVENTS
                </AppLink>
                <div class="hero-info">
                    <div class="category-tags">
                        <Tag
                            v-for="category in event.categories"
                            :key="category.id"
                            :value="category.name.toUpperCase()"
                            class="category-tag"
                        />
                        <Tag
                            v-if="event.event_type === 'recurring'"
                            value="RECURRING"
                            class="type-tag"
                        />
                    </div>
                    <h1>{{ event.name }}</h1>
                    <div class="event-meta">
                        <span>{{ event.duration_display }}</span>
                        <span class="meta-separator">|</span>
                        <span>{{ getLocationLabel }}</span>
                        <span v-if="event.capacity" class="meta-separator">|</span>
                        <span v-if="event.capacity">{{ event.capacity }} SPOTS</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <div class="event-content">
            <div class="content-container">
                <div class="content-layout">
                    <!-- Left Column -->
                    <div class="main-column">
                        <!-- Description -->
                        <div v-if="event.description" class="content-block">
                            <h2>ABOUT THIS EVENT</h2>
                            <div class="description" v-html="event.description"></div>
                        </div>

                        <!-- Gallery -->
                        <div v-if="event.gallery.length > 0" class="content-block">
                            <h2>GALLERY</h2>
                            <div class="gallery-grid">
                                <div v-for="(image, index) in event.gallery" :key="index" class="gallery-item">
                                    <img :src="image.thumbnail" :alt="`${event.name} - Image ${index + 1}`" />
                                </div>
                            </div>
                        </div>

                        <!-- Team -->
                        <div v-if="event.team_members.length > 0" class="content-block">
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

                    <!-- Right Column - Booking -->
                    <div class="sidebar-column">
                        <div class="booking-card">
                            <div class="booking-header">
                                <span class="price">{{ event.price_display }}</span>
                                <span class="per-person">per person</span>
                            </div>

                            <!-- Date Selection -->
                            <div v-if="hasUpcomingOccurrences" class="occurrence-selection">
                                <label class="selection-label">SELECT DATE</label>
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
                                            <Tag v-if="occurrence.is_sold_out" value="SOLD OUT" severity="danger" />
                                            <Tag v-else-if="occurrence.spots_remaining <= 5" :value="`${occurrence.spots_remaining} LEFT`" severity="warn" />
                                            <span v-else class="spots-count">{{ occurrence.spots_remaining }} SPOTS</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div v-else class="no-dates">
                                <p>NO UPCOMING DATES</p>
                                <small>Check back later</small>
                            </div>

                            <AppLink v-if="selectedOccurrence && !selectedOccurrence.is_sold_out" :href="getBookEventUrl()">
                                <Button label="BOOK NOW" class="btn-primary book-btn" />
                            </AppLink>
                            <Button v-else label="NO DATES AVAILABLE" class="book-btn" disabled />

                            <div class="location-info">
                                <i :class="event.location_type === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker'"></i>
                                <div>
                                    <span class="location-type">{{ event.location_type === 'virtual' ? 'VIRTUAL EVENT' : 'IN-PERSON' }}</span>
                                    <span v-if="event.location" class="location-address">{{ event.location }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ArchitectBoldLayout>
</template>

<style scoped>
.event-hero {
    position: relative;
    min-height: 400px;
    background: var(--provider-text);
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: flex-end;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.3) 100%);
}

.hero-content {
    position: relative;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 3rem 2rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    margin-bottom: 2rem;
    transition: color 0.2s;
}

.back-link:hover {
    color: white;
}

.category-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.category-tag {
    background: transparent !important;
    border: 1px solid rgba(255, 255, 255, 0.5) !important;
    color: white !important;
    font-size: 0.625rem;
    letter-spacing: 0.1em;
}

.type-tag {
    background: var(--provider-primary) !important;
    color: white !important;
    font-size: 0.625rem;
    letter-spacing: 0.1em;
}

.hero-content h1 {
    margin: 0 0 1rem 0;
    font-size: 3rem;
    font-weight: 700;
    color: white;
    letter-spacing: 0.05em;
}

.event-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    letter-spacing: 0.1em;
}

.meta-separator {
    opacity: 0.5;
}

.event-content {
    padding: 3rem 0;
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.content-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
    align-items: start;
}

.main-column {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.content-block {
    background: white;
    border: 1px solid var(--provider-border);
    padding: 2rem;
}

.content-block h2 {
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: var(--provider-text);
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--provider-text);
}

.description {
    font-size: 1rem;
    line-height: 1.7;
    color: #4b5563;
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
    letter-spacing: 0.05em;
}

/* Sidebar */
.sidebar-column {
    position: sticky;
    top: 100px;
}

.booking-card {
    background: white;
    border: 1px solid var(--provider-border);
    padding: 2rem;
}

.booking-header {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid var(--provider-text);
}

.price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-text);
}

.per-person {
    font-size: 0.75rem;
    color: #6b7280;
    letter-spacing: 0.05em;
}

.occurrence-selection {
    margin-bottom: 1.5rem;
}

.selection-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: var(--provider-text);
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
}

.occurrence-time {
    font-size: 0.75rem;
    color: #6b7280;
}

.spots-count {
    font-size: 0.625rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: #6b7280;
}

.no-dates {
    text-align: center;
    padding: 2rem 1rem;
    margin-bottom: 1rem;
}

.no-dates p {
    margin: 0 0 0.25rem 0;
    font-weight: 600;
    font-size: 0.875rem;
    letter-spacing: 0.1em;
}

.no-dates small {
    color: #6b7280;
    font-size: 0.75rem;
}

.book-btn {
    width: 100%;
    margin-bottom: 1.5rem;
}

:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
    border-radius: 0 !important;
    font-weight: 600;
    letter-spacing: 0.1em;
}

:deep(.btn-primary:hover) {
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

.location-info > div {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.location-type {
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    color: var(--provider-text);
}

.location-address {
    font-size: 0.75rem;
    color: #6b7280;
}

@media (max-width: 900px) {
    .hero-content h1 {
        font-size: 2rem;
    }

    .content-layout {
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

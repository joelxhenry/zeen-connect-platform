<script setup lang="ts">
import { ref, computed } from 'vue';
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
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
    icon?: string;
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

const getLocationIcon = (locationType: 'virtual' | 'in_person') => {
    return locationType === 'virtual' ? 'pi pi-video' : 'pi pi-map-marker';
};

const getLocationLabel = computed(() => {
    if (props.event.location_type === 'virtual') return 'Virtual Event';
    return props.event.location || 'In Person';
});

const selectOccurrence = (occurrence: EventOccurrence) => {
    if (!occurrence.is_sold_out) {
        selectedOccurrence.value = occurrence;
    }
};

const hasUpcomingOccurrences = computed(() => props.occurrences.length > 0);
const availableOccurrences = computed(() => props.occurrences.filter(o => !o.is_sold_out));
</script>

<template>
    <ProviderSiteLayout :title="event.name">
        <div class="event-detail-page">
            <div class="page-container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <AppLink href="/events" class="breadcrumb-link">
                        <i class="pi pi-arrow-left"></i>
                        Back to Events
                    </AppLink>
                </div>

                <div class="event-layout">
                    <!-- Main Content -->
                    <div class="event-main">
                        <!-- Event Header -->
                        <div class="event-header">
                            <div v-if="event.display_image" class="event-hero-image">
                                <img :src="event.display_image" :alt="event.name" />
                            </div>

                            <div class="event-title-section">
                                <div class="category-tags">
                                    <Tag
                                        v-for="category in event.categories"
                                        :key="category.id"
                                        :value="category.name"
                                        severity="secondary"
                                    />
                                    <Tag
                                        v-if="event.event_type === 'recurring'"
                                        value="Recurring"
                                        severity="info"
                                    />
                                </div>
                                <h1>{{ event.name }}</h1>

                                <div class="event-meta-row">
                                    <span class="meta-item">
                                        <i class="pi pi-clock"></i>
                                        {{ event.duration_display }}
                                    </span>
                                    <span class="meta-item">
                                        <i :class="getLocationIcon(event.location_type)"></i>
                                        {{ getLocationLabel }}
                                    </span>
                                    <span v-if="event.capacity" class="meta-item">
                                        <i class="pi pi-users"></i>
                                        {{ event.capacity }} spots per session
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="event.description" class="event-section">
                            <h2>About This Event</h2>
                            <div class="description-content" v-html="event.description"></div>
                        </div>

                        <!-- Gallery -->
                        <div v-if="event.gallery.length > 0" class="event-section">
                            <h2>Gallery</h2>
                            <div class="gallery-grid">
                                <div v-for="(image, index) in event.gallery" :key="index" class="gallery-item">
                                    <img :src="image.thumbnail" :alt="`${event.name} - Image ${index + 1}`" />
                                </div>
                            </div>
                        </div>

                        <!-- Team Members -->
                        <div v-if="event.team_members.length > 0" class="event-section">
                            <h2>Hosted By</h2>
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

                    <!-- Sidebar - Booking Card -->
                    <div class="event-sidebar">
                        <div class="booking-card">
                            <div class="booking-header">
                                <span class="price">{{ event.price_display }}</span>
                                <span class="per-person">per person</span>
                            </div>

                            <!-- Date Selection -->
                            <div v-if="hasUpcomingOccurrences" class="occurrence-selection">
                                <label class="selection-label">Select a Date</label>
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
                                        <div class="occurrence-date">{{ occurrence.formatted_date }}</div>
                                        <div class="occurrence-time">{{ occurrence.formatted_time }}</div>
                                        <div class="occurrence-spots">
                                            <template v-if="occurrence.is_sold_out">
                                                <Tag value="Sold Out" severity="danger" />
                                            </template>
                                            <template v-else-if="occurrence.spots_remaining <= 5">
                                                <Tag :value="`${occurrence.spots_remaining} left`" severity="warn" />
                                            </template>
                                            <template v-else>
                                                <span class="spots-available">{{ occurrence.spots_remaining }} spots</span>
                                            </template>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- No Upcoming Dates -->
                            <div v-else class="no-dates">
                                <i class="pi pi-calendar-times"></i>
                                <p>No upcoming dates scheduled</p>
                                <small>Check back later for new dates</small>
                            </div>

                            <!-- Book Button -->
                            <AppLink v-if="selectedOccurrence && !selectedOccurrence.is_sold_out" :href="getBookEventUrl()">
                                <Button label="Book This Event" class="btn-primary book-btn" />
                            </AppLink>
                            <Button
                                v-else-if="!hasUpcomingOccurrences"
                                label="No Dates Available"
                                class="book-btn"
                                disabled
                            />
                            <Button
                                v-else
                                label="Select an Available Date"
                                class="book-btn"
                                disabled
                            />

                            <!-- Location Info -->
                            <div class="location-info">
                                <i :class="getLocationIcon(event.location_type)"></i>
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
    </ProviderSiteLayout>
</template>

<style scoped>
.event-detail-page {
    padding: 2rem 0 4rem;
}

.page-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.breadcrumb {
    margin-bottom: 1.5rem;
}

.breadcrumb-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.15s;
}

.breadcrumb-link:hover {
    color: var(--provider-primary);
}

.event-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2rem;
    align-items: start;
}

.event-main {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.event-header {
    background: white;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.event-hero-image {
    width: 100%;
    height: 300px;
    overflow: hidden;
}

.event-hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-title-section {
    padding: 1.5rem;
}

.category-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.event-title-section h1 {
    margin: 0 0 1rem 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--provider-text);
}

.event-meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9375rem;
    color: #6b7280;
}

.meta-item i {
    color: var(--provider-primary);
}

.event-section {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.event-section h2 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--provider-text);
}

.description-content {
    font-size: 0.9375rem;
    line-height: 1.6;
    color: #4b5563;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.gallery-item {
    aspect-ratio: 1;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #f3f4f6;
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
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
}

.member-avatar {
    width: 40px !important;
    height: 40px !important;
}

.member-name {
    font-weight: 500;
    color: var(--provider-text);
}

/* Sidebar / Booking Card */
.event-sidebar {
    position: sticky;
    top: 80px;
}

.booking-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.booking-header {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.booking-header .price {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--provider-primary);
}

.booking-header .per-person {
    font-size: 0.875rem;
    color: #6b7280;
}

.occurrence-selection {
    margin-bottom: 1.5rem;
}

.selection-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text);
    margin-bottom: 0.75rem;
}

.occurrence-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 280px;
    overflow-y: auto;
}

.occurrence-option {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.25rem 1rem;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border: 2px solid transparent;
    border-radius: 0.5rem;
    text-align: left;
    cursor: pointer;
    transition: all 0.15s;
}

.occurrence-option:hover:not(:disabled) {
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border-color: var(--provider-primary-10, rgba(16, 107, 79, 0.1));
}

.occurrence-option.selected {
    background: var(--provider-primary-05, rgba(16, 107, 79, 0.05));
    border-color: var(--provider-primary);
}

.occurrence-option.sold-out {
    opacity: 0.6;
    cursor: not-allowed;
}

.occurrence-date {
    font-weight: 500;
    color: var(--provider-text);
    font-size: 0.9375rem;
}

.occurrence-time {
    font-size: 0.8125rem;
    color: #6b7280;
    grid-column: 1;
}

.occurrence-spots {
    grid-row: 1 / 3;
    grid-column: 2;
    display: flex;
    align-items: center;
}

.spots-available {
    font-size: 0.75rem;
    color: #6b7280;
}

.no-dates {
    text-align: center;
    padding: 1.5rem 1rem;
    color: #6b7280;
}

.no-dates i {
    font-size: 2rem;
    color: #d1d5db;
    margin-bottom: 0.5rem;
}

.no-dates p {
    margin: 0 0 0.25rem 0;
    font-weight: 500;
}

.no-dates small {
    font-size: 0.8125rem;
}

.book-btn {
    width: 100%;
    margin-bottom: 1rem;
}

:deep(.btn-primary) {
    background-color: var(--provider-primary) !important;
    border-color: var(--provider-primary) !important;
}

:deep(.btn-primary:hover) {
    background-color: var(--provider-primary-hover) !important;
    border-color: var(--provider-primary-hover) !important;
}

.location-info {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
}

.location-info i {
    font-size: 1.25rem;
    color: var(--provider-primary);
}

.location-details {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.location-type {
    font-weight: 500;
    color: var(--provider-text);
    font-size: 0.9375rem;
}

.location-address {
    font-size: 0.8125rem;
    color: #6b7280;
}

@media (max-width: 900px) {
    .event-layout {
        grid-template-columns: 1fr;
    }

    .event-sidebar {
        position: static;
        order: -1;
    }

    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

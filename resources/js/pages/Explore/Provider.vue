<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import type { PageProps } from '@/types/models';

interface ProviderData {
    id: number;
    uuid: string;
    slug: string;
    business_name: string;
    tagline?: string;
    bio?: string;
    avatar?: string;
    website?: string;
    social_links?: Record<string, string>;
    location?: string;
    locations: Array<{ id: number; name: string; display_name: string }>;
    rating_avg: number;
    rating_count: number;
    total_bookings: number;
    is_featured: boolean;
    is_favorited: boolean;
    verified_at?: string;
}

interface ServiceData {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
}

interface CategoryGroup {
    category: {
        id: number;
        name: string;
        icon?: string;
        slug: string;
    };
    services: ServiceData[];
}

interface AvailabilitySlot {
    day: string;
    day_of_week: number;
    start_time: string;
    end_time: string;
}

interface ReviewData {
    id: number;
    uuid: string;
    client: {
        name: string;
        avatar?: string;
    };
    service_name: string;
    rating: number;
    comment?: string;
    provider_response?: string;
    formatted_date: string;
    time_ago: string;
}

interface ReviewStats {
    total: number;
    average: number;
    average_display: string;
    distribution: Record<number, number>;
}

const props = defineProps<{
    provider: ProviderData;
    servicesByCategory: CategoryGroup[];
    availability: AvailabilitySlot[];
    reviews: ReviewData[];
    reviewStats: ReviewStats;
}>();

const getDistributionPercentage = (count: number): number => {
    if (props.reviewStats.total === 0) return 0;
    return (count / props.reviewStats.total) * 100;
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getSocialIcon = (platform: string): string => {
    const icons: Record<string, string> = {
        instagram: 'pi-instagram',
        facebook: 'pi-facebook',
        twitter: 'pi-twitter',
        tiktok: 'pi-tiktok',
        youtube: 'pi-youtube',
        linkedin: 'pi-linkedin',
    };
    return icons[platform.toLowerCase()] || 'pi-link';
};

const totalServices = props.servicesByCategory.reduce(
    (sum, group) => sum + group.services.length,
    0
);

const page = usePage<PageProps>();
const toast = useToast();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const isFavorited = ref(props.provider.is_favorited);
const togglingFavorite = ref(false);

const toggleFavorite = async () => {
    if (!isAuthenticated.value) {
        window.location.href = '/login';
        return;
    }

    togglingFavorite.value = true;
    try {
        const response = await fetch(`/dashboard/favorites/${props.provider.slug}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        isFavorited.value = data.is_favorited;
        toast.add({
            severity: data.is_favorited ? 'success' : 'info',
            summary: data.is_favorited ? 'Added to Favorites' : 'Removed from Favorites',
            detail: data.message,
            life: 3000,
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update favorites',
            life: 3000,
        });
    } finally {
        togglingFavorite.value = false;
    }
};
</script>

<template>
    <PublicLayout :title="provider.business_name">
        <Toast />
        <div class="provider-page">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="hero-content">
                    <div class="hero-top-row">
                        <Link href="/explore" class="back-link">
                            <i class="pi pi-arrow-left"></i>
                            <span>Back to Explore</span>
                        </Link>
                        <Button
                            :icon="isFavorited ? 'pi pi-heart-fill' : 'pi pi-heart'"
                            :severity="isFavorited ? 'danger' : 'secondary'"
                            :outlined="!isFavorited"
                            rounded
                            :loading="togglingFavorite"
                            @click="toggleFavorite"
                            v-tooltip.left="isFavorited ? 'Remove from favorites' : 'Add to favorites'"
                            class="favorite-btn"
                        />
                    </div>

                    <div class="provider-header">
                        <div class="avatar-section">
                            <div class="avatar-wrapper">
                                <img
                                    v-if="provider.avatar"
                                    :src="provider.avatar"
                                    :alt="provider.business_name"
                                    class="avatar"
                                />
                                <div v-else class="avatar-placeholder">
                                    {{ getInitials(provider.business_name) }}
                                </div>
                                <div v-if="provider.is_featured" class="featured-badge">
                                    <i class="pi pi-star-fill"></i>
                                </div>
                            </div>
                        </div>

                        <div class="header-info">
                            <div class="name-row">
                                <h1 class="business-name">{{ provider.business_name }}</h1>
                                <Tag v-if="provider.verified_at" severity="success" class="verified-tag">
                                    <i class="pi pi-verified"></i>
                                    Verified
                                </Tag>
                            </div>
                            <p v-if="provider.tagline" class="tagline">{{ provider.tagline }}</p>
                            <div class="location" v-if="provider.location">
                                <i class="pi pi-map-marker"></i>
                                <span>{{ provider.location }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="stats-row">
                        <div class="stat">
                            <div class="stat-value">
                                <i class="pi pi-star-fill star-icon"></i>
                                {{ provider.rating_avg > 0 ? provider.rating_avg.toFixed(1) : 'New' }}
                            </div>
                            <div class="stat-label">
                                {{ provider.rating_count > 0 ? `${provider.rating_count} reviews` : 'No reviews yet' }}
                            </div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat">
                            <div class="stat-value">{{ totalServices }}</div>
                            <div class="stat-label">{{ totalServices === 1 ? 'Service' : 'Services' }}</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat">
                            <div class="stat-value">{{ provider.total_bookings }}</div>
                            <div class="stat-label">Bookings</div>
                        </div>
                    </div>

                    <div class="social-links" v-if="provider.social_links && Object.keys(provider.social_links).length > 0">
                        <a
                            v-for="(url, platform) in provider.social_links"
                            :key="platform"
                            :href="url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-link"
                        >
                            <i :class="`pi ${getSocialIcon(platform)}`"></i>
                        </a>
                        <a
                            v-if="provider.website"
                            :href="provider.website"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-link"
                        >
                            <i class="pi pi-globe"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-container">
                <div class="content-grid">
                    <!-- Services Section -->
                    <div class="services-section">
                        <h2 class="section-title">
                            <i class="pi pi-list"></i>
                            Services
                        </h2>

                        <div v-if="servicesByCategory.length > 0" class="services-list">
                            <div
                                v-for="group in servicesByCategory"
                                :key="group.category.id"
                                class="category-group"
                            >
                                <h3 class="category-name">
                                    <i v-if="group.category.icon" :class="`pi ${group.category.icon}`"></i>
                                    {{ group.category.name }}
                                </h3>

                                <div class="services-cards">
                                    <div
                                        v-for="service in group.services"
                                        :key="service.id"
                                        class="service-card"
                                    >
                                        <div class="service-info">
                                            <h4 class="service-name">{{ service.name }}</h4>
                                            <p v-if="service.description" class="service-description">
                                                {{ service.description }}
                                            </p>
                                            <div class="service-meta">
                                                <span class="duration">
                                                    <i class="pi pi-clock"></i>
                                                    {{ service.duration_display }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="service-price">
                                            <span class="price">{{ service.price_display }}</span>
                                            <Button
                                                label="Book"
                                                size="small"
                                                class="book-btn"
                                                disabled
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="empty-services">
                            <i class="pi pi-inbox"></i>
                            <p>No services available at the moment.</p>
                        </div>
                    </div>

                    <!-- Reviews Section -->
                    <div class="reviews-section" v-if="reviewStats.total > 0">
                        <h2 class="section-title">
                            <i class="pi pi-star"></i>
                            Reviews
                            <span class="reviews-count">({{ reviewStats.total }})</span>
                        </h2>

                        <div class="reviews-content">
                            <!-- Rating Summary -->
                            <div class="rating-summary">
                                <div class="rating-overview">
                                    <div class="rating-big">{{ reviewStats.average_display }}</div>
                                    <div class="rating-stars">
                                        <i
                                            v-for="star in 5"
                                            :key="star"
                                            :class="[
                                                'pi',
                                                star <= Math.round(reviewStats.average) ? 'pi-star-fill' : 'pi-star'
                                            ]"
                                        ></i>
                                    </div>
                                    <div class="rating-total">{{ reviewStats.total }} {{ reviewStats.total === 1 ? 'review' : 'reviews' }}</div>
                                </div>

                                <div class="rating-distribution">
                                    <div
                                        v-for="rating in [5, 4, 3, 2, 1]"
                                        :key="rating"
                                        class="distribution-row"
                                    >
                                        <span class="star-label">{{ rating }}</span>
                                        <i class="pi pi-star-fill distribution-star"></i>
                                        <div class="bar-container">
                                            <div
                                                class="bar-fill"
                                                :style="{ width: `${getDistributionPercentage(reviewStats.distribution[rating] || 0)}%` }"
                                            ></div>
                                        </div>
                                        <span class="bar-count">{{ reviewStats.distribution[rating] || 0 }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Reviews List -->
                            <div class="reviews-list">
                                <div
                                    v-for="review in reviews"
                                    :key="review.id"
                                    class="review-card"
                                >
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <div class="reviewer-avatar" v-if="review.client.avatar">
                                                <img :src="review.client.avatar" :alt="review.client.name" />
                                            </div>
                                            <div class="reviewer-avatar placeholder" v-else>
                                                {{ getInitials(review.client.name) }}
                                            </div>
                                            <div class="reviewer-details">
                                                <span class="reviewer-name">{{ review.client.name }}</span>
                                                <span class="review-service">{{ review.service_name }}</span>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <i
                                                v-for="star in 5"
                                                :key="star"
                                                :class="[
                                                    'pi',
                                                    star <= review.rating ? 'pi-star-fill' : 'pi-star'
                                                ]"
                                            ></i>
                                        </div>
                                    </div>

                                    <p v-if="review.comment" class="review-comment">{{ review.comment }}</p>

                                    <div v-if="review.provider_response" class="provider-response">
                                        <div class="response-label">
                                            <i class="pi pi-reply"></i>
                                            Response from {{ provider.business_name }}
                                        </div>
                                        <p class="response-text">{{ review.provider_response }}</p>
                                    </div>

                                    <div class="review-footer">
                                        <span class="review-date">{{ review.time_ago }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <aside class="sidebar">
                        <!-- About Section -->
                        <div class="sidebar-card" v-if="provider.bio">
                            <h3 class="card-title">
                                <i class="pi pi-user"></i>
                                About
                            </h3>
                            <p class="bio-text">{{ provider.bio }}</p>
                        </div>

                        <!-- Availability Section -->
                        <div class="sidebar-card" v-if="availability.length > 0">
                            <h3 class="card-title">
                                <i class="pi pi-calendar"></i>
                                Availability
                            </h3>
                            <div class="availability-list">
                                <div
                                    v-for="slot in availability"
                                    :key="slot.day_of_week"
                                    class="availability-item"
                                >
                                    <span class="day">{{ slot.day }}</span>
                                    <span class="hours">{{ slot.start_time }} - {{ slot.end_time }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Locations Section -->
                        <div class="sidebar-card" v-if="provider.locations.length > 1">
                            <h3 class="card-title">
                                <i class="pi pi-map-marker"></i>
                                Service Areas
                            </h3>
                            <div class="locations-list">
                                <Tag
                                    v-for="loc in provider.locations"
                                    :key="loc.id"
                                    severity="secondary"
                                    class="location-tag"
                                >
                                    {{ loc.name }}
                                </Tag>
                            </div>
                        </div>

                        <!-- CTA Card -->
                        <div class="cta-card">
                            <h3 class="cta-title">Ready to book?</h3>
                            <p class="cta-text">Booking feature coming soon!</p>
                            <Button
                                label="Coming Soon"
                                class="w-full"
                                disabled
                            />
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.provider-page {
    min-height: calc(100vh - 64px);
    background-color: var(--p-surface-50);
}

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, var(--p-primary-50) 0%, white 100%);
    border-bottom: 1px solid var(--p-surface-200);
    padding: 1.5rem 1.5rem 2rem;
}

.hero-content {
    max-width: 1280px;
    margin: 0 auto;
}

.hero-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--p-surface-600);
    font-size: 0.875rem;
    text-decoration: none;
    transition: color 0.2s;
}

.favorite-btn {
    flex-shrink: 0;
}

.back-link:hover {
    color: var(--p-primary-color);
}

.provider-header {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.avatar-wrapper {
    position: relative;
}

.avatar {
    width: 96px;
    height: 96px;
    border-radius: 20px;
    object-fit: cover;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.avatar-placeholder {
    width: 96px;
    height: 96px;
    border-radius: 20px;
    background: linear-gradient(135deg, var(--p-primary-color), var(--p-primary-400));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 2rem;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.featured-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4);
    border: 2px solid white;
}

.header-info {
    flex: 1;
}

.name-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.375rem;
}

.business-name {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.verified-tag {
    font-size: 0.75rem;
}

.verified-tag i {
    margin-right: 0.25rem;
}

.tagline {
    font-size: 1.0625rem;
    color: var(--p-surface-600);
    margin: 0 0 0.5rem 0;
}

.location {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.9375rem;
    color: var(--p-surface-500);
}

.location i {
    color: var(--p-surface-400);
}

.stats-row {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1rem 1.25rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    margin-bottom: 1rem;
}

.stat {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.stat-value {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--p-surface-900);
}

.star-icon {
    color: #fbbf24;
}

.stat-label {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.stat-divider {
    width: 1px;
    height: 32px;
    background-color: var(--p-surface-200);
}

.social-links {
    display: flex;
    gap: 0.5rem;
}

.social-link {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 10px;
    color: var(--p-surface-600);
    text-decoration: none;
    transition: all 0.2s;
}

.social-link:hover {
    border-color: var(--p-primary-color);
    color: var(--p-primary-color);
    background-color: var(--p-primary-50);
}

/* Main Content */
.main-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 2rem;
}

@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .provider-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .name-row {
        flex-direction: column;
    }

    .location {
        justify-content: center;
    }

    .stats-row {
        justify-content: center;
    }

    .social-links {
        justify-content: center;
    }
}

/* Services Section */
.section-title {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 1.25rem 0;
}

.section-title i {
    color: var(--p-surface-500);
}

.category-group {
    margin-bottom: 1.5rem;
}

.category-group:last-child {
    margin-bottom: 0;
}

.category-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--p-surface-700);
    margin: 0 0 0.75rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--p-surface-200);
}

.category-name i {
    color: var(--p-surface-500);
    font-size: 0.875rem;
}

.services-cards {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.service-card {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    transition: border-color 0.2s;
}

.service-card:hover {
    border-color: var(--p-primary-200);
}

.service-info {
    flex: 1;
    min-width: 0;
}

.service-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.25rem 0;
}

.service-description {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
    margin: 0 0 0.5rem 0;
    line-height: 1.5;
}

.service-meta {
    display: flex;
    gap: 0.75rem;
}

.duration {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.duration i {
    font-size: 0.75rem;
}

.service-price {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.price {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--p-surface-900);
}

.book-btn {
    white-space: nowrap;
}

.empty-services {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    text-align: center;
}

.empty-services i {
    font-size: 2rem;
    color: var(--p-surface-300);
    margin-bottom: 0.75rem;
}

.empty-services p {
    color: var(--p-surface-500);
    margin: 0;
}

/* Sidebar */
.sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.sidebar-card {
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    padding: 1.25rem;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.75rem 0;
}

.card-title i {
    color: var(--p-surface-500);
    font-size: 0.9375rem;
}

.bio-text {
    font-size: 0.9375rem;
    color: var(--p-surface-600);
    line-height: 1.6;
    margin: 0;
}

.availability-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.availability-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--p-surface-100);
}

.availability-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.day {
    font-weight: 500;
    color: var(--p-surface-700);
    font-size: 0.875rem;
}

.hours {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.locations-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.location-tag {
    font-size: 0.75rem;
}

.cta-card {
    background: linear-gradient(135deg, var(--p-primary-50), var(--p-primary-100));
    border: 1px solid var(--p-primary-200);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.cta-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.375rem 0;
}

.cta-text {
    font-size: 0.875rem;
    color: var(--p-surface-600);
    margin: 0 0 1rem 0;
}

/* Reviews Section */
.reviews-section {
    margin-top: 2rem;
}

.reviews-count {
    font-weight: 400;
    color: var(--p-surface-500);
    font-size: 1rem;
}

.reviews-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.rating-summary {
    display: flex;
    gap: 2rem;
    padding: 1.25rem 1.5rem;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
}

.rating-overview {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 100px;
}

.rating-big {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    line-height: 1;
}

.rating-stars {
    display: flex;
    gap: 0.125rem;
    margin: 0.5rem 0;
}

.rating-stars i {
    color: #fbbf24;
    font-size: 0.875rem;
}

.rating-stars i.pi-star {
    color: var(--p-surface-300);
}

.rating-total {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.rating-distribution {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.star-label {
    width: 12px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--p-surface-700);
    text-align: right;
}

.distribution-star {
    color: #fbbf24;
    font-size: 0.6875rem;
}

.bar-container {
    flex: 1;
    height: 8px;
    background: var(--p-surface-200);
    border-radius: 4px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: #fbbf24;
    border-radius: 4px;
    transition: width 0.3s;
}

.bar-count {
    width: 20px;
    font-size: 0.75rem;
    color: var(--p-surface-500);
    text-align: right;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.review-card {
    padding: 1.25rem;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.reviewer-info {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.reviewer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    overflow: hidden;
}

.reviewer-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.reviewer-avatar.placeholder {
    background: linear-gradient(135deg, var(--p-primary-100), var(--p-primary-200));
    color: var(--p-primary-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.reviewer-details {
    display: flex;
    flex-direction: column;
}

.reviewer-name {
    font-weight: 600;
    color: var(--p-surface-900);
    font-size: 0.9375rem;
}

.review-service {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.review-rating {
    display: flex;
    gap: 0.125rem;
}

.review-rating i {
    color: #fbbf24;
    font-size: 0.75rem;
}

.review-rating i.pi-star {
    color: var(--p-surface-300);
}

.review-comment {
    color: var(--p-surface-600);
    font-size: 0.9375rem;
    line-height: 1.6;
    margin: 0 0 0.75rem 0;
}

.provider-response {
    background: var(--p-surface-50);
    border-left: 3px solid var(--p-primary-color);
    padding: 0.75rem 1rem;
    border-radius: 0 8px 8px 0;
    margin-bottom: 0.75rem;
}

.response-label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--p-surface-600);
    margin-bottom: 0.375rem;
}

.response-label i {
    font-size: 0.6875rem;
    color: var(--p-primary-color);
}

.response-text {
    color: var(--p-surface-600);
    font-size: 0.875rem;
    line-height: 1.5;
    margin: 0;
}

.review-footer {
    display: flex;
    justify-content: flex-end;
}

.review-date {
    font-size: 0.75rem;
    color: var(--p-surface-400);
}

@media (max-width: 640px) {
    .rating-summary {
        flex-direction: column;
        gap: 1rem;
    }

    .rating-overview {
        flex-direction: row;
        gap: 1rem;
    }

    .review-header {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

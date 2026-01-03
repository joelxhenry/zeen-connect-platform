/**
 * Provider Site Types
 *
 * Shared type definitions for provider site pages and composables.
 */

// ============================================================================
// Common Types
// ============================================================================

export interface GalleryImage {
    id: number;
    uuid: string;
    url: string;
    thumbnail: string;
    medium: string;
    large: string;
    filename: string;
}

export interface Video {
    id: number;
    uuid: string;
    platform: 'youtube' | 'vimeo';
    video_id: string;
    url: string;
    embed_url: string;
    watch_url: string;
    title?: string;
    thumbnail_url?: string;
    human_duration?: string;
}

export interface Category {
    id: number;
    name: string;
    icon?: string;
    slug: string;
}

// ============================================================================
// Service Types
// ============================================================================

export interface Service {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    display_image?: string;
}

export interface ServiceCategory {
    category: Category;
    services: Service[];
}

export interface ServiceForBooking extends Service {
    category?: Category;
    fees?: {
        tier: string;
        tier_label: string;
        service_price: number;
        convenience_fee: number;
        client_pays: number;
        provider_receives: number;
        requires_deposit: boolean;
        deposit_amount: number;
        deposit_percentage: number;
        fee_payer: string;
    };
}

// ============================================================================
// Review Types
// ============================================================================

export interface Review {
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

export interface ReviewStats {
    total: number;
    average: number;
    average_display: string;
    distribution: Record<number, number>;
}

export interface PaginatedReviews {
    data: Review[];
    current_page: number;
    last_page: number;
    total: number;
}

// ============================================================================
// Availability Types
// ============================================================================

export interface Availability {
    day: string;
    day_of_week: number;
    start_time: string;
    end_time: string;
}

export interface Slot {
    start_time: string;
    end_time?: string;
    display?: string;
}

// ============================================================================
// Team Member Types
// ============================================================================

export interface TeamMemberForBooking {
    id: number;
    uuid: string;
    name: string;
    avatar?: string;
}

export interface TeamMemberForHome {
    id: number;
    uuid?: string;
    name: string;
    role?: string;
    avatar?: string;
    social_links?: Record<string, string>;
}

// ============================================================================
// Site Feature Types
// ============================================================================

export interface SiteFeature {
    icon: string;
    title: string;
    description: string;
}

// ============================================================================
// Provider Types
// ============================================================================

export interface ProviderBase {
    id: number;
    uuid: string;
    slug: string;
    business_name: string;
}

export interface ProviderForHome extends ProviderBase {
    tagline?: string;
    bio?: string;
    avatar?: string;
    domain?: string;
    cover_image?: string;
    website?: string;
    social_links?: Record<string, string>;
    address?: string;
    rating_avg: number;
    rating_count: number;
    rating_display: string;
    total_bookings: number;
    is_featured: boolean;
    verified_at?: string;
    services_count: number;
    gallery?: GalleryImage[];
    videos?: Video[];
}

export interface ProviderForBooking extends ProviderBase {
    avatar?: string;
    tier: string;
    tier_label: string;
}

// ============================================================================
// Page Props Types
// ============================================================================

export interface HomePageProps {
    provider: ProviderForHome;
    servicesByCategory: ServiceCategory[];
    availability: Availability[];
    reviews: Review[];
    reviewStats: ReviewStats;
    teamMembers?: TeamMemberForHome[];
    features?: SiteFeature[];
}

export interface ServicesPageProps {
    provider: ProviderForHome;
    servicesByCategory: ServiceCategory[];
}

export interface ReviewsPageProps {
    provider: ProviderForHome;
    reviews: PaginatedReviews;
    reviewStats: ReviewStats;
}

export interface EventOccurrenceForBooking {
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

export interface EventForBooking {
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
    display_image?: string;
}

export interface BookingPageProps {
    bookingType: 'service' | 'event';
    provider: ProviderForBooking;
    // Service booking props
    services?: ServiceForBooking[];
    availableDates?: string[];
    preselectedService?: number | null;
    teamMembers?: TeamMemberForBooking[];
    // Event booking props
    event?: EventForBooking;
    occurrences?: EventOccurrenceForBooking[];
    preselectedOccurrence?: number | null;
    // Common props
    isAuthenticated: boolean;
    user: {
        name: string;
        email: string;
        phone?: string;
    } | null;
}

export interface ConfirmationPageProps {
    booking: {
        uuid: string;
        booking_date: string;
        start_time: string;
        end_time: string;
        status: string;
        status_label: string;
        status_color: string;
        total: number;
        total_display: string;
        notes?: string;
        client?: {
            name: string;
            email: string;
            phone?: string;
            avatar?: string;
        };
        guest_name?: string;
        guest_email?: string;
        guest_phone?: string;
        service: {
            id: number;
            uuid: string;
            name: string;
            description?: string;
            duration_minutes: number;
            price: number;
        };
        provider: {
            id: number;
            business_name: string;
            slug: string;
            address?: string;
            user?: {
                name: string;
                avatar?: string;
                email: string;
            };
        };
        requires_deposit: boolean;
        deposit_paid: boolean;
        deposit_amount?: number;
        convenience_fee?: number;
        balance_due?: number;
        payment?: {
            uuid: string;
            status: string;
            amount: number;
        };
    };
}

// ============================================================================
// Composable Return Types
// ============================================================================

export interface UseProviderSiteHomeReturn {
    stats: {
        bookings: number;
        rating: number;
        reviewCount: number;
        servicesCount: number;
    };
    bookingUrl: string;
    servicesUrl: string;
    reviewsUrl: string;
    getServiceBookingUrl: (serviceId: number) => string;
    hasPortfolio: boolean;
    hasServices: boolean;
    hasAvailability: boolean;
    hasReviews: boolean;
}

export interface UseProviderSiteReviewsReturn {
    getDistributionPercentage: (count: number) => number;
    hasMorePages: boolean;
    loadMore: () => void;
}

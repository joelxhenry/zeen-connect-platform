import type { BookingFees } from './booking';
import type { MediaItem, VideoEmbed } from './media';

/**
 * Category info as nested in a Service.
 */
export interface ServiceCategory {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon: string | null;
}

/**
 * Provider info as nested in a Service.
 */
export interface ServiceProvider {
    id: number;
    uuid: string;
    business_name: string;
    slug: string;
    avatar: string | null;
}

/**
 * Booking settings for a Service.
 */
export interface ServiceBookingSettings {
    use_provider_defaults: boolean;
    requires_approval: boolean;
    deposit_type: 'none' | 'fixed' | 'percentage';
    deposit_amount: number | null;
    cancellation_policy: 'flexible' | 'moderate' | 'strict';
    advance_booking_days: number;
    min_booking_notice_hours: number;
}

/**
 * Core Service interface.
 * Contains all essential service fields.
 */
export interface Service {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    is_active: boolean;
    sort_order: number;

    // Optional nested relationships (based on context)
    category?: ServiceCategory | null;
    provider?: ServiceProvider | null;

    // Media (when withMedia is called)
    cover?: MediaItem | null;
    cover_url?: string | null;
    cover_thumbnail?: string | null;
    gallery?: MediaItem[];
    videos?: VideoEmbed[];

    // Fees (when withFees is called)
    fees?: BookingFees | null;

    // Booking settings (when withBookingSettings is called)
    booking_settings?: ServiceBookingSettings;

    // Counts (when withCounts is called)
    total_bookings?: number;
}

/**
 * Full Category interface.
 * Used for category management views.
 */
export interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon: string | null;
    description: string | null;
    is_active: boolean;
    sort_order: number;

    // Count (when withCounts is called)
    services_count?: number;
}

/**
 * Service for booking flow.
 * Includes pre-calculated fees.
 */
export interface ServiceForBooking extends Pick<Service,
    'id' | 'name' | 'description' | 'duration_minutes' | 'duration_display' | 'price' | 'price_display'
> {
    category: ServiceCategory | null;
    fees: BookingFees;
}

/**
 * Service for list views.
 * Minimal data for efficient rendering.
 */
export interface ServiceListItem extends Pick<Service,
    'id' | 'uuid' | 'name' | 'description' | 'duration_minutes' | 'duration_display' | 'price' | 'price_display' | 'is_active' | 'sort_order'
> {
    category?: ServiceCategory | null;
    cover_url?: string | null;
    total_bookings?: number;
}

/**
 * Service for edit forms.
 * Full service data with media.
 */
export interface ServiceEditForm extends Service {
    category_id: number | null;
    provider_id: number;
    cover: MediaItem | null;
    gallery: MediaItem[];
    videos: VideoEmbed[];
    booking_settings: ServiceBookingSettings;
}

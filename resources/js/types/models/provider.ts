import type { MediaItem, VideoEmbed } from './media';

// Provider status types
export type ProviderStatus = 'pending' | 'active' | 'suspended' | 'inactive';
export type DepositType = 'none' | 'fixed' | 'percentage';
export type CancellationPolicy = 'flexible' | 'moderate' | 'strict';
export type FeePayer = 'client' | 'provider' | 'split';

// Simple provider for list views
export interface ProviderSimple {
    id: number;
    uuid: string;
    slug: string;
    business_name: string;
    tagline: string | null;
    status: ProviderStatus;
    is_featured: boolean;
    rating_avg: number;
    rating_count: number;
    rating_display: string;
    total_bookings: number;
    avatar: string | null;
    user?: ProviderUser;
    services_count?: number;
    reviews_count?: number;
    commission_rate?: number;
    verified_at: string | null;
    created_at: string;
}

// Full provider with optional nested data
export interface Provider extends ProviderSimple {
    domain: string;
    bio: string | null;
    address: string | null;
    website: string | null;
    social_links: Record<string, string> | null;
    // Optional nested data (based on method chaining)
    avatar_media?: MediaItem | null;
    cover?: string | null;
    cover_media?: MediaItem | null;
    gallery?: MediaItem[];
    videos?: VideoEmbed[];
    services?: ProviderService[];
    availability?: ProviderAvailabilitySlot[];
    // Admin details
    requires_approval?: boolean;
    deposit_type?: DepositType;
    deposit_amount?: number | null;
    deposit_percentage?: number | null;
    cancellation_policy?: CancellationPolicy;
    advance_booking_days?: number;
    min_booking_notice_hours?: number;
    fee_payer?: FeePayer;
    verified_at_formatted?: string;
    created_at_formatted?: string;
}

// Nested user in provider context
export interface ProviderUser {
    id?: number;
    name: string;
    email: string;
    phone?: string | null;
    avatar: string | null;
    joined?: string;
}

// Service within provider context
export interface ProviderService {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    category: string | null;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    is_active: boolean;
}

// Availability slot
export interface ProviderAvailabilitySlot {
    day_of_week: number;
    day: string;
    start_time: string;
    end_time: string;
    is_available: boolean;
}

// Booking settings
export interface BookingSettings {
    requires_approval: boolean;
    deposit_type: DepositType;
    deposit_amount: number | null;
    cancellation_policy: CancellationPolicy;
    advance_booking_days: number;
    min_booking_notice_hours: number;
}

// Team-related types
export type TeamMemberStatus = 'pending' | 'active' | 'suspended';

export interface TeamMember {
    id: number;
    uuid: string;
    provider_id: number;
    user_id: number | null;
    email: string;
    name: string | null;
    avatar: string | null;
    permissions: string[];
    permissions_summary?: string;
    status: TeamMemberStatus;
    status_label: string;
    status_color: string;
    invited_at: string | null;
    accepted_at: string | null;
    is_expired: boolean;
    is_pending: boolean;
}

export interface TeamPermission {
    key: string;
    label: string;
    description: string;
    group: string;
}

export interface TeamPreset {
    label: string;
    description: string;
    permissions: string[];
}

export interface TeamInfo {
    tier: string;
    tier_label: string;
    supports_team: boolean;
    free_slots: number;
    active_count: number;
    extra_count: number;
    fee_per_extra: number;
    total_extra_fee: number;
    would_exceed_free_slots: boolean;
}

// Review stats for provider
export interface ProviderReviewStats {
    total: number;
    average: number;
    average_display: string;
    distribution: Record<number, number>;
}

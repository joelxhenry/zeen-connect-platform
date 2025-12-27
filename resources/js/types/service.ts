import type { Category, MediaItem, Service as BaseService } from './models';

// Re-export base Service type
export type { Category };

// Extended Service type with booking settings and stats
export interface Service extends BaseService {
    use_provider_defaults: boolean;
    requires_approval?: boolean;
    deposit_type?: 'none' | 'fixed' | 'percentage';
    deposit_amount?: number | null;
    cancellation_policy?: 'flexible' | 'moderate' | 'strict';
    advance_booking_days?: number;
    min_booking_notice_hours?: number;
    cover?: MediaItem | null;
    total_bookings?: number;
    price_display?: string;
    duration_display?: string;
}

// Booking settings from provider defaults
export interface BookingSettings {
    requires_approval: boolean;
    deposit_type: 'none' | 'fixed' | 'percentage';
    deposit_amount: number | null;
    cancellation_policy: 'flexible' | 'moderate' | 'strict';
    advance_booking_days: number;
    min_booking_notice_hours: number;
}

// Tier-based restrictions
export interface TierRestrictions {
    tier: string;
    tier_label: string;
    platform_fee_rate: number;
    minimum_service_price: number;
    minimum_service_price_display: string;
    minimum_deposit_percentage: number;
    can_disable_deposit: boolean;
    can_customize_deposit: boolean;
    deposit_help_text: string;
    price_help_text: string;
}

// Service statistics
export interface ServiceStats {
    total: number;
    active: number;
    inactive: number;
}

// Deposit type option
export interface DepositTypeOption {
    value: 'none' | 'fixed' | 'percentage';
    label: string;
    disabled?: boolean;
}

// Duration option
export interface DurationOption {
    value: number;
    label: string;
}

// Cancellation policy option
export interface CancellationPolicyOption {
    value: 'flexible' | 'moderate' | 'strict';
    label: string;
    description: string;
}

// Page Props for Index
export interface ServicesIndexProps {
    services: Service[];
    stats: ServiceStats;
    categories: Category[];
    providerDefaults: BookingSettings;
    tierRestrictions: TierRestrictions;
}

// Page Props for Create
export interface ServicesCreateProps {
    categories: Category[];
    providerDefaults: BookingSettings;
    tierRestrictions: TierRestrictions;
}

// Page Props for Edit
export interface ServicesEditProps {
    service: Service;
    categories: Category[];
    providerDefaults: BookingSettings;
    tierRestrictions: TierRestrictions;
}

// Form validation result
export interface ValidationResult {
    valid: boolean;
    message: string | null;
}

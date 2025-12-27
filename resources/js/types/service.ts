import type {
    Service as BaseService,
    ServiceEditForm,
    Category,
    ServiceBookingSettings,
} from './models/service';

// Re-export types for backward compatibility
export type { Category };

// Extended Service type with booking settings and stats (alias for ServiceEditForm)
export type Service = ServiceEditForm;

// Alias for ServiceBookingSettings
export type BookingSettings = ServiceBookingSettings;

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

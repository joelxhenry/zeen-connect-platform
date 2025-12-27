/**
 * Subscription tier values.
 */
export type SubscriptionTier = 'starter' | 'premium' | 'enterprise';

/**
 * Subscription status values.
 */
export type SubscriptionStatus = 'active' | 'cancelled' | 'expired' | 'past_due';

/**
 * Feature with availability info.
 */
export interface SubscriptionFeature {
    value: string;
    label: string;
    description: string;
    icon: string;
    available: boolean;
    minimum_tier: string;
}

/**
 * Feature as nested in TierInfo.
 */
export interface TierFeature {
    value: string;
    label: string;
    available: boolean;
}

/**
 * Tier configuration/pricing info.
 */
export interface TierInfo {
    value: SubscriptionTier;
    label: string;
    color: string;
    monthly_price: number;
    monthly_price_display: string;
    deposit_percentage: number;
    zeen_fee_rate: number;
    gateway_fee_rate: number;
    total_fee_rate: number;
    platform_fee_rate: number; // Legacy alias
    team_slots: number | 'unlimited';
    team_description: string;
    features: TierFeature[];
}

/**
 * Current tier summary (less detail than TierInfo).
 */
export interface CurrentTier {
    value: SubscriptionTier;
    label: string;
    color: string;
    deposit_percentage: number;
    zeen_fee_rate: number;
    gateway_fee_rate: number;
    total_fee_rate: number;
    platform_fee_rate: number; // Legacy alias
    team_description: string;
}

/**
 * Subscription record.
 */
export interface Subscription {
    started_at: string | null;
    expires_at: string | null;
    status: SubscriptionStatus;
    status_label: string;
}

/**
 * Fee calculation breakdown.
 */
export interface FeeCalculation {
    base_amount: number;
    zeen_fee: number;
    gateway_fee: number;
    convenience_fee: number;
    total_fees: number;
    client_pays: number;
    provider_receives: number;
    fee_payer: 'provider' | 'client';
}

/**
 * Tier restrictions passed to service/booking forms.
 */
export interface TierRestrictions {
    tier: SubscriptionTier;
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

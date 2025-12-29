// Payment Status Types
export type PaymentStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'refunded' | 'partially_refunded';
export type PayoutStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled';
export type LedgerEntryType = 'credit' | 'debit' | 'hold' | 'release';
export type GatewayProvider = 'wipay';
export type GatewayMode = 'split' | 'escrow';
export type PayoutSchedule = 'daily' | 'weekly' | 'monthly';
export type VerificationStatus = 'pending' | 'verified' | 'failed';
export type PayoutMethod = 'wipay_account' | 'wipay_bank' | 'manual_bank_transfer';
export type BankAccountType = 'savings' | 'checking';

// Payment Models
export interface Payment {
    id: number;
    uuid: string;
    booking_uuid: string;
    client_name: string;
    service_name: string;
    booking_date: string;
    amount: number;
    amount_display: string;
    platform_fee: number;
    platform_fee_display: string;
    provider_amount: number;
    provider_amount_display: string;
    processing_fee: number;
    currency: string;
    payment_type: 'deposit' | 'full' | 'balance';
    status: PaymentStatus;
    status_label: string;
    status_color: string;
    status_icon: string;
    is_completed: boolean;
    is_pending: boolean;
    is_failed: boolean;
    can_refund: boolean;
    is_refunded: boolean;
    failure_reason?: string | null;
    refund_reason?: string | null;
    refund_transaction_id?: string | null;
    gateway?: string;
    gateway_type?: string;
    gateway_provider?: GatewayProvider;
    gateway_mode?: GatewayMode;
    gateway_transaction_id?: string;
    gateway_order_id?: string;
    gateway_response_code?: string;
    processing_fee_payer?: 'provider' | 'client';
    card_display?: string;
    paid_at?: string;
    refunded_at?: string;
    created_at: string;
    client?: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
    };
    booking?: {
        id: number;
        uuid: string;
        booking_date: string;
        formatted_date: string;
        start_time: string;
        end_time: string;
        status: string;
        service_name: string;
        service_duration: number;
        guest_name: string | null;
        guest_email: string | null;
    };
}

export interface Payout {
    id: number;
    uuid: string;
    amount: number;
    amount_display: string;
    period_display?: string;
    period_start?: string;
    period_end?: string;
    status: PayoutStatus;
    status_label: string;
    status_color: string;
    payout_method?: PayoutMethod;
    bank_account_display?: string;
    reference_number?: string;
    notes?: string;
    processed_by?: string;
    processed_at?: string;
    created_at: string;
    payments_count?: number;
}

export interface ScheduledPayout {
    id: number;
    uuid: string;
    amount: number;
    amount_display: string;
    status: PayoutStatus;
    status_label: string;
    status_color: string;
    scheduled_for: string;
    can_cancel: boolean;
    payout_method?: PayoutMethod;
}

// Gateway Types
export interface GatewayConfig {
    id: number;
    slug: GatewayProvider;
    name: string;
    icon: string;
    is_verified: boolean;
    is_pending: boolean;
    is_failed: boolean;
    is_primary: boolean;
    is_active: boolean;
    verification_status: VerificationStatus;
    verification_status_label: string;
    supports_split: boolean;
    supports_escrow: boolean;
    merchant_account_id?: string;
    created_at: string;
    verified_at?: string;
}

export interface GatewayOption {
    slug: GatewayProvider;
    name: string;
    icon: string;
    description: string;
    supports_split: boolean;
    supports_escrow: boolean;
    features: GatewayFeature[];
}

export interface GatewayFeature {
    icon: string;
    label: string;
    description: string;
}

// Banking Info Types (for escrow payouts)
export interface BankingInfo {
    bank_name: string | null;
    bank_account_number: string | null;
    bank_account_holder_name: string | null;
    bank_branch_code: string | null;
    bank_account_type: BankAccountType | null;
    is_verified: boolean;
    verified_at?: string;
    has_banking_info: boolean;
}

export interface BankingInfoForm {
    bank_name: string;
    bank_account_number: string;
    bank_account_holder_name: string;
    bank_branch_code: string;
    bank_account_type: BankAccountType;
}

// Wallet & Ledger Types
export interface BalanceSummary {
    total: number;
    total_display: string;
    available: number;
    available_display: string;
    held: number;
    held_display: string;
    pending_payout?: number;
    pending_payout_display?: string;
}

export interface LedgerEntry {
    id: number;
    uuid: string;
    type: LedgerEntryType;
    type_label: string;
    type_icon: string;
    type_color: string;
    amount: number;
    amount_display: string;
    balance_after: number;
    balance_after_display: string;
    description: string;
    booking_uuid?: string;
    payment_uuid?: string;
    created_at: string;
}

export interface PayoutSettings {
    schedule: PayoutSchedule;
    schedule_label: string;
    minimum_amount: number;
    minimum_amount_display: string;
    next_payout_date?: string;
}

// Refund Types
export interface Refund {
    id: number;
    uuid: string;
    booking_uuid: string;
    client_name: string;
    service_name: string;
    booking_date: string;
    original_amount: number;
    original_amount_display: string;
    provider_amount: number;
    provider_amount_display: string;
    status: PaymentStatus;
    status_label: string;
    status_color: string;
    refund_reason?: string;
    refund_transaction_id?: string;
    is_partial: boolean;
    paid_at?: string;
    refunded_at?: string;
}

export interface RefundStats {
    total_count: number;
    total_amount: number;
    total_amount_display: string;
    refund_rate: number;
    refund_rate_display: string;
}

// Earnings Summary Types
export interface EarningsSummary {
    total_earnings: number;
    total_earnings_display: string;
    pending_payout: number;
    pending_payout_display: string;
    total_paid_out: number;
    total_paid_out_display: string;
    this_month?: number;
    this_month_display?: string;
    available_balance?: number;
    available_balance_display?: string;
}

export interface MonthlyEarnings {
    month: string;
    month_label: string;
    total: number;
    total_display: string;
    transaction_count: number;
}

// Form Types for Gateway Setup
export interface WiPayCredentials {
    account_number: string;
    api_key: string;
    environment: 'sandbox' | 'production';
}

// Page Props Types
export interface PaymentsIndexProps {
    summary: EarningsSummary;
    recent_payments: Payment[];
    payouts: Payout[];
    monthly_earnings: MonthlyEarnings[];
    has_gateway_configured: boolean;
    gateway_mode: GatewayMode | null;
    gateway_name?: string;
}

export interface PaymentHistoryProps {
    payments: PaginatedData<Payment>;
    current_status: string;
    counts: {
        all: number;
        completed: number;
        pending: number;
        failed: number;
    };
    status_options: Array<{ value: string; label: string }>;
}

export interface WalletProps {
    balance: BalanceSummary;
    payout_settings: PayoutSettings;
    ledger_entries: PaginatedData<LedgerEntry>;
    scheduled_payouts: ScheduledPayout[];
    has_gateway: boolean;
    filters: {
        type: string;
        date_from?: string;
        date_to?: string;
    };
    type_options: Array<{ value: string; label: string }>;
}

export interface RefundsProps {
    refunds: PaginatedData<Refund>;
    stats: RefundStats;
    filters: {
        status: string;
        date_from?: string;
        date_to?: string;
    };
    statusOptions: Array<{ value: string; label: string }>;
}

export interface GatewaySetupIndexProps {
    hasGatewayConfigured: boolean;
    configuredGateways: GatewayConfig[];
    availableGateways: GatewayOption[];
    bankingInfo: BankingInfo;
}

export interface GatewaySetupFormProps {
    gateway: GatewayOption;
    config: GatewayConfig | null;
    isEdit: boolean;
}

export interface BankingInfoProps {
    bankingInfo: BankingInfo;
    hasWiPayAccount: boolean;
    banks: Record<string, string>;
}

export interface PaymentShowProps {
    payment: Payment;
}

// Utility Types
export interface PaginatedData<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}

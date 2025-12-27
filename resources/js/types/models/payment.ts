/**
 * Payment Status
 */
export type PaymentStatus =
    | 'pending'
    | 'processing'
    | 'completed'
    | 'failed'
    | 'refunded'
    | 'partially_refunded';

/**
 * Client info as nested in a Payment.
 */
export interface PaymentClient {
    id: number;
    name: string;
    email: string;
    phone: string | null;
}

/**
 * Provider info as nested in a Payment.
 */
export interface PaymentProvider {
    id: number;
    uuid: string;
    business_name: string;
    slug: string;
}

/**
 * Booking info as nested in a Payment.
 */
export interface PaymentBooking {
    id: number;
    uuid: string;
    booking_date: string;
    formatted_date: string;
    start_time: string | null;
    status: string;
    service_name: string | null;
}

/**
 * Comprehensive Payment interface.
 * Supports all contexts: Provider, Client, Admin views.
 */
export interface Payment {
    id: number;
    uuid: string;

    // Nested relationships (optional based on context)
    client?: PaymentClient | null;
    provider?: PaymentProvider | null;
    booking?: PaymentBooking | null;

    // Amounts with display values
    amount: number;
    amount_display: string;
    platform_fee: number;
    platform_fee_display: string;
    provider_amount: number;
    provider_amount_display: string;
    processing_fee: number;
    currency: string;

    // Payment type and method
    payment_type: 'deposit' | 'full';
    gateway: string | null;
    card_display: string | null;

    // Status with pre-computed display values
    status: PaymentStatus;
    status_label: string;
    status_color: string;
    status_icon: string;

    // State booleans
    is_completed: boolean;
    is_pending: boolean;
    is_failed: boolean;
    can_refund: boolean;
    is_refunded: boolean;

    // Failure info
    failure_reason: string | null;

    // Gateway details (admin only)
    gateway_transaction_id?: string | null;
    gateway_order_id?: string | null;
    gateway_response_code?: string | null;
    processing_fee_payer?: string | null;

    // Timestamps
    paid_at: string | null;
    refunded_at: string | null;
    created_at: string;
}

/**
 * Payment counts for status tabs.
 */
export interface PaymentCounts {
    all: number;
    completed: number;
    pending: number;
    failed: number;
}

/**
 * Payment status option for filters.
 */
export interface PaymentStatusOption {
    value: string;
    label: string;
}

/**
 * Paginated payments response.
 */
export interface PaginatedPayments {
    data: Payment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

/**
 * Payout Status
 */
export type PayoutStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled';

/**
 * Payout info (for provider dashboard).
 */
export interface Payout {
    id: number;
    uuid: string;
    amount: number;
    amount_display: string;
    period_start?: string;
    period_end?: string;
    period_display: string;
    payout_method?: string;
    bank_account_display: string | null;
    reference_number: string | null;
    status: PayoutStatus;
    status_label: string;
    status_color: string;
    notes?: string | null;
    processed_by?: string | null;
    processed_at: string | null;
    created_at: string;
    payments_count?: number;
    can_cancel?: boolean;
}

/**
 * Earnings summary for provider dashboard.
 */
export interface EarningsSummary {
    total_earnings: number;
    total_earnings_display: string;
    pending_payout: number;
    pending_payout_display: string;
    total_paid_out: number;
    total_paid_out_display: string;
    this_month: number;
    this_month_display: string;
    available_balance: number;
    available_balance_display: string;
}

/**
 * Monthly earnings data point.
 */
export interface MonthlyEarning {
    month: string;
    month_label: string;
    total: number;
    total_display: string;
    transaction_count: number;
}

/**
 * Wallet balance summary.
 */
export interface WalletBalance {
    total: number;
    total_display: string;
    available: number;
    available_display: string;
    held: number;
    held_display: string;
    pending_payout: number;
    pending_payout_display: string;
}

/**
 * Ledger entry type.
 */
export type LedgerEntryType = 'credit' | 'debit' | 'hold' | 'release';

/**
 * Ledger entry for wallet transactions.
 */
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
    description: string | null;
    booking_uuid: string | null;
    payment_uuid: string | null;
    created_at: string;
}

/**
 * Scheduled payout.
 */
export interface ScheduledPayout {
    id: number;
    uuid: string;
    amount: number;
    amount_display: string;
    status: string;
    status_label: string;
    status_color: string;
    scheduled_for: string;
    can_cancel: boolean;
}

/**
 * Payment totals for admin dashboard.
 */
export interface PaymentTotals {
    total_amount: number;
    total_fees: number;
    total_provider: number;
    pending_count: number;
}

/**
 * Payout settings.
 */
export interface PayoutSettings {
    schedule: 'daily' | 'weekly' | 'monthly';
    schedule_label: string;
    minimum_amount: number;
    minimum_amount_display: string;
    next_payout_date: string | null;
}

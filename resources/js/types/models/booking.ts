/**
 * Booking Status
 */
export type BookingStatus =
    | 'pending'
    | 'confirmed'
    | 'completed'
    | 'cancelled'
    | 'no_show';

/**
 * Client info as nested in a Booking.
 * Used for both guest and registered users.
 */
export interface BookingClient {
    name: string | null;
    email: string | null;
    phone: string | null;
    avatar: string | null;
    is_guest: boolean;
}

/**
 * Provider info as nested in a Booking (client view).
 */
export interface BookingProvider {
    id: number;
    uuid: string;
    business_name: string;
    slug: string;
    avatar: string | null;
    address: string | null;
}

/**
 * Service info as nested in a Booking.
 */
export interface BookingService {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    duration_minutes: number;
    price: number;
    price_display: string;
}

/**
 * Payment info as nested in a Booking.
 */
export interface BookingPayment {
    id: number;
    uuid: string;
    amount: number;
    amount_display: string;
    status: string;
    status_label: string;
    status_color: string;
    payment_type: 'deposit' | 'full';
    card_display: string | null;
    paid_at: string | null;
}

/**
 * Review info as nested in a Booking.
 */
export interface BookingReview {
    id: number;
    uuid: string;
    rating: number;
    comment: string | null;
    provider_response: string | null;
    created_at: string;
}

/**
 * Comprehensive Booking interface.
 * Supports all contexts: Provider, Client, Admin views.
 */
export interface Booking {
    id: number;
    uuid: string;

    // Nested relationships (optional based on context)
    client?: BookingClient;
    provider?: BookingProvider;
    service?: BookingService;
    payment?: BookingPayment | null;
    review?: BookingReview | null;

    // Date/time (all snake_case)
    booking_date: string;
    formatted_date: string;
    formatted_time: string;
    start_time: string | null;
    end_time: string | null;

    // Status with pre-computed display values
    status: BookingStatus;
    status_label: string;
    status_color: string;
    status_icon: string;

    // Amounts
    service_price: number;
    platform_fee: number;
    total_amount: number;
    total_display: string;

    // Notes
    client_notes: string | null;
    provider_notes: string | null;
    cancellation_reason: string | null;

    // Computed state booleans
    is_past: boolean;
    is_today: boolean;
    is_guest_booking: boolean;

    // Action permissions
    can_confirm: boolean;
    can_complete: boolean;
    can_cancel: boolean;
    can_review: boolean;

    // Deposit/payment tracking
    requires_deposit: boolean;
    deposit_amount: number;
    deposit_paid: boolean;
    balance_amount: number;
    can_pay: boolean;

    // Fee breakdown (new structure)
    zeen_fee: number;
    gateway_fee: number;
    convenience_fee: number;
    fee_payer: 'provider' | 'client';

    // Timestamps
    confirmed_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    created_at: string;
}

/**
 * Booking counts for status tabs.
 */
export interface BookingCounts {
    all: number;
    pending: number;
    confirmed: number;
    completed: number;
    cancelled: number;
}

/**
 * Booking status option for filters.
 */
export interface BookingStatusOption {
    value: string;
    label: string;
    color: string;
}

/**
 * Fee calculation result from SubscriptionService.calculateFees().
 * Used during booking creation to display fee breakdown.
 */
export interface BookingFees {
    tier: string;
    tier_label: string;
    service_price: number;

    deposit_amount: number;
    deposit_percentage: number;
    requires_deposit: boolean;

    // Separated fee structure
    zeen_fee: number;
    zeen_fee_rate: number;
    gateway_fee: number;
    gateway_fee_rate: number;
    total_fees: number;
    total_fee_rate: number;

    // Legacy aliases for backwards compatibility
    platform_fee: number;
    platform_fee_rate: number;

    // Fee payer determines who bears the fees
    convenience_fee: number;
    fee_payer: 'provider' | 'client';
    client_pays: number;
    provider_receives: number;
}

/**
 * Paginated bookings response.
 */
export interface PaginatedBookings {
    data: Booking[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

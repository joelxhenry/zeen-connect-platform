/**
 * Client info as nested in a Review.
 */
export interface ReviewClient {
    name: string | null;
    avatar: string | null;
}

/**
 * Provider info as nested in a Review.
 */
export interface ReviewProvider {
    id: number;
    uuid: string;
    business_name: string;
    slug: string;
}

/**
 * Media item in a Review.
 */
export interface ReviewMedia {
    id: number;
    uuid: string;
    url: string;
    thumbnail: string;
}

/**
 * Review interface.
 */
export interface Review {
    id: number;
    uuid: string;

    // Rating
    rating: number;
    rating_stars: string;

    // Content
    comment: string | null;
    provider_response: string | null;

    // Pre-computed display values
    formatted_date: string;
    time_ago: string;

    // Nested (optional based on context)
    client?: ReviewClient;
    service_name?: string | null;
    provider?: ReviewProvider;
    media?: ReviewMedia[];

    // State
    has_response: boolean;
    can_respond: boolean;
    is_visible: boolean;
    is_flagged: boolean;

    // Timestamps
    provider_responded_at: string | null;
    created_at: string;
}

/**
 * Review statistics for a provider.
 */
export interface ReviewStats {
    total: number;
    average: number;
    average_display: string;
    distribution: {
        1: number;
        2: number;
        3: number;
        4: number;
        5: number;
    };
}

/**
 * Paginated reviews response.
 */
export interface PaginatedReviews {
    data: Review[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

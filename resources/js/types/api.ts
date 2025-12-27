/**
 * API Response Types
 * Matches the backend ApiResponse class structure
 */

export interface ApiSuccessResponse<T = unknown> {
    success: true;
    data?: T;
    message?: string;
    meta?: PaginationMeta;
    links?: PaginationLinks;
}

export interface ApiErrorResponse {
    success: false;
    error: {
        message: string;
        code?: string;
        errors?: Record<string, string[]>;
    };
}

export type ApiResponse<T = unknown> = ApiSuccessResponse<T> | ApiErrorResponse;

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface PaginationLinks {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
}

export interface PaginatedResponse<T> extends ApiSuccessResponse<T[]> {
    meta: PaginationMeta;
    links: PaginationLinks;
}

/**
 * Type guard to check if response is successful
 */
export function isApiSuccess<T>(response: ApiResponse<T>): response is ApiSuccessResponse<T> {
    return response.success === true;
}

/**
 * Type guard to check if response is an error
 */
export function isApiError(response: ApiResponse): response is ApiErrorResponse {
    return response.success === false;
}

/**
 * Request configuration options
 */
export interface ApiRequestOptions extends Omit<RequestInit, 'body' | 'method'> {
    /** Query parameters to append to URL */
    params?: Record<string, string | number | boolean | undefined>;
    /** Request timeout in milliseconds */
    timeout?: number;
    /** Whether to include credentials (cookies) */
    withCredentials?: boolean;
}

/**
 * API Error class for typed error handling
 */
export class ApiError extends Error {
    public readonly status: number;
    public readonly code?: string;
    public readonly errors?: Record<string, string[]>;

    constructor(message: string, status: number, code?: string, errors?: Record<string, string[]>) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.code = code;
        this.errors = errors;
    }

    /**
     * Check if this is a validation error
     */
    isValidationError(): boolean {
        return this.status === 422;
    }

    /**
     * Check if this is an authentication error
     */
    isAuthError(): boolean {
        return this.status === 401;
    }

    /**
     * Check if this is a forbidden error
     */
    isForbidden(): boolean {
        return this.status === 403;
    }

    /**
     * Check if this is a not found error
     */
    isNotFound(): boolean {
        return this.status === 404;
    }

    /**
     * Get validation error for a specific field
     */
    getFieldError(field: string): string | undefined {
        return this.errors?.[field]?.[0];
    }

    /**
     * Get all validation errors as a flat array of messages
     */
    getAllErrors(): string[] {
        if (!this.errors) return [this.message];
        return Object.values(this.errors).flat();
    }
}

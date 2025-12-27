import { ref, shallowRef, readonly, type Ref } from 'vue';
import { useErrorHandler } from './useErrorHandler';
import {
    type ApiResponse,
    type ApiSuccessResponse,
    type ApiRequestOptions,
    ApiError,
    isApiSuccess,
    isApiError,
} from '@/types/api';

interface UseApiOptions {
    /** Whether to show toast on error */
    showErrorToast?: boolean;
    /** Whether to show toast on success */
    showSuccessToast?: boolean;
    /** Default timeout in milliseconds */
    timeout?: number;
}

interface UseApiReturn<T> {
    /** Response data */
    data: Readonly<Ref<T | null>>;
    /** Error if request failed */
    error: Readonly<Ref<ApiError | null>>;
    /** Loading state */
    loading: Readonly<Ref<boolean>>;
    /** Execute GET request */
    get: <R = T>(url: string, options?: ApiRequestOptions) => Promise<R>;
    /** Execute POST request */
    post: <R = T>(url: string, body?: unknown, options?: ApiRequestOptions) => Promise<R>;
    /** Execute PUT request */
    put: <R = T>(url: string, body?: unknown, options?: ApiRequestOptions) => Promise<R>;
    /** Execute PATCH request */
    patch: <R = T>(url: string, body?: unknown, options?: ApiRequestOptions) => Promise<R>;
    /** Execute DELETE request */
    delete: <R = T>(url: string, options?: ApiRequestOptions) => Promise<R>;
    /** Upload file(s) with FormData */
    upload: <R = T>(url: string, formData: FormData, options?: ApiRequestOptions) => Promise<R>;
    /** Reset state */
    reset: () => void;
    /** Abort current request */
    abort: () => void;
}

/**
 * Get CSRF token from cookies
 */
function getCsrfToken(): string {
    return document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1]?.replace(/%3D/g, '=') || '';
}

/**
 * Build URL with query parameters
 */
function buildUrl(url: string, params?: Record<string, string | number | boolean | undefined>): string {
    if (!params) return url;

    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined) {
            searchParams.append(key, String(value));
        }
    });

    const queryString = searchParams.toString();
    if (!queryString) return url;

    return url.includes('?') ? `${url}&${queryString}` : `${url}?${queryString}`;
}

/**
 * Composable for making API requests with standardized response handling
 */
export function useApi<T = unknown>(options: UseApiOptions = {}): UseApiReturn<T> {
    const {
        showErrorToast = true,
        showSuccessToast = false,
        timeout = 30000,
    } = options;

    const data = shallowRef<T | null>(null);
    const error = ref<ApiError | null>(null);
    const loading = ref(false);
    const abortController = ref<AbortController | null>(null);

    const { handleError, showSuccess } = useErrorHandler({ showToast: showErrorToast });

    /**
     * Core request function
     */
    async function request<R>(
        method: string,
        url: string,
        body?: unknown,
        requestOptions: ApiRequestOptions = {}
    ): Promise<R> {
        // Abort any existing request
        if (abortController.value) {
            abortController.value.abort();
        }

        abortController.value = new AbortController();
        loading.value = true;
        error.value = null;

        const { params, timeout: requestTimeout, withCredentials = true, ...fetchOptions } = requestOptions;
        const finalUrl = buildUrl(url, params);

        // Set up timeout
        const timeoutId = setTimeout(() => {
            abortController.value?.abort();
        }, requestTimeout || timeout);

        try {
            const headers: Record<string, string> = {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
                ...(fetchOptions.headers as Record<string, string> || {}),
            };

            // Don't set Content-Type for FormData (browser sets it with boundary)
            if (body && !(body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
            }

            const response = await fetch(finalUrl, {
                method,
                headers,
                body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
                credentials: withCredentials ? 'include' : 'same-origin',
                signal: abortController.value.signal,
                ...fetchOptions,
            });

            clearTimeout(timeoutId);

            // Handle non-JSON responses
            const contentType = response.headers.get('content-type');
            if (!contentType?.includes('application/json')) {
                if (!response.ok) {
                    throw new ApiError(
                        'Server returned non-JSON response',
                        response.status
                    );
                }
                // For 204 No Content or similar
                data.value = null;
                return null as R;
            }

            const json: ApiResponse<R> = await response.json();

            if (!response.ok || isApiError(json)) {
                const errorResponse = json as { success: false; error: { message: string; code?: string; errors?: Record<string, string[]> } };
                const apiError = new ApiError(
                    errorResponse.error?.message || 'Request failed',
                    response.status,
                    errorResponse.error?.code,
                    errorResponse.error?.errors
                );
                error.value = apiError;

                if (showErrorToast) {
                    handleError(apiError, 'API');
                }

                throw apiError;
            }

            if (isApiSuccess(json)) {
                const responseData = json.data as R;
                data.value = responseData as T;

                if (showSuccessToast && json.message) {
                    showSuccess(json.message);
                }

                return responseData;
            }

            // Fallback for unexpected response format
            data.value = json as unknown as T;
            return json as unknown as R;

        } catch (err) {
            clearTimeout(timeoutId);

            if (err instanceof ApiError) {
                throw err;
            }

            if (err instanceof DOMException && err.name === 'AbortError') {
                const abortError = new ApiError('Request was cancelled', 0, 'ABORTED');
                error.value = abortError;
                throw abortError;
            }

            const networkError = new ApiError(
                err instanceof Error ? err.message : 'Network error',
                0,
                'NETWORK_ERROR'
            );
            error.value = networkError;

            if (showErrorToast) {
                handleError(networkError, 'Network');
            }

            throw networkError;

        } finally {
            loading.value = false;
            abortController.value = null;
        }
    }

    const get = <R = T>(url: string, options?: ApiRequestOptions) =>
        request<R>('GET', url, undefined, options);

    const post = <R = T>(url: string, body?: unknown, options?: ApiRequestOptions) =>
        request<R>('POST', url, body, options);

    const put = <R = T>(url: string, body?: unknown, options?: ApiRequestOptions) =>
        request<R>('PUT', url, body, options);

    const patch = <R = T>(url: string, body?: unknown, options?: ApiRequestOptions) =>
        request<R>('PATCH', url, body, options);

    const del = <R = T>(url: string, options?: ApiRequestOptions) =>
        request<R>('DELETE', url, undefined, options);

    const upload = <R = T>(url: string, formData: FormData, options?: ApiRequestOptions) =>
        request<R>('POST', url, formData, options);

    const reset = () => {
        data.value = null;
        error.value = null;
        loading.value = false;
    };

    const abort = () => {
        if (abortController.value) {
            abortController.value.abort();
            abortController.value = null;
        }
    };

    return {
        data: readonly(data) as Readonly<Ref<T | null>>,
        error: readonly(error),
        loading: readonly(loading),
        get,
        post,
        put,
        patch,
        delete: del,
        upload,
        reset,
        abort,
    };
}

/**
 * Simple one-off API call without reactive state
 * Useful for event handlers and non-reactive contexts
 */
export async function apiRequest<T>(
    method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
    url: string,
    body?: unknown,
    options: ApiRequestOptions = {}
): Promise<T> {
    const { params, timeout = 30000, withCredentials = true, ...fetchOptions } = options;
    const finalUrl = buildUrl(url, params);

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    try {
        const headers: Record<string, string> = {
            'Accept': 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
            ...(fetchOptions.headers as Record<string, string> || {}),
        };

        if (body && !(body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(finalUrl, {
            method,
            headers,
            body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
            credentials: withCredentials ? 'include' : 'same-origin',
            signal: controller.signal,
            ...fetchOptions,
        });

        clearTimeout(timeoutId);

        const contentType = response.headers.get('content-type');
        if (!contentType?.includes('application/json')) {
            if (!response.ok) {
                throw new ApiError('Server returned non-JSON response', response.status);
            }
            return null as T;
        }

        const json: ApiResponse<T> = await response.json();

        if (!response.ok || isApiError(json)) {
            const errorResponse = json as { success: false; error: { message: string; code?: string; errors?: Record<string, string[]> } };
            throw new ApiError(
                errorResponse.error?.message || 'Request failed',
                response.status,
                errorResponse.error?.code,
                errorResponse.error?.errors
            );
        }

        if (isApiSuccess(json)) {
            return json.data as T;
        }

        return json as unknown as T;

    } catch (err) {
        clearTimeout(timeoutId);

        if (err instanceof ApiError) {
            throw err;
        }

        if (err instanceof DOMException && err.name === 'AbortError') {
            throw new ApiError('Request was cancelled', 0, 'ABORTED');
        }

        throw new ApiError(
            err instanceof Error ? err.message : 'Network error',
            0,
            'NETWORK_ERROR'
        );
    }
}

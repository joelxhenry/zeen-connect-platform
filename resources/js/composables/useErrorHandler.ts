import { ref, readonly } from 'vue';
import { useToast } from 'primevue/usetoast';
import { router } from '@inertiajs/vue3';

interface ErrorDetails {
    message: string;
    code?: string | number;
    field?: string;
    context?: Record<string, unknown>;
}

interface UseErrorHandlerOptions {
    showToast?: boolean;
    reportToService?: boolean;
}

export function useErrorHandler(options: UseErrorHandlerOptions = {}) {
    const { showToast = true, reportToService = true } = options;
    const toast = useToast();

    const lastError = ref<ErrorDetails | null>(null);
    const isHandlingError = ref(false);

    /**
     * Handle any type of error and normalize it
     */
    const handleError = (error: unknown, context?: string): ErrorDetails => {
        isHandlingError.value = true;

        let details: ErrorDetails;

        if (error instanceof Error) {
            details = {
                message: error.message,
                context: { name: error.name, stack: error.stack, userContext: context },
            };
        } else if (typeof error === 'string') {
            details = { message: error, context: { userContext: context } };
        } else if (typeof error === 'object' && error !== null) {
            const err = error as Record<string, unknown>;
            details = {
                message: (err.message as string) || 'An unexpected error occurred',
                code: err.code as string | number,
                field: err.field as string,
                context: { ...err, userContext: context },
            };
        } else {
            details = { message: 'An unexpected error occurred', context: { userContext: context } };
        }

        lastError.value = details;

        // Show toast notification
        if (showToast) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: details.message,
                life: 5000,
            });
        }

        // Report to error service
        if (reportToService && window.Sentry) {
            window.Sentry.captureMessage(details.message, {
                level: 'error',
                extra: details.context,
            });
        }

        isHandlingError.value = false;
        return details;
    };

    /**
     * Handle API/HTTP errors with status-specific messages
     */
    const handleApiError = (response: {
        status: number;
        data?: { message?: string; errors?: Record<string, string[]> };
    }) => {
        const { status, data } = response;

        let message: string;

        switch (status) {
            case 401:
                message = 'Please log in to continue.';
                router.visit('/login');
                break;
            case 403:
                message = 'You do not have permission to perform this action.';
                break;
            case 404:
                message = 'The requested resource was not found.';
                break;
            case 419:
                message = 'Your session has expired. Please refresh the page.';
                break;
            case 422:
                message = data?.message || 'Please check your input and try again.';
                break;
            case 429:
                message = 'Too many requests. Please wait a moment and try again.';
                break;
            case 500:
            case 502:
            case 503:
                message = 'Server error. Please try again later.';
                break;
            default:
                message = data?.message || 'An unexpected error occurred.';
        }

        return handleError({ message, code: status }, 'API');
    };

    /**
     * Handle Inertia form errors
     */
    const handleFormErrors = (errors: Record<string, string>) => {
        const firstError = Object.values(errors)[0];
        if (firstError) {
            handleError({ message: firstError, field: Object.keys(errors)[0] }, 'Form');
        }
    };

    /**
     * Clear the last error
     */
    const clearError = () => {
        lastError.value = null;
    };

    /**
     * Show a success message
     */
    const showSuccess = (message: string, summary = 'Success') => {
        toast.add({
            severity: 'success',
            summary,
            detail: message,
            life: 3000,
        });
    };

    /**
     * Show a warning message
     */
    const showWarning = (message: string, summary = 'Warning') => {
        toast.add({
            severity: 'warn',
            summary,
            detail: message,
            life: 4000,
        });
    };

    /**
     * Show an info message
     */
    const showInfo = (message: string, summary = 'Info') => {
        toast.add({
            severity: 'info',
            summary,
            detail: message,
            life: 3000,
        });
    };

    return {
        lastError: readonly(lastError),
        isHandlingError: readonly(isHandlingError),
        handleError,
        handleApiError,
        handleFormErrors,
        clearError,
        showSuccess,
        showWarning,
        showInfo,
    };
}

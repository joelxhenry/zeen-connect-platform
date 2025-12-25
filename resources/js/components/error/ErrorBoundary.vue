<script setup lang="ts">
import { ref, onErrorCaptured, provide } from 'vue';

interface ErrorInfo {
    componentName?: string;
    info: string;
}

interface Props {
    fallback?: 'inline' | 'page';
    onError?: (error: Error, info: ErrorInfo) => void;
}

const props = withDefaults(defineProps<Props>(), {
    fallback: 'inline',
});

const error = ref<Error | null>(null);
const errorInfo = ref<ErrorInfo | null>(null);
const hasError = ref(false);

const resetError = () => {
    error.value = null;
    errorInfo.value = null;
    hasError.value = false;
};

provide('resetError', resetError);

onErrorCaptured((err, instance, info) => {
    error.value = err as Error;
    errorInfo.value = {
        componentName: instance?.$options?.name || 'Unknown',
        info,
    };
    hasError.value = true;

    // Call optional error handler
    props.onError?.(err as Error, errorInfo.value);

    // Report to Sentry if available
    if (window.Sentry) {
        window.Sentry.captureException(err as Error, {
            extra: { info, component: errorInfo.value.componentName },
        });
    }

    // Prevent error from propagating (we're handling it)
    return false;
});
</script>

<template>
    <slot v-if="!hasError" />
    <slot v-else name="fallback" :error="error" :reset="resetError">
        <div class="error-boundary-fallback">
            <div class="error-boundary-icon">
                <i class="pi pi-exclamation-triangle"></i>
            </div>
            <h3>Something went wrong</h3>
            <p>{{ error?.message || 'An unexpected error occurred in this section.' }}</p>
            <button @click="resetError" class="error-boundary-btn">
                <i class="pi pi-refresh"></i>
                Try Again
            </button>
        </div>
    </slot>
</template>

<style scoped>
.error-boundary-fallback {
    padding: 2rem;
    text-align: center;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 1px solid #fecaca;
    border-radius: 16px;
    margin: 1rem 0;
}

.error-boundary-icon {
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
}

.error-boundary-icon i {
    font-size: 1.75rem;
    color: #dc2626;
}

.error-boundary-fallback h3 {
    color: #0D1F1B;
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.error-boundary-fallback p {
    color: #6b7280;
    margin: 0 0 1.25rem 0;
    font-size: 0.9375rem;
    line-height: 1.5;
}

.error-boundary-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #106B4F;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.error-boundary-btn:hover {
    background: #0D5A42;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 107, 79, 0.25);
}
</style>

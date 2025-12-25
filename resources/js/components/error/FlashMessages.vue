<script setup lang="ts">
import { watch, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';

interface FlashData {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}

const page = usePage();
const toast = useToast();

// Track shown messages to prevent duplicates
const shownMessages = ref<Set<string>>(new Set());

// Watch for flash messages from Inertia shared props
watch(
    () => page.props.flash as FlashData | undefined,
    (flash) => {
        if (!flash) return;

        const messageTypes: Array<{
            key: keyof FlashData;
            severity: 'success' | 'error' | 'warn' | 'info';
            summary: string;
            life: number;
        }> = [
            { key: 'success', severity: 'success', summary: 'Success', life: 3000 },
            { key: 'error', severity: 'error', summary: 'Error', life: 5000 },
            { key: 'warning', severity: 'warn', summary: 'Warning', life: 4000 },
            { key: 'info', severity: 'info', summary: 'Info', life: 3000 },
        ];

        messageTypes.forEach(({ key, severity, summary, life }) => {
            const message = flash[key];

            // Only show if message exists and hasn't been shown
            if (message && !shownMessages.value.has(`${key}:${message}`)) {
                const messageKey = `${key}:${message}`;
                shownMessages.value.add(messageKey);

                toast.add({
                    severity,
                    summary,
                    detail: message,
                    life,
                });

                // Clear from tracking after showing
                setTimeout(() => {
                    shownMessages.value.delete(messageKey);
                }, 100);
            }
        });
    },
    { immediate: true, deep: true }
);
</script>

<template>
    <Toast position="top-right" :pt="toastStyles" />
</template>

<script lang="ts">
// Custom styling for toast notifications
const toastStyles = {
    root: {
        class: 'zeen-toast',
    },
    message: {
        class: 'zeen-toast-message',
    },
};
</script>

<style>
/* Custom toast styles */
.zeen-toast {
    font-family: 'Outfit', sans-serif;
}

.zeen-toast .p-toast-message {
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.zeen-toast .p-toast-message-success {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid #a7f3d0;
}

.zeen-toast .p-toast-message-success .p-toast-message-icon {
    color: #059669;
}

.zeen-toast .p-toast-message-success .p-toast-summary {
    color: #065f46;
}

.zeen-toast .p-toast-message-success .p-toast-detail {
    color: #047857;
}

.zeen-toast .p-toast-message-error {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 1px solid #fecaca;
}

.zeen-toast .p-toast-message-error .p-toast-message-icon {
    color: #dc2626;
}

.zeen-toast .p-toast-message-error .p-toast-summary {
    color: #991b1b;
}

.zeen-toast .p-toast-message-error .p-toast-detail {
    color: #b91c1c;
}

.zeen-toast .p-toast-message-warn {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1px solid #fde68a;
}

.zeen-toast .p-toast-message-warn .p-toast-message-icon {
    color: #d97706;
}

.zeen-toast .p-toast-message-warn .p-toast-summary {
    color: #92400e;
}

.zeen-toast .p-toast-message-warn .p-toast-detail {
    color: #b45309;
}

.zeen-toast .p-toast-message-info {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid #bfdbfe;
}

.zeen-toast .p-toast-message-info .p-toast-message-icon {
    color: #2563eb;
}

.zeen-toast .p-toast-message-info .p-toast-summary {
    color: #1e40af;
}

.zeen-toast .p-toast-message-info .p-toast-detail {
    color: #1d4ed8;
}

.zeen-toast .p-toast-summary {
    font-weight: 600;
    font-size: 0.9375rem;
}

.zeen-toast .p-toast-detail {
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
</style>

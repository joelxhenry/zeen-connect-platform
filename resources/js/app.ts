import '../css/app.css';
import 'primeicons/primeicons.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Zeen';

// Global error handler for uncaught errors
const handleGlobalError = (error: Error, context?: string) => {
    console.error(`[Zeen Error${context ? ` - ${context}` : ''}]`, error);

    // Report to Sentry if available
    if (window.Sentry) {
        window.Sentry.captureException(error, {
            tags: { context: context || 'global' },
        });
    }
};

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', (event) => {
    handleGlobalError(
        event.reason instanceof Error ? event.reason : new Error(String(event.reason)),
        'unhandledrejection'
    );
});

// Handle global window errors
window.addEventListener('error', (event) => {
    handleGlobalError(event.error || new Error(event.message), 'window.onerror');
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Global Vue error handler
        app.config.errorHandler = (err, instance, info) => {
            const error = err instanceof Error ? err : new Error(String(err));
            handleGlobalError(error, `Vue: ${info}`);

            // Log component info in development
            if (import.meta.env.DEV) {
                console.error('Vue Error:', err);
                console.error('Component:', instance);
                console.error('Info:', info);
            }
        };

        // Global warning handler (development only)
        if (import.meta.env.DEV) {
            app.config.warnHandler = (msg, _instance, trace) => {
                console.warn('[Vue Warn]:', msg);
                if (trace) console.warn('Trace:', trace);
            };
        }

        // Register Inertia plugin
        app.use(plugin);

        // Register Ziggy for route() helper
        app.use(ZiggyVue);

        // Register PrimeVue with Aura theme
        app.use(PrimeVue, {
            theme: {
                preset: Aura,
                options: {
                    prefix: 'p',
                    darkModeSelector: '.dark',
                    cssLayer: false,
                },
            },
        });

        // Register PrimeVue services
        app.use(ToastService);
        app.use(ConfirmationService);

        app.mount(el);
    },
    progress: {
        color: '#106B4F',
    },
});

// Extend Window interface for Sentry
declare global {
    interface Window {
        Sentry?: {
            captureException: (error: Error, options?: Record<string, unknown>) => void;
            captureMessage: (message: string, options?: Record<string, unknown>) => void;
        };
    }
}

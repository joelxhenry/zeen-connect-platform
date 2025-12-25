import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, DefineComponent, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import { ZiggyVue } from 'ziggy-js';
import AppLink from '@/components/common/AppLink.vue';
import { resolveUrl } from '@/utils/url';

const appName = import.meta.env.VITE_APP_NAME || 'Zeen';

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => (title ? `${title} - ${appName}` : appName),
            resolve: resolvePage,
            setup: ({ App, props, plugin }) => {
                const app = createSSRApp({ render: () => h(App, props) });

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

                // Register global components
                app.component('AppLink', AppLink);

                // Provide global URL resolver
                app.provide('resolveUrl', resolveUrl);
                app.config.globalProperties.$resolveUrl = resolveUrl;

                return app;
            },
        }),
    { cluster: true },
);

function resolvePage(name: string) {
    const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

    return resolvePageComponent<DefineComponent>(`./pages/${name}.vue`, pages);
}

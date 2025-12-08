import '../css/app.css';
import 'element-plus/dist/index.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import ElementPlus from 'element-plus';
import * as ElementPlusIconsVue from '@element-plus/icons-vue';

const appName = import.meta.env.VITE_APP_NAME || 'Zeen';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Register Element Plus
        app.use(plugin);
        app.use(ElementPlus);

        // Register all Element Plus icons globally
        for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
            app.component(key, component);
        }

        app.mount(el);
    },
    progress: {
        color: '#409EFF',
    },
});

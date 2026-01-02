import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: true,
        // For local dev: Extract hostname from APP_URL and use with :5173
        // For remote dev: Use VITE_DEV_SERVER_URL if explicitly set
        origin: process.env.VITE_DEV_SERVER_URL && !process.env.VITE_DEV_SERVER_URL.includes(':8000')
            ? process.env.VITE_DEV_SERVER_URL
            : (function() {
                const appUrl = process.env.APP_URL || 'http://localhost';
                try {
                    const url = new URL(appUrl);
                    return `${url.protocol}//${url.hostname}:5173`;
                } catch {
                    return 'http://localhost:5173';
                }
            })(),
        hmr: {
            // Extract hostname from VITE_HMR_HOST (or APP_URL for local dev)
            host: (function() {
                const hmrHost = process.env.VITE_HMR_HOST;
                if (hmrHost) {
                    try {
                        // If it's a full URL, extract hostname
                        const url = new URL(hmrHost.startsWith('http') ? hmrHost : `http://${hmrHost}`);
                        return url.hostname;
                    } catch {
                        return hmrHost;
                    }
                }
                // Default to extracting from APP_URL
                try {
                    const url = new URL(process.env.APP_URL || 'http://localhost');
                    return url.hostname;
                } catch {
                    return 'localhost';
                }
            })(),
            // Use WSS only for remote dev (when HMR host is explicitly set and is HTTPS)
            protocol: process.env.VITE_HMR_HOST?.startsWith('https://') ? 'wss' : 'ws',
            // Use 443 for remote HTTPS, otherwise 5173
            clientPort: process.env.VITE_HMR_HOST?.startsWith('https://') ? 443 : 5173,
        },
    },
});

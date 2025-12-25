import { resolveUrl, resolveRoute } from '@/utils/url';

/**
 * Composable for working with routes
 *
 * This composable provides utilities for resolving route URLs
 * to work correctly in development with custom ports.
 */
export function useRoute() {
    /**
     * Resolve a URL for the current environment
     */
    const resolve = (url: string) => resolveUrl(url);

    /**
     * Get the resolved URL from a route definition
     */
    const url = <T extends { url: string }>(route: T): string => {
        return resolveUrl(route.url);
    };

    /**
     * Get a resolved route definition
     */
    const route = <T extends { url: string }>(routeDef: T): T => {
        return resolveRoute(routeDef);
    };

    return {
        resolve,
        url,
        route,
    };
}

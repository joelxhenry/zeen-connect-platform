/**
 * URL Utilities for development environment
 *
 * Wayfinder generates protocol-relative URLs like `//localhost/path` which don't include
 * the port. In development with `php artisan serve` on port 8000, these URLs resolve
 * to port 80 instead of 8000.
 *
 * This utility resolves URLs correctly based on the current window location.
 */

// Known domains from the app configuration
const KNOWN_DOMAINS = [
    'zeenconnect.test',
    'auth.zeenconnect.test',
    'admin.zeenconnect.test',
    'console.zeenconnect.test',
    'payments.zeenconnect.test',

    // Localhost variants
    'localhost',
    'auth.localhost',
    'admin.localhost',
    'console.localhost',
    'payments.localhost',
];

/**
 * Check if a domain is a known internal domain
 */
function isKnownDomain(domain: string): boolean {
    return KNOWN_DOMAINS.some(known =>
        domain === known || domain.endsWith(`.${known}`)
    );
}

/**
 * Extract domain and path from a URL
 */
function parseUrl(url: string): { domain: string | null; path: string; isProtocolRelative: boolean } {
    // Protocol-relative URL (//domain/path)
    if (url.startsWith('//')) {
        const withoutProtocol = url.slice(2);
        const slashIndex = withoutProtocol.indexOf('/');
        if (slashIndex === -1) {
            return { domain: withoutProtocol, path: '/', isProtocolRelative: true };
        }
        return {
            domain: withoutProtocol.slice(0, slashIndex),
            path: withoutProtocol.slice(slashIndex),
            isProtocolRelative: true,
        };
    }

    // Absolute URL with protocol
    if (url.startsWith('http://') || url.startsWith('https://')) {
        try {
            const parsed = new URL(url);
            return {
                domain: parsed.host,
                path: parsed.pathname + parsed.search + parsed.hash,
                isProtocolRelative: false,
            };
        } catch {
            return { domain: null, path: url, isProtocolRelative: false };
        }
    }

    // Relative URL
    return { domain: null, path: url, isProtocolRelative: false };
}

/**
 * Get the base domain from a hostname (e.g., auth.localhost -> localhost)
 */
function getBaseDomain(hostname: string): string {
    const parts = hostname.split('.');
    // For localhost-style domains, the base is the last part
    if (parts[parts.length - 1] === 'localhost') {
        return 'localhost';
    }
    // For regular domains, return last two parts
    return parts.slice(-2).join('.');
}

/**
 * Resolve a URL for the current environment
 *
 * In development, this converts protocol-relative URLs to work with the current port.
 * For example: `//auth.localhost/login` -> `http://auth.localhost:8000/login`
 *
 * If the domain matches the current page's domain, it returns just the path.
 */
export function resolveUrl(url: string): string {

    if (typeof window === 'undefined') {
        // SSR: return URL as-is, the client will resolve it
        return url;
    }

    const { domain, path, isProtocolRelative } = parseUrl(url);

    // No domain to resolve
    if (!domain) {
        return url;
    }

    const currentHost = window.location.hostname;
    const currentPort = window.location.port;
    const currentProtocol = window.location.protocol;

    // If it's the same domain as current page, use relative path
    if (domain === currentHost) {
        return path;
    }

    // For protocol-relative URLs with known domains, add the current port
    if (isProtocolRelative && isKnownDomain(domain)) {
        // Check if we're on the same base domain
        const urlBaseDomain = getBaseDomain(domain);
        const currentBaseDomain = getBaseDomain(currentHost);

        if (urlBaseDomain === currentBaseDomain && currentPort) {
            // Same base domain, add the port
            return `${currentProtocol}//${domain}:${currentPort}${path}`;
        }
    }

    // For protocol-relative URLs, use current protocol
    if (isProtocolRelative) {
        return `${currentProtocol}${url}`;
    }

    return url;
}

/**
 * Create a route object with resolved URL
 *
 * This wraps route definitions to automatically resolve URLs.
 * Usage: resolveRoute(home()).url
 */
export function resolveRoute<T extends { url: string }>(route: T): T {
    return {
        ...route,
        url: resolveUrl(route.url),
    };
}

/**
 * Higher-order function to wrap route functions
 *
 * Usage:
 * const resolvedHome = withResolvedUrl(home);
 * resolvedHome().url // Returns resolved URL
 */
export function withResolvedUrl<TArgs extends unknown[], TReturn extends { url: string }>(
    routeFn: (...args: TArgs) => TReturn
): (...args: TArgs) => TReturn {
    return (...args: TArgs) => resolveRoute(routeFn(...args));
}

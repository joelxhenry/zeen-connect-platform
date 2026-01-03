import { computed, ref, onMounted, onUnmounted, type ComputedRef, type Ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface ProviderBrandingData {
    id: number;
    business_name: string;
    domain: string;
    avatar?: string;
    cover_image?: string;
    logo?: string;
    brand_primary_color?: string;
    brand_primary_rgb?: string;
    brand_hover_color?: string;
    brand_text_color?: string;
    brand_success_color?: string;
    brand_warning_color?: string;
    brand_danger_color?: string;
    brand_info_color?: string;
    brand_secondary_color?: string;
    brand_accent_color?: string;
    color_mode?: 'light' | 'dark' | 'system';
}

export interface BrandingCssVars {
    '--provider-primary'?: string;
    '--provider-primary-rgb'?: string;
    '--provider-primary-hover'?: string;
    '--provider-text'?: string;
    '--provider-success'?: string;
    '--provider-warning'?: string;
    '--provider-danger'?: string;
    '--provider-info'?: string;
    '--provider-secondary'?: string;
    '--provider-accent'?: string;
    [key: string]: string | undefined;
}

export interface UseProviderBrandingReturn {
    provider: ComputedRef<ProviderBrandingData | null>;
    brandingCssVars: ComputedRef<BrandingCssVars>;
    colorMode: ComputedRef<'light' | 'dark'>;
    colorModeClass: ComputedRef<string>;
    prefersDark: Ref<boolean>;
    isSystemDark: Ref<boolean>;
}

// Default branding colors
const DEFAULT_BRANDING: BrandingCssVars = {
    '--provider-primary': '#106B4F',
    '--provider-primary-rgb': '16, 107, 79',
    '--provider-primary-hover': '#0D5A42',
    '--provider-text': '#0D1F1B',
    '--provider-success': '#22C55E',
    '--provider-warning': '#F59E0B',
    '--provider-danger': '#EF4444',
    '--provider-info': '#3B82F6',
    '--provider-secondary': '#6B7280',
    '--provider-accent': '#8B5CF6',
};

/**
 * Composable for managing provider branding across the provider site
 * Handles CSS variable injection, color mode detection, and system preference
 */
export function useProviderBranding(): UseProviderBrandingReturn {
    const page = usePage();

    // Track system color preference
    const isSystemDark = ref(false);
    let mediaQuery: MediaQueryList | null = null;

    // Get provider data from Inertia page props
    const provider = computed<ProviderBrandingData | null>(() => {
        return page.props.__provider as ProviderBrandingData | null;
    });

    // Detect system preference for dark mode
    const updateSystemPreference = (e?: MediaQueryListEvent) => {
        isSystemDark.value = e?.matches ?? mediaQuery?.matches ?? false;
    };

    onMounted(() => {
        if (typeof window !== 'undefined') {
            mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            isSystemDark.value = mediaQuery.matches;
            mediaQuery.addEventListener('change', updateSystemPreference);
        }
    });

    onUnmounted(() => {
        if (mediaQuery) {
            mediaQuery.removeEventListener('change', updateSystemPreference);
        }
    });

    // Determine if dark mode should be preferred based on provider setting
    const prefersDark = computed(() => {
        const mode = provider.value?.color_mode ?? 'system';
        if (mode === 'dark') return true;
        if (mode === 'light') return false;
        return isSystemDark.value;
    });

    // Effective color mode (light or dark)
    const colorMode = computed<'light' | 'dark'>(() => {
        return prefersDark.value ? 'dark' : 'light';
    });

    // CSS class for color mode
    const colorModeClass = computed(() => {
        return `color-mode-${colorMode.value}`;
    });

    // Generate CSS variables from provider branding
    const brandingCssVars = computed<BrandingCssVars>(() => {
        const p = provider.value;
        if (!p) return DEFAULT_BRANDING;

        const vars: BrandingCssVars = { ...DEFAULT_BRANDING };

        // Override with provider-specific colors
        if (p.brand_primary_color) {
            vars['--provider-primary'] = p.brand_primary_color;
        }
        if (p.brand_primary_rgb) {
            vars['--provider-primary-rgb'] = p.brand_primary_rgb;
        }
        if (p.brand_hover_color) {
            vars['--provider-primary-hover'] = p.brand_hover_color;
        }
        if (p.brand_text_color) {
            vars['--provider-text'] = p.brand_text_color;
        }
        if (p.brand_success_color) {
            vars['--provider-success'] = p.brand_success_color;
        }
        if (p.brand_warning_color) {
            vars['--provider-warning'] = p.brand_warning_color;
        }
        if (p.brand_danger_color) {
            vars['--provider-danger'] = p.brand_danger_color;
        }
        if (p.brand_info_color) {
            vars['--provider-info'] = p.brand_info_color;
        }
        if (p.brand_secondary_color) {
            vars['--provider-secondary'] = p.brand_secondary_color;
        }
        if (p.brand_accent_color) {
            vars['--provider-accent'] = p.brand_accent_color;
        }

        // Generate derived variables
        const primaryRgb = vars['--provider-primary-rgb'] || '16, 107, 79';
        vars['--provider-primary-10'] = `rgba(${primaryRgb}, 0.1)`;
        vars['--provider-primary-05'] = `rgba(${primaryRgb}, 0.05)`;
        vars['--provider-primary-20'] = `rgba(${primaryRgb}, 0.2)`;

        return vars;
    });

    return {
        provider,
        brandingCssVars,
        colorMode,
        colorModeClass,
        prefersDark,
        isSystemDark,
    };
}

/**
 * Helper to convert hex color to RGB string
 */
export function hexToRgb(hex: string): string | null {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (!result) return null;
    return `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}`;
}

/**
 * Helper to darken a hex color by a percentage
 */
export function darkenColor(hex: string, percent: number): string {
    const cleanHex = hex.replace('#', '');
    const r = Math.max(0, Math.floor(parseInt(cleanHex.substring(0, 2), 16) * (100 - percent) / 100));
    const g = Math.max(0, Math.floor(parseInt(cleanHex.substring(2, 4), 16) * (100 - percent) / 100));
    const b = Math.max(0, Math.floor(parseInt(cleanHex.substring(4, 6), 16) * (100 - percent) / 100));
    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
}

/**
 * Helper to lighten a hex color by a percentage
 */
export function lightenColor(hex: string, percent: number): string {
    const cleanHex = hex.replace('#', '');
    const r = Math.min(255, Math.floor(parseInt(cleanHex.substring(0, 2), 16) + (255 - parseInt(cleanHex.substring(0, 2), 16)) * percent / 100));
    const g = Math.min(255, Math.floor(parseInt(cleanHex.substring(2, 4), 16) + (255 - parseInt(cleanHex.substring(2, 4), 16)) * percent / 100));
    const b = Math.min(255, Math.floor(parseInt(cleanHex.substring(4, 6), 16) + (255 - parseInt(cleanHex.substring(4, 6), 16)) * percent / 100));
    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
}

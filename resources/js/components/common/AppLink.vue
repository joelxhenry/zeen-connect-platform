<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { resolveUrl } from '@/utils/url';

interface Props {
    href: string;
    as?: string;
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    data?: Record<string, unknown>;
    replace?: boolean;
    preserveScroll?: boolean;
    preserveState?: boolean;
    only?: string[];
    except?: string[];
    headers?: Record<string, string>;
    queryStringArrayFormat?: 'brackets' | 'indices';
}

const props = withDefaults(defineProps<Props>(), {
    method: 'get',
});

const resolvedHref = computed(() => resolveUrl(props.href));

const isSameDomain = computed(() => {
    const resolved = resolvedHref.value;

    // Relative URLs are always same domain
    if (resolved.startsWith('/') && !resolved.startsWith('//')) {
        return true;
    }

    try {
        const targetUrl = new URL(resolved, window.location.origin);
        const currentHost = window.location.host;
        const targetHost = targetUrl.host;

        // Same host means same domain (including port)
        return currentHost === targetHost;
    } catch {
        // If URL parsing fails, assume same domain for relative-like paths
        return true;
    }
});

const isGetMethod = computed(() => props.method === 'get');

// Get CSRF token from page props or meta tag
const csrfToken = computed(() => {
    const page = usePage();
    // Try to get from Inertia props first
    if (page.props?.csrf_token) {
        return page.props.csrf_token as string;
    }
    // Fallback to meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag?.getAttribute('content') || '';
});

</script>

<template>
    <!-- Use Inertia Link for same-domain navigation -->
    <Link v-if="isSameDomain" :href="resolvedHref" :as="as" :method="method" :data="data" :replace="replace"
        :preserve-scroll="preserveScroll" :preserve-state="preserveState" :only="only" :except="except"
        :headers="headers" :query-string-array-format="queryStringArrayFormat">
        <slot />
    </Link>
    <!-- Use regular anchor for cross-domain GET requests -->
    <a v-else-if="isGetMethod" :href="resolvedHref">
        <slot />
    </a>
    <!-- Use form for cross-domain non-GET requests -->
    <form v-else :action="resolvedHref" :method="method === 'get' ? 'get' : 'post'" style="display: inline;">
        <input type="hidden" name="_token" :value="csrfToken" />
        <input v-if="method !== 'post'" type="hidden" name="_method" :value="method" />
        <template v-if="data">
            <input v-for="(value, key) in data" :key="key" type="hidden" :name="String(key)" :value="String(value)" />
        </template>
        <button type="submit" style="all: inherit; cursor: pointer;">
            <slot />
        </button>
    </form>
</template>

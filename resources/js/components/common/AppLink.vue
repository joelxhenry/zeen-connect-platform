<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
</script>

<template>
    <!-- Use Inertia Link for same-domain navigation -->
    <Link
        v-if="isSameDomain"
        :href="resolvedHref"
        :as="as"
        :method="method"
        :data="data"
        :replace="replace"
        :preserve-scroll="preserveScroll"
        :preserve-state="preserveState"
        :only="only"
        :except="except"
        :headers="headers"
        :query-string-array-format="queryStringArrayFormat"
    >
        <slot />
    </Link>
    <!-- Use regular anchor for cross-domain/subdomain navigation -->
    <a v-else :href="resolvedHref">
        <slot />
    </a>
</template>
